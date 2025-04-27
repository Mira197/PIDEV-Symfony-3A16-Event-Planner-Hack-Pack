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
use App\Repository\OrderRepository;
use App\Service\QRCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

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
        // ✅ 1. Récupérer l'utilisateur connecté
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        $walletCredit = $walletTransactionRepository->calculateWalletBalance($user);


        if (!$user) {
            return $this->redirectToRoute('app_login');
        }

        // ✅ 2. Récupérer le panier
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            $this->addFlash('warning', 'No cart found.');
            return $this->redirectToRoute('aya_cart');
        }

        // ✅ 3. Récupérer les produits du panier
        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

        // ✅ 4. Calcul du total
        $total = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }

        $total = number_format($total, 2, '.', '');

        // ✅ 5. Initialiser la commande
        $order = new Order();
        $order->setUser($user);
        $order->setOrderedAt(new \DateTime());

        // ✅ 6. Appliquer les réductions
        $finalPrice = $total;

        // Coupon
        $couponCode = $request->get('coupon_code');
        if ($couponCode) {
            if ($couponCode == 'PROMO10') {
                $couponDiscount = 0.10 * $total;
                $finalPrice -= $couponDiscount;
            }
        }

        // Gift Card
        $giftCardCode = $request->get('gift_card_code');
        $giftCardPin = $request->get('gift_card_pin');
        if ($giftCardCode && $giftCardPin) {
            $giftCard = $giftCardRepository->findOneBy(['code' => $giftCardCode, 'pin' => $giftCardPin]);
            if ($giftCard && !$giftCard->isUsed()) {
                $finalPrice -= $giftCard->getBalance();
                $giftCard->setIsUsed(true);
                $em->flush();
            }
        }

        // Wallet Credit
        $walletAmount = $request->get('wallet_credit');
        if ($walletAmount) {
            $finalPrice -= $walletAmount;
        }

        // Points de fidélité utilisés
        // Points de fidélité utilisés
        $pointsUsed = $request->get('points');
        $pointsMinimum = 100; // 📌 Seuil minimum de points

        if ($pointsUsed) {
            $fidelityPoints = $fidelityPointRepository->findOneBy(['user' => $user]);
            if ($fidelityPoints && $fidelityPoints->getPoints() >= $pointsMinimum) {
                if ($fidelityPoints->getPoints() >= $pointsUsed) {
                    $discountAmount = $pointsUsed * 0.1;
                    $finalPrice -= $discountAmount;
                    $fidelityPoints->setPoints($fidelityPoints->getPoints() - $pointsUsed);
                    $em->persist($fidelityPoints);
                    $em->flush();
                }
            }
        }



        // ✅ 7. Enregistrer le montant final
        $order->setTotalPrice($finalPrice);

        // ✅ 8. Formulaire de commande
        $form = $this->createForm(AyaOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $paymentMode = $request->getSession()->get('payment_mode', 'stripe_only');
            $order->setPaymentMethod($paymentMode); // 🆕 stocker ce que l'utilisateur a choisi

            $finalTotalFromForm = $request->request->get('final_total');

            if ($finalTotalFromForm !== null) {
                $order->setTotalPrice(floatval($finalTotalFromForm));
            }
            if ($order->getTotalPrice() == 0.00) {
                $order->setPaymentMethod('Wallet Only');
            }
            $order->setStatus('PENDING');


            // ✅ 9. Nouveau panier vide
            $newCart = new Cart();
            $newCart->setUser($user);
            $em->persist($newCart);

            $order->setCart($newCart);
            $em->persist($order);

            // ✅ 10. Supprimer les produits et panier actuel
            foreach ($cartProducts as $item) {
                $em->remove($item);
            }
            $em->remove($cart);

            // ✅ 11. Ajouter les POINTS FIDÉLITÉ pour la commande
            $earnedPoints = floor($finalPrice / 10); // Exemple : 1 point pour chaque 10 Dinars
            if ($earnedPoints > 0) {
                $fidelityPoint = new FidelityPoint();
                $fidelityPoint->setUser($user);
                $fidelityPoint->setPoints($earnedPoints);
                $fidelityPoint->setType('earned');
                $fidelityPoint->setDate(new \DateTimeImmutable());
                $fidelityPoint->setReason('Commande validée');
                $em->persist($fidelityPoint);
            }

            // ✅ 12. Tout sauvegarder
            $em->flush();

            // ✅ 13. Message succès
            $this->addFlash('success', "Order placed successfully! You earned {$earnedPoints} points!");

            return $this->redirectToRoute('aya_order_confirm', [
                'id' => $order->getOrderId()
            ]);
        }

        return $this->render('aya_order/aya_order.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart,
            'total' => $total,
            'wallet_credit' => $walletCredit,
        ]);
    }
    #[Route('/api/order/new', name: 'api_aya_order_new', methods: ['POST'])]
    public function apiNew(
        Request $request,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        WalletTransactionRepository $walletTransactionRepository,
        GiftCardRepository $giftCardRepository,
        FidelityPointRepository $fidelityPointRepository,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // ✅ Récupérer user
        $userId = $session->get('user_id') ?? ($data['user_id'] ?? null);
        $user = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // ✅ Récupérer panier
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            return new JsonResponse(['error' => 'No cart found'], 404);
        }

        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

        $total = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }

        $finalPrice = $total;

        // ✅ Appliquer la réduction des points fidélité
        $pointsUsed = $data['points'] ?? 0; // Points envoyés depuis Postman
        if ($pointsUsed) {
            $fidelityPoints = $fidelityPointRepository->findOneBy(['user' => $user]);
            if ($fidelityPoints && $fidelityPoints->getPoints() >= $pointsUsed) {
                $discountAmount = $pointsUsed * 0.1; // ✅ 1 point = 0.1 dinar
                $finalPrice -= $discountAmount;
                $fidelityPoints->setPoints($fidelityPoints->getPoints() - $pointsUsed);
                $em->persist($fidelityPoints);
                $em->flush();
            }
        }

        // ✅ Créer la commande
        $order = new Order();
        $order->setUser($user);
        $order->setExactAddress($data['exact_address']);
        $order->setEventDate(new \DateTime($data['event_date']));
        $order->setPaymentMethod($data['payment_method']);
        $order->setOrderedAt(new \DateTime());
        $order->setTotalPrice($finalPrice);
        $order->setStatus('PENDING');

        // ✅ Nouveau panier vide
        $newCart = new Cart();
        $newCart->setUser($user);
        $em->persist($newCart);

        $order->setCart($newCart);
        $em->persist($order);

        // ✅ Supprimer ancien panier
        foreach ($cartProducts as $item) {
            $em->remove($item);
        }
        $em->remove($cart);

        // ✅ Ajouter points fidélité EARNED
        $earnedPoints = floor($finalPrice / 10);
        if ($earnedPoints > 0) {
            $fidelityPoint = new FidelityPoint();
            $fidelityPoint->setUser($user);
            $fidelityPoint->setPoints($earnedPoints);
            $fidelityPoint->setType('earned');
            $fidelityPoint->setDate(new \DateTimeImmutable());
            $fidelityPoint->setReason('Commande API');
            $em->persist($fidelityPoint);
        }

        $em->flush();

        return new JsonResponse([
            'message' => 'Order created successfully via API',
            'order_id' => $order->getOrderId(),
            'total_price' => $order->getTotalPrice(),
            'points_used' => $pointsUsed,
            'earned_points' => $earnedPoints,
        ], 201);
    }






    // #[Route('/aya/order/confirm/{id}', name: 'aya_order_confirm')]
    // public function confirm(Order $order = null): Response
    // {
    //     if (!$order) {
    //         throw $this->createNotFoundException("Order not found.");
    //     }

    //     return $this->render('aya_order/ayaconfirmation.html.twig', [
    //         'order' => $order,
    //     ]);
    // }

    #[Route('/aya/order/confirm/{id}', name: 'aya_order_confirm')]
    public function confirm(int $id, OrderRepository $orderRepository): Response
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order not found.');
        }

        return $this->render('aya_order/ayaconfirmation.html.twig', [
            'order' => $order, // <-- ✅ On envoie l'objet complet
        ]);
    }




    #[Route('/api/order/new', name: 'api_order_new', methods: ['POST'])]
    public function apiNewOrder(
        Request $request,
        EntityManagerInterface $em,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        SessionInterface $session,
        WalletTransactionRepository $walletTransactionRepository,
        GiftCardRepository $giftCardRepository,
        FidelityPointRepository $fidelityPointRepository,
        UserRepository $userRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // ✅ Récupérer user
        $userId = $session->get('user_id') ?? ($data['user_id'] ?? null);
        $user = $userRepository->find($userId);
        if (!$user) {
            return new JsonResponse(['error' => 'Unauthorized'], 401);
        }

        // ✅ Récupérer panier
        $cart = $cartRepository->findOneBy(['user' => $user]);
        if (!$cart) {
            return new JsonResponse(['error' => 'No cart found'], 404);
        }

        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

        $total = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }

        $finalPrice = $total;

        // ✅ Appliquer la réduction des points fidélité si atteinte du minimum
        $pointsMinimum = 100; // 💬 Seuil minimum pour utiliser les points
        $pointsUsed = $data['points'] ?? 0; // Points envoyés depuis Postman

        if ($pointsUsed) {
            $fidelityPoints = $fidelityPointRepository->findOneBy(['user' => $user]);
            if ($fidelityPoints) {
                if ($fidelityPoints->getPoints() >= $pointsMinimum) {
                    if ($fidelityPoints->getPoints() >= $pointsUsed) {
                        $discountAmount = $pointsUsed * 0.1; // 1 point = 0.1 dinar
                        $finalPrice -= $discountAmount;
                        $fidelityPoints->setPoints($fidelityPoints->getPoints() - $pointsUsed);
                        $em->persist($fidelityPoints);
                        $em->flush();
                    }
                }
            }
        }

        // ✅ Créer la commande
        $order = new Order();
        $order->setUser($user);
        $order->setExactAddress($data['exact_address']);
        $order->setEventDate(new \DateTime($data['event_date']));
        $order->setPaymentMethod($data['payment_method']);
        $order->setOrderedAt(new \DateTime());
        $order->setTotalPrice($finalPrice);
        $order->setStatus('PENDING');

        // ✅ Nouveau panier vide
        $newCart = new Cart();
        $newCart->setUser($user);
        $em->persist($newCart);

        $order->setCart($newCart);
        $em->persist($order);

        // ✅ Supprimer ancien panier
        foreach ($cartProducts as $item) {
            $em->remove($item);
        }
        $em->remove($cart);

        // ✅ Ajouter les points fidélité EARNED
        $earnedPoints = floor($finalPrice / 10);
        if ($earnedPoints > 0) {
            $fidelityPointEarned = new FidelityPoint();
            $fidelityPointEarned->setUser($user);
            $fidelityPointEarned->setPoints($earnedPoints);
            $fidelityPointEarned->setType('earned');
            $fidelityPointEarned->setDate(new \DateTimeImmutable());
            $fidelityPointEarned->setReason('Commande API');
            $em->persist($fidelityPointEarned);
        }

        $em->flush();

        return new JsonResponse([
            'message' => 'Order created successfully via API',
            'order_id' => $order->getOrderId(),
            'total_price' => $order->getTotalPrice(),
            'points_used' => $pointsUsed,
            'earned_points' => $earnedPoints,
        ], 201);
    }
}
