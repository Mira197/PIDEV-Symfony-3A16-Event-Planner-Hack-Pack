<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class AyaDashboardOrdersController extends AbstractController
{
    #[Route('/admin/aya-orders', name: 'aya_admin_orders')]
    public function index(
        ChartBuilderInterface $chartBuilder
    ): Response {
        // ✅ Données statiques pour l'exemple
        $months = ['January', 'February', 'March', 'April', 'May', 'June'];
        $orderCounts = [15, 30, 20, 45, 25, 40];
        $revenues = [1500, 3000, 2000, 4500, 2500, 4000];

        // ✅ Chart 3 : Line Chart pour Orders
        $chart3 = $chartBuilder->createChart(Chart::TYPE_LINE);
        $chart3->setData([
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Orders per Month',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'borderWidth' => 2,
                    'tension' => 0.4, // jolie courbure de la ligne
                    'data' => $orderCounts,
                ],
            ],
        ]);
        $chart3->setOptions([
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ]);

        // ✅ Chart 4 : Bar Chart pour Revenue
        $chart4 = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart4->setData([
            'labels' => $months,
            'datasets' => [
                [
                    'label' => 'Revenue ($)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'borderWidth' => 1,
                    'data' => $revenues,
                ],
            ],
        ]);
        $chart4->setOptions([
            'responsive' => true,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
        ]);

        // ✅ Rendu de la vue
        return $this->render('admin/AyaDashboardOrders.html.twig', [
            'chart3' => $chart3,
            'chart4' => $chart4,
        ]);
    }
}
