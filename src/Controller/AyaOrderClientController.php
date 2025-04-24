<?php
// src/Controller/AyaOrderClientController.php
namespace App\Controller;

use App\Entity\Order;
use App\Entity\User;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\UserRepository;

class AyaOrderClientController extends AbstractController
{
    #[Route('/client/orders', name: 'client_orders', methods: ['GET'])]
    public function index(OrderRepository $orderRepository, EntityManagerInterface $em, Request $request,SessionInterface $session,
    UserRepository $userRepository): Response
    {
        //$user = $em->getRepository(User::class)->find(x100); // simulate user
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        if (!$user) return $this->redirectToRoute('app_login');

        $filter = $request->query->get('status');
        $orders = $filter
            ? $orderRepository->findBy(['user' => $user, 'status' => strtoupper($filter)])
            : $orderRepository->findBy(['user' => $user]);

        // JSON response for AJAX or Postman
        if ($request->isXmlHttpRequest() || $request->getRequestFormat() === 'json') {
            $data = [];

            foreach ($orders as $order) {
                $data[] = [
                    'order_id' => $order->getOrderId(),
                    'event_date' => $order->getEventDate()?->format('Y-m-d H:i'),
                    'address' => $order->getExactAddress(),
                    'status' => $order->getStatus(),
                    'ordered_at' => $order->getOrderedAt()?->format('Y-m-d H:i'),
                    'total' => $order->getTotalPrice(),
                ];
            }

            return new JsonResponse($data);
        }

        return $this->render('aya_order/ayaclientorders.html.twig', [
            'orders' => $orders,
            'currentFilter' => $filter,
        ]);
    }

    #[Route('/client/orders/table', name: 'client_orders_table', methods: ['GET'])]
    public function ordersTable(OrderRepository $orderRepository, EntityManagerInterface $em, Request $request,SessionInterface $session,
    UserRepository $userRepository): Response
    {
        //$user = $em->getRepository(User::class)->find(x100); // Test user
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        if (!$user) return $this->redirectToRoute('app_login');

        $filter = $request->query->get('status');
        $orders = $filter
            ? $orderRepository->findBy(['user' => $user, 'status' => $filter])
            : $orderRepository->findBy(['user' => $user]);

        return $this->render('aya_order/ayaclientorders.html.twig', [
            'orders' => $orders,
            'currentFilter' => $filter,
        ]);
    }
    #[Route('/client/order/cancel/{id}', name: 'cancel_order', methods: ['POST'])]
    public function cancelOrder(int $id, EntityManagerInterface $em,SessionInterface $session,
    UserRepository $userRepository): Response
    {
        //$user = $em->getRepository(User::class)->find(x100); // simulate user
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        if (!$user) return $this->redirectToRoute('app_login');
        $order = $em->getRepository(Order::class)->find($id);
        

        if (!$order || $order->getUser()?->getIdUser() !== $user->getIdUser()) {
            throw $this->createNotFoundException('Order not found or access denied');
        }

        if (strtoupper($order->getStatus()) !== 'PENDING') {
            $this->addFlash('danger', 'Only pending orders can be canceled.');
            return $this->redirectToRoute('client_orders');
        }

        $order->setStatus('CANCELLED');
        $em->flush();

        $this->addFlash('success', 'Order canceled successfully!');
        return $this->redirectToRoute('client_orders');
    }
    #[Route('/client/orders/search', name: 'client_orders_search', methods: ['GET'])]
    public function search(Request $request, OrderRepository $orderRepository, EntityManagerInterface $em,SessionInterface $session,
    UserRepository $userRepository): JsonResponse
    {
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(x100);
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        if (!$user) return new JsonResponse([], 401);
        $query = strtolower($request->query->get('q', ''));

        $qb = $orderRepository->createQueryBuilder('o')
            ->andWhere('o.user = :user')
            ->setParameter('user', $user);

        if (!empty($query)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(o.status) LIKE :q',
                    'LOWER(o.payment_method) LIKE :q',
                    'LOWER(o.exact_address) LIKE :q',
                    'o.total_price LIKE :q',
                    "DATE_FORMAT(o.ordered_at, '%Y-%m-%d %H:%i') LIKE :q",
                    "DATE_FORMAT(o.event_date, '%Y-%m-%d %H:%i') LIKE :q"
                )
            )
                ->setParameter('q', '%' . $query . '%');
        }

        $orders = $qb->getQuery()->getResult();

        $results = [];

        foreach ($orders as $order) {
            $results[] = [
                'id' => $order->getOrderId(),
                'eventDate' => $order->getEventDate()?->format('Y-m-d H:i'),
                'exactAddress' => $order->getExactAddress(),
                'status' => $order->getStatus(),
                'paymentMethod' => $order->getPaymentMethod(),
                'orderedAt' => $order->getOrderedAt()?->format('Y-m-d H:i'),
                'totalPrice' => $order->getTotalPrice(),
            ];
        }

        return new JsonResponse($results);
    }
}
