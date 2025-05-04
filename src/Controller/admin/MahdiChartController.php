<?php
// src/Controller/admin/MahdiChartController.php
namespace App\Controller\admin;

use App\Repository\ProductRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;
use Psr\Log\LoggerInterface;

class MahdiChartController extends AbstractController
{
    private $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    #[Route('/admin/charts/products', name: 'chart_products')]
    public function productsChart(ProductRepository $productRepo, StockRepository $stockRepository, ChartBuilderInterface $chartBuilder): Response
    {
        // Récupérer tous les produits
        $products = $productRepo->findAll();

        // Chart 1: Products per Category
        $categories = [];
        foreach ($products as $product) {
            $cat = $product->getCategory() ?? 'Unknown';
            $categories[$cat] = ($categories[$cat] ?? 0) + 1;
        }

        $chart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $chart->setData([
            'labels' => array_keys($categories),
            'datasets' => [
                [
                    'label' => 'Products per Category',
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
                'y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Number of Products']],
                'x' => ['title' => ['display' => true, 'text' => 'Category']],
            ],
        ]);

        // Chart 2: Products by Price Range
        $priceRanges = [
            '0-50' => 0,
            '51-100' => 0,
            '101-200' => 0,
            '201-500' => 0,
            '501+' => 0,
        ];
        foreach ($products as $product) {
            $price = $product->getPrice();
            if ($price === null) continue; // Skip products with no price
            if ($price <= 50) $priceRanges['0-50']++;
            elseif ($price <= 100) $priceRanges['51-100']++;
            elseif ($price <= 200) $priceRanges['101-200']++;
            elseif ($price <= 500) $priceRanges['201-500']++;
            else $priceRanges['501+']++;
        }

        $priceChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $priceChart->setData([
            'labels' => array_keys($priceRanges),
            'datasets' => [
                [
                    'label' => 'Products by Price Range',
                    'backgroundColor' => ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF'],
                    'data' => array_values($priceRanges),
                ],
            ],
        ]);
        $priceChart->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ]);

        // Chart 3: Stock Available vs Minimum per Supplier
        $stocks = $stockRepository->findAll();
        $suppliers = [];
        foreach ($stocks as $stock) {
            $supplierName = $stock->getUser() ? $stock->getUser()->getFirstName() : 'Unknown';
            $suppliers[$supplierName]['available'] = ($suppliers[$supplierName]['available'] ?? 0) + ($stock->getAvailableQuantity() ?? 0);
            $suppliers[$supplierName]['minimum'] = ($suppliers[$supplierName]['minimum'] ?? 0) + ($stock->getMinimumQuantity() ?? 0);
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
        $supplierChart->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'scales' => [
                'y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Stock Quantity']],
                'x' => ['title' => ['display' => true, 'text' => 'Supplier']],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
            ],
        ]);

        // Chart 4: Stock Available per Product
        $stockData = [];
        $labels = [];
        foreach ($products as $product) {
            $stock = $product->getStock();
            if ($stock && $stock->getAvailableQuantity() !== null) {
                $labels[] = $product->getName() ?? 'Unnamed Product';
                $stockData[] = $stock->getAvailableQuantity();
            }
        }

        $stockPerProductChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $stockPerProductChart->setData([
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Available Stock',
                    'backgroundColor' => 'rgba(153, 102, 255, 0.5)',
                    'data' => $stockData,
                ],
            ],
        ]);
        $stockPerProductChart->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'scales' => [
                'y' => ['beginAtZero' => true, 'title' => ['display' => true, 'text' => 'Stock Quantity']],
                'x' => ['title' => ['display' => true, 'text' => 'Product']],
            ],
        ]);

        // Debugging: Log the data to confirm it's being generated
        $this->logChartData($chart, $priceChart, $supplierChart, $stockPerProductChart);

        return $this->render('admin/productsChart.html.twig', [
            'chart' => $chart,
            'priceChart' => $priceChart,
            'supplierChart' => $supplierChart,
            'stockPerProductChart' => $stockPerProductChart,
        ]);
    }

    private function logChartData($chart, $priceChart, $supplierChart, $stockPerProductChart): void
    {
        $this->logger->info('Products per Category Chart Data', ['data' => $chart->getData()]);
        $this->logger->info('Products by Price Range Chart Data', ['data' => $priceChart->getData()]);
        $this->logger->info('Supplier Stock Comparison Chart Data', ['data' => $supplierChart->getData()]);
        $this->logger->info('Stock Available per Product Chart Data', ['data' => $stockPerProductChart->getData()]);
    }
}