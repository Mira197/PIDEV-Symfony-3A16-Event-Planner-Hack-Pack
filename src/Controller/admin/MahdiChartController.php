<?php
// src/Controller/admin/ChartController.php
namespace App\Controller\admin;

use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class MahdiChartController extends AbstractController
{
    #[Route('/admin/charts/products', name: 'chart_products')]
    public function productsChart(ProductRepository $productRepo,StockRepository $stockRepository, ChartBuilderInterface $chartBuilder): Response
    {
        // Récupérer tous les produits
        $products = $productRepo->findAll();

        // Grouper par catégorie
        $categories = [];
        foreach ($products as $product) {
            $cat = $product->getCategory() ?? 'Unknown';
            if (!isset($categories[$cat])) {
                $categories[$cat] = 0;
            }
            $categories[$cat]++;
        }

        // Construire le graphique
        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => array_keys($categories),
            'datasets' => [
                [
                    'label' => 'Produits par Catégorie',
                    'backgroundColor' => 'rgba(107, 51, 110, 0.7)',
                    'borderColor' => 'rgba(107, 51, 110, 1)',
                    'borderWidth' => 2,
                    'data' => array_values($categories),
                ],
            ],
        ]);

        $chart->setOptions([
            'maintainAspectRatio' => false,
    'responsive' => true,
            'scales' => [
                'y' => ['beginAtZero' => true],
            ],
        ]);


         // Chart 2 : Nombre de produits selon les plages de prix
    $priceRanges = [
        '0-50' => 0,
        '51-100' => 0,
        '101-200' => 0,
        '201-500' => 0,
        '501+' => 0,
    ];
    foreach ($products as $product) {
        $price = $product->getPrice();
        if ($price <= 50) $priceRanges['0-50']++;
        elseif ($price <= 100) $priceRanges['51-100']++;
        elseif ($price <= 200) $priceRanges['101-200']++;
        elseif ($price <= 500) $priceRanges['201-500']++;
        else $priceRanges['501+']++;
    }

    $priceChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
    $priceChart->setData([
        'labels' => array_keys($priceRanges),
        'datasets' => [[
            'label' => 'Products by Price Range',
            'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
            'data' => array_values($priceRanges),
        ]],
    ]);
   


    // Chart 3 : Stock disponible vs Stock minimum par fournisseur
    $stocks = $stockRepository->findAll();
    $suppliers = [];
    foreach ($stocks as $stock) {
        $supplierName = $stock->getUser() ? $stock->getUser()->getFirstName() : 'Unknown';
        $suppliers[$supplierName]['available'] = ($suppliers[$supplierName]['available'] ?? 0) + $stock->getAvailable_quantity();
        $suppliers[$supplierName]['minimum'] = ($suppliers[$supplierName]['minimum'] ?? 0) + $stock->getMinimum_quantity();
    }

    $supplierChart = $chartBuilder->createChart(Chart::TYPE_BAR);
    $supplierChart->setData([
        'labels' => array_keys($suppliers),
        'datasets' => [
            [
                'label' => 'Available Stock',
                'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                'data' => array_column($suppliers, 'available'),
            ],
            [
                'label' => 'Minimum Stock',
                'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                'data' => array_column($suppliers, 'minimum'),
            ],
        ],
    ]);



    // Chart 4 : Stock disponible par produit
    $stockPerProductChart = $chartBuilder->createChart(Chart::TYPE_BAR);
    $stockPerProductChart->setData([
        'labels' => array_map(fn($product) => $product->getName(), $products),
        'datasets' => [[
            'label' => 'Available Stock',
            'backgroundColor' => 'rgba(153, 102, 255, 0.5)',
            'data' => array_map(fn($product) => $product->getStock() ? $product->getStock()->getAvailable_quantity() : 0, $products),
        ]],
    ]);



        return $this->render('admin/productsChart.html.twig', [
            'chart' => $chart,
            'priceChart' => $priceChart,
            'supplierChart' => $supplierChart,
            'stockPerProductChart' => $stockPerProductChart,
        ]);
    }
}
