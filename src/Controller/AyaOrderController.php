<?php
// src/Controller/AyaOrderController.php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Controller\AyaOrderType;
use App\Repository\CartRepository;
use App\Repository\CartProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
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
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $cart = $cartRepository->findOneBy(['user' => $user]);

        if (!$cart) {
            $this->addFlash('warning', 'No cart found.');
            return $this->redirectToRoute('aya_order_confirmation');
        }

        $order = new Order();
        $order->setEventDate(new \DateTime());
        $form = $this->createForm(AyaOrderType::class, $order);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $order->setUser($user);
            $order->setCart($cart);
            $order->setOrderedAt(new \DateTime());
            $order->setStatus('Pending');

            // Total calculation
            $total = 0;
            $cartProducts = $cartProductRepository->findBy(['cart' => $cart]);
            foreach ($cartProducts as $item) {
                $total += $item->getTotalPrice();
            }
            $order->setTotalPrice($total);

            $em->persist($order);
            $em->flush();

            // 🧹 Vider le panier
            foreach ($cartProducts as $item) {
                $em->remove($item);
            }
            $em->flush();

            $this->addFlash('success', 'Order placed successfully!');

            return $this->redirectToRoute('aya_order_confirm', [
                'id' => $order->getOrderId()
            ]);
        }


        return $this->render('aya_order/aya_order.html.twig', [
            'form' => $form->createView(),
            'cart' => $cart
        ]);
    }
    #[Route('/aya/order/confirm/{id}', name: 'aya_order_confirm')]
    public function confirm(Order $order): Response
    {
        return $this->render('aya_order/ayaconfirmation.html.twig', [
            'order' => $order,
        ]);
    }
}
