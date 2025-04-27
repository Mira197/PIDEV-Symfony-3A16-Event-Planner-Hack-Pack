<?php
// src/Controller/AyaOrderController.php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Form\AyaOrderType;
use App\Entity\Cart;
use App\Repository\CartRepository;
use App\Repository\CartProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class AyaOrderController extends AbstractController
{
    #[Route('/aya/order/new', name: 'aya_order_new')]
    public function new(
        Request $request,
        CartRepository $cartRepository,
        CartProductRepository $cartProductRepository,
        EntityManagerInterface $em
    ): Response {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(49);
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            $this->addFlash('warning', 'No cart found.');
            return $this->redirectToRoute('aya_cart');
        }

        $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);

        $order = new Order();
        $order->setUser($user); // 🔴 obligatoire AVANT validation
        $order->setOrderedAt(new \DateTime()); // 🔴 obligatoire AVANT validation

        // 💰 calcul total obligatoire AVANT validation
        $total = 0;
        foreach ($cartProducts as $item) {
            $total += $item->getTotalPrice();
        }
        $order->setTotalPrice($total); // 🔴 AVANT validation

        $form = $this->createForm(AyaOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Définis les valeurs obligatoires AVANT la validation
            $order->setUser($user);
            $order->setOrderedAt(new \DateTime()); // Commande maintenant
            $total = 0;
            foreach ($cartProducts as $item) {
                $total += $item->getTotalPrice();
            }
            $order->setTotalPrice($total); // Calcul total du panier

            if ($form->isValid()) {
                $order->setStatus('PENDING');

                // Nouveau panier vide pour l'utilisateur
                $newCart = new Cart();
                $newCart->setUser($user);
                $em->persist($newCart);

                $order->setCart($newCart);
                $em->persist($order);

                // Nettoyage du panier actuel
                foreach ($cartProducts as $item) {
                    $em->remove($item);
                }
                $em->remove($cart);

                $em->flush();

                $this->addFlash('success', 'Order placed successfully!');
                return $this->redirectToRoute('aya_order_confirm', [
                    'id' => $order->getOrderId()
                ]);
            }
        }


        return $this->render('aya_order/aya_order.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart
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
        CartProductRepository $cartProductRepository
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        $user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

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
}
