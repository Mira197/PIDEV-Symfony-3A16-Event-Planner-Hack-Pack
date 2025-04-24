<?php
// src/Controller/AyaOrderController.php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\Cart;
use App\Entity\WalletTransaction;
use App\Entity\GiftCard;
use App\Entity\FidelityPoint;
use App\Form\AyaOrderType;
use App\Repository\CartRepository;
use App\Repository\CartProductRepository;
use App\Repository\UserRepository;
use App\Repository\WalletTransactionRepository;
use App\Repository\GiftCardRepository;
use App\Repository\FidelityPointRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class AyaOrderController extends AbstractController
{
    #[Route('/aya/order/new', name: 'aya_order_new')]
    public function new(
        Request $request,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        WalletTransactionRepository $walletTransactionRepository,
        GiftCardRepository $giftCardRepository,
        FidelityPointRepository $fidelityPointRepository,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        // Récupérer l'utilisateur connecté
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // Récupérer le panier de l'utilisateur
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            $this->addFlash('warning', 'No cart found.');
            return $this->redirectToRoute('aya_cart');
        }

        // Récupérer les produits du panier
        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

        // Calcul du total du panier
        $total = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }

        // Initialiser l'entité de commande
        $order = new Order();
        $order->setUser($user);
        $order->setOrderedAt(new \DateTime());

        // Initialiser le prix final
        $finalPrice = $total;

        // Appliquer le coupon si nécessaire
        $couponCode = $request->get('coupon_code');
        if ($couponCode) {
            if ($couponCode == 'PROMO10') {
                $couponDiscount = 0.10 * $total;  // Réduction de 10%
                $finalPrice -= $couponDiscount;  // Appliquer la réduction
            }
        }

        // Appliquer la carte cadeau si présente
        $giftCardCode = $request->get('gift_card_code');
        $giftCardPin = $request->get('gift_card_pin');
        if ($giftCardCode && $giftCardPin) {
            $giftCard = $giftCardRepository->findOneBy(['code' => $giftCardCode, 'pin' => $giftCardPin]);
            if ($giftCard && !$giftCard->isUsed()) {
                $finalPrice -= $giftCard->getBalance(); // Appliquer le solde de la carte cadeau
                $giftCard->setIsUsed(true); // Marquer la carte cadeau comme utilisée
                $em->flush();
            }
        }

        // Appliquer le crédit du portefeuille si présent
        $walletAmount = $request->get('wallet_credit');
        if ($walletAmount) {
            $finalPrice -= $walletAmount;  // Appliquer le crédit du portefeuille
        }

        // Appliquer les points de fidélité 3alakifi si présents
        $points = $request->get('points');
        if ($points) {
            $fidelityPoints = $fidelityPointRepository->findOneBy(['user' => $user]);
            if ($fidelityPoints && $fidelityPoints->getPoints() >= $points) {
                $fidelityPoints->setPoints($fidelityPoints->getPoints() - $points);
                $finalPrice -= $points; // Déduire la valeur des points du total
                $em->flush();
            }
        }

        // Enregistrer le montant final dans la commande
        $order->setTotalPrice($finalPrice);

        // Gérer la soumission du formulaire de commande
        $form = $this->createForm(AyaOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setStatus('PENDING'); // Statut initial de la commande

            // Créer un nouveau panier vide pour l'utilisateur
            $newCart = new Cart();
            $newCart->setUser($user);
            $em->persist($newCart);

            // Associer la commande au nouveau panier
            $order->setCart($newCart);
            $em->persist($order);

            // Supprimer les produits du panier actuel
            foreach ($cartProducts as $item) {
                $em->remove($item);
            }
            $em->remove($cart);  // Supprimer l'ancien panier

            $em->flush();  // Sauvegarder toutes les modifications

            // Ajouter un message de succès et rediriger vers la confirmation de la commande
            $this->addFlash('success', 'Order placed successfully!');
            return $this->redirectToRoute('aya_order_confirm', [
                'id' => $order->getOrderId()
            ]);
        }

        // Retourner la vue avec le formulaire de commande et le total calculé
        return $this->render('aya_order/aya_order.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart,
            'total' => $finalPrice
        ]);
    }




    #[Route('/aya/order/confirm/{id}', name: 'aya_order_confirm')]
    public function confirm(Order $order = null): Response
    {
        if (!$order) {
            throw $this->createNotFoundException("Order not found.");
        }

        return $this->render('aya_order/ayaconfirmation.html.twig', [
            'order' => $order,
        ]);
    }

    

    #[Route('/api/order/new', name: 'api_order_new', methods: ['POST'])]
    public function apiNewOrder(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        SessionInterface $session,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(x100); // Test user
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        if (!$user) return new JsonResponse(['error' => 'Unauthorized'], 401);

        $cart = $cartRepository->findOneBy(['user' => $user]);

        // 🔁 Si pas de panier, on en crée un
        if (!$cart) {
            $cart = new \App\Entity\Cart();
            $cart->setUser($user);
            $em->persist($cart);
            $em->flush();
        }

        // 🛒 Récupérer les produits du panier
        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

        // 🧾 Vérif champs requis
        if (empty($data['exact_address']) || empty($data['event_date']) || empty($data['payment_method'])) {
            return new JsonResponse(['error' => 'Missing required fields'], 400);
        }

        // 💰 Total
        $total = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }

        // 🧾 Création de la commande
        $order = new Order();
        $order->setUser($user);
        $order->setExactAddress($data['exact_address']);
        $order->setEventDate(new \DateTime($data['event_date']));
        $order->setPaymentMethod($data['payment_method']);
        $order->setOrderedAt(new \DateTime());
        $order->setStatus('PENDING');
        $order->setTotalPrice($total);

        // 👇 Supprimer les CartProducts
        foreach ($cartProducts as $item) {
            $em->remove($item);
        }
        $em->flush();

        // 👇 Supprimer le panier actuel
        $em->remove($cart);
        $em->flush();

        // 🔁 Créer un nouveau panier vide
        $newCart = new \App\Entity\Cart();
        $newCart->setUser($user);
        $em->persist($newCart); // 🔑 persist d'abord
        $em->flush();

        // 📎 Associer à l’ordre après persist
        $order->setCart($newCart);

        // 💾 Enregistrer la commande
        $em->persist($order);
        $em->flush();

        return new JsonResponse([
            'message' => 'Order created successfully',
            'order_id' => $order->getOrderId(),
            'total' => $order->getTotalPrice()
        ], 201);
    }

    public function processOrder(
        Request $request,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'You need to be logged in.');
            return $this->redirectToRoute('app_login');
        }

        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            $this->addFlash('error', 'No cart found.');
            return $this->redirectToRoute('aya_cart');
        }

        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

        // Calcul total
        $total = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }

        // Enregistrer les réductions
        $order = new Order();
        $order->setUser($user);
        $order->setOrderedAt(new \DateTime());
        $order->setTotalPrice($total);

        // Ajouter les réductions
        // Exemple dans la méthode `new` du contrôleur
        $walletUsed = $session->get('wallet_used', 0);
        $giftCardUsed = $session->get('gift_card_amount', 0);
        $pointsUsed = $session->get('points_used', 0);
        $couponDiscount = $session->get('coupon_discount', 0);

        // Calcul du total avec les réductions
        $finalPrice = $total - $walletUsed - $giftCardUsed - $pointsUsed - $couponDiscount;
        $order->setTotalPrice($finalPrice);

        $em->persist($order);
        $em->flush();

        // Nettoyer le panier
        foreach ($cartProducts as $item) {
            $em->remove($item);
        }

        $em->remove($cart);
        $em->flush();

        // Rediriger vers la confirmation
        $this->addFlash('success', 'Your order has been successfully placed.');
        return $this->redirectToRoute('aya_order_confirm', ['id' => $order->getOrderId()]);
    }
}
