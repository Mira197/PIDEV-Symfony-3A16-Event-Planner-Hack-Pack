<?php

namespace App\Controller\admin;

use App\Entity\Order;
use App\Repository\OrderRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Knp\Component\Pager\PaginatorInterface;
#[Route('/admin/orders')]
class AyaAdminOrderController extends AbstractController
{
    #[Route('/all', name: 'admin_orders')]
public function index(Request $request, OrderRepository $orderRepository, PaginatorInterface $paginator): Response
{
    $query = $orderRepository->createQueryBuilder('o')
        ->join('o.user', 'u')->addSelect('u')
        ->orderBy('o.ordered_at', 'DESC')
        ->getQuery();

    $orders = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        6
    );

    return $this->render('admin/aya_orders_admin.html.twig', [
        'orders' => $orders,
    ]);
}
    #[Route('/update-status/{id}', name: 'admin_order_update_status', methods: ['POST'])]
    public function updateStatus(Order $order, Request $request, EntityManagerInterface $em): JsonResponse
    {
        $newStatus = $request->request->get('status');

        if ($newStatus) {
            $order->setStatus($newStatus);
            $em->flush();

            return new JsonResponse(['success' => true]);
        }

        return new JsonResponse(['success' => false, 'message' => 'Invalid status']);
    }

    #[Route('/update-field/{id}', name: 'admin_order_update_field', methods: ['POST'])]
    public function updateField(Request $request, Order $order, EntityManagerInterface $em, ValidatorInterface $validator): JsonResponse
    {
        // Accepter à la fois JSON et form-data
        if ($request->getContentType() === 'json' || $request->headers->get('Content-Type') === 'application/json') {
            $data = json_decode($request->getContent(), true);
        } else {
            $data = $request->request->all();
        }

        if (!isset($data['field']) || !isset($data['value'])) {
            return new JsonResponse([
                'success' => false,
                'message' => 'Missing required fields: field or value'
            ], 400);
        }

        $field = $data['field'];
        $value = $data['value'];

        try {
            if ($field === 'orderedAt') {
                $newDate = new \DateTime($value);
                $order->setOrderedAt($newDate);
            }

            // Validation via le Validator
            $errors = $validator->validate($order);
            if (count($errors) > 0) {
                $errorMessages = [];
                foreach ($errors as $error) {
                    $errorMessages[] = $error->getMessage();
                }

                return new JsonResponse([
                    'success' => false,
                    'messages' => $errorMessages
                ], 400);
            }

            $em->flush();
            return new JsonResponse(['success' => true]);
        } catch (\Exception $e) {
            return new JsonResponse([
                'success' => false,
                'message' => $e->getMessage()
            ], 400);
        }
    }





    #[Route('/delete/{id}', name: 'admin_order_delete', methods: ['DELETE'])]
    public function deleteOrder($id, OrderRepository $orderRepository, EntityManagerInterface $em): JsonResponse
    {
        $order = $orderRepository->find($id);

        if (!$order) {
            return new JsonResponse(['success' => false, 'message' => 'Order not found.'], 404);
        }

        if ($order->getStatus() === 'DELIVERED') {
            return new JsonResponse(['success' => false, 'message' => 'You cannot delete an order that has already been delivered.'], 400);
        }

        $em->remove($order);
        $em->flush();

        return new JsonResponse(['success' => true]);
    }




    #[Route('/delete-all', name: 'admin_order_delete_all', methods: ['POST'])]
    public function deleteAll(EntityManagerInterface $em, OrderRepository $orderRepository): JsonResponse
    {
        $orders = $orderRepository->findAll();
        foreach ($orders as $order) {
            $em->remove($order);
        }
        $em->flush();

        return new JsonResponse(['success' => true]);
    }
    #[Route('/view/{id}', name: 'admin_order_view')]
    public function viewOrder($id, OrderRepository $orderRepo): Response
    {
        $order = $orderRepo->find($id);
        if (!$order) {
            throw $this->createNotFoundException("Order not found");
        }

        return $this->render('admin/order/view.html.twig', [
            'order' => $order,
        ]);
    }
    #[Route('/search', name: 'admin_orders_search', methods: ['GET'])]
    public function search(Request $request, OrderRepository $orderRepository): JsonResponse
    {
        $query = strtolower($request->query->get('q', ''));

        $qb = $orderRepository->createQueryBuilder('o')
            ->join('o.user', 'u')
            ->addSelect('u');

        if (!empty($query)) {
            $qb->andWhere(
                $qb->expr()->orX(
                    'LOWER(o.status) LIKE :q',
                    'LOWER(u.username) LIKE :q',
                    'o.total_price LIKE :q',
                    $qb->expr()->like('o.ordered_at', ':q') // Changé ici
                )
            )->setParameter('q', '%' . $query . '%');
        }

        $orders = $qb->getQuery()->getResult();

        $results = [];

        foreach ($orders as $order) {
            $results[] = [
                'id' => $order->getOrderId(),
                'username' => $order->getUser()->getUsername(),
                'status' => $order->getStatus(),
                'totalPrice' => $order->getTotalPrice(),
                'orderedAt' => $order->getOrderedAt()->format('Y-m-d'),
            ];
        }

        return new JsonResponse($results);
    }
}
