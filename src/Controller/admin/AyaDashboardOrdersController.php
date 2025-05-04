<?php
namespace App\Controller\admin;

use App\Repository\OrderRepository;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AyaDashboardOrdersController extends AbstractController
{
    private $connection;

    // Constructor to inject the Doctrine DBAL connection
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    #[Route('/admin/aya-orders', name: 'aya_admin_orders')]
    public function index(OrderRepository $orderRepo): Response
    {
        // Total Orders
        $totalOrders = $orderRepo->count([]);

        // Orders by Status
        $ordersConfirmed = $orderRepo->createQueryBuilder('o')
            ->select('COUNT(o.order_id)')
            ->where('o.status = :status')
            ->setParameter('status', 'CONFIRMED')
            ->getQuery()
            ->getSingleScalarResult();

        $ordersPending = $orderRepo->createQueryBuilder('o')
            ->select('COUNT(o.order_id)')
            ->where('o.status = :status')
            ->setParameter('status', 'PENDING')
            ->getQuery()
            ->getSingleScalarResult();

        $ordersCancelled = $orderRepo->createQueryBuilder('o')
            ->select('COUNT(o.order_id)')
            ->where('o.status = :status')
            ->setParameter('status', 'CANCELLED')
            ->getQuery()
            ->getSingleScalarResult();

        // 📈 Income and Expense calculation
        $income = $orderRepo->createQueryBuilder('o')
            ->select('SUM(o.total_price)')
            ->where('o.payment_method != :cash')
            ->setParameter('cash', 'Cash on Delivery')
            ->getQuery()
            ->getSingleScalarResult() ?? 0;

        $expense = $income * 0.3;

        // 📊 Performance = comparing orders between two months
        $stmt = $this->connection->executeQuery("
            SELECT MONTH(o.ordered_at) as month, COUNT(o.order_id) as count
            FROM `order` o
            GROUP BY MONTH(o.ordered_at)
            ORDER BY MONTH(o.ordered_at) ASC
        ");
        $monthlyOrders = $stmt->fetchAllAssociative();

        $ordersPerMonth = array_fill(1, 12, 0);
        foreach ($monthlyOrders as $order) {
            $ordersPerMonth[(int)$order['month']] = (int)$order['count'];
        }

        // Calculate performance based on comparison (e.g., March vs. April)
        $marchOrders = $ordersPerMonth[3] ?? 0;
        $aprilOrders = $ordersPerMonth[4] ?? 0;
        $performance = $marchOrders > 0 ? (($aprilOrders - $marchOrders) / $marchOrders) * 100 : 0;

        // Predicted sales for the next month
        $nextMonthPrediction = $this->predictNextMonthSalesFromPython();


        return $this->render('admin/ayaDashboardOrders.html.twig', [
            'totalOrders' => $totalOrders,
            'ordersConfirmed' => $ordersConfirmed,
            'ordersPending' => $ordersPending,
            'ordersCancelled' => $ordersCancelled,
            'income' => $income,
            'expense' => $expense,
            'performance' => $performance,
            'ordersPerMonth' => $ordersPerMonth,
            'nextMonthPrediction' => $nextMonthPrediction, // Pass predicted sales
        ]);
    }

    private function predictNextMonthSalesFromPython(): string
{
    $projectDir = $this->getParameter('kernel.project_dir'); // racine du projet
    $scriptPath = $projectDir . '/src/ai/ai_model.py';        // chemin absolu

    $process = new Process(['python', $scriptPath]);
    $process->run();

    if (!$process->isSuccessful()) {
        return 'Erreur Python : ' . $process->getErrorOutput();
    }

    return trim($process->getOutput());
}

    
    
}
