<?php

namespace App\Controller\admin;

use App\Repository\OrderRepository;
use League\Csv\Writer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Annotation\Route;

class AyaOrderExportController extends AbstractController
{
    #[Route('/admin/orders/export/csv', name: 'admin_orders_export_csv')]
    public function exportOrders(OrderRepository $orderRepository): StreamedResponse
    {
        $orders = $orderRepository->findAll();
        $totalVentes = 0;

        $response = new StreamedResponse(function () use ($orders, &$totalVentes) {
            echo "\xEF\xBB\xBF"; // BOM UTF-8

            $csv = Writer::createFromString('');
            $csv->setDelimiter(';');

            $csv->insertOne(['Order ID', 'User', 'Status', 'Total Price (DT)', 'Ordered At']);

            foreach ($orders as $order) {
                $price = $order->getTotalPrice() ?? 0;
                $totalVentes += $price;

                $csv->insertOne([
                    $order->getOrderId(),
                    $order->getUser() ? $order->getUser()->getUsername() : 'N/A',
                    ucfirst(strtolower($order->getStatus())),
                    number_format($price, 2, '.', ''),
                    $order->getOrderedAt()?->format('d/m/Y') ?? '',
                ]);
            }

            $csv->insertOne([]);
            $csv->insertOne(['', '', '', 'TOTAL VENTES', number_format($totalVentes, 2, '.', '') . ' DT']);

            echo $csv->toString();
        });

        $response->headers->set('Content-Type', 'application/force-download');
        $response->headers->set('Content-Disposition', 'attachment; filename="admin_orders.csv"');

        return $response;
    }
}
