<?php
// src/Controller/admin/ForumChartController.php
namespace App\Controller\admin;

use App\Repository\PublicationRepository;
use App\Repository\ReportRepository;
use App\Repository\CommentRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\UX\Chartjs\Builder\ChartBuilderInterface;
use Symfony\UX\Chartjs\Model\Chart;

class ForumChartController extends AbstractController
{
    #[Route('/admin/charts/forum', name: 'forum_charts')]
    public function charts(
        PublicationRepository $publicationRepo,
        ReportRepository $reportRepo,
        CommentRepository $commentRepo,
        ChartBuilderInterface $chartBuilder
    ): Response {
        // Fetch all data
        $publications = $publicationRepo->findAll();
        $reports = $reportRepo->findAll();
        $comments = $commentRepo->findAll();

        // Debug: Log the counts of fetched data
       

        // 1. Chart - Reports by Status
        $statusCounts = ['Pending' => 0, 'Verified' => 0, 'Rejected' => 0];
        $reportStatuses = []; // For debugging
        foreach ($reports as $report) {
            $status = $report->getStatus();
            $reportStatuses[] = $status; // Collect statuses for debugging
            if (isset($statusCounts[$status])) {
                $statusCounts[$status]++;
            }
        }
        // Debug: Log the status counts and actual statuses
       
        $statusChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $statusChart->setData([
            'labels' => array_keys($statusCounts),
            'datasets' => [[
                'label' => 'Reports by Status',
                'backgroundColor' => 'rgba(107, 51, 110, 0.7)',
                'borderColor' => 'rgba(107, 51, 110, 1)',
                'borderWidth' => 2,
                'data' => array_values($statusCounts),
            ]],
        ]);
        $statusChart->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
            'scales' => ['y' => ['beginAtZero' => true]],
        ]);

        // 2. Chart - Sentiment Distribution
        $sentiments = ['Positive' => 0, 'Negative' => 0, 'Neutral' => 0, 'Unknown' => 0];
        $rawSentiments = []; // For debugging
        foreach ($publications as $p) {
            $rawSentiment = $p->getSentiment();
            $rawSentiments[] = $rawSentiment; // Collect raw sentiments for debugging
            $normalized = ucfirst(strtolower($rawSentiment ?? 'Unknown'));
            if (!array_key_exists($normalized, $sentiments)) {
                $normalized = 'Unknown';
            }
            $sentiments[$normalized]++;
        }
        // Debug: Log the sentiment counts and actual sentiments
       
        $sentimentChart = $chartBuilder->createChart(Chart::TYPE_DOUGHNUT);
        $sentimentChart->setData([
            'labels' => array_keys($sentiments),
            'datasets' => [[
                'label' => 'Sentiments',
                'backgroundColor' => ['#28a745', '#dc3545', '#6c757d', '#C9CBCF'],
                'data' => array_values($sentiments),
            ]],
        ]);
        $sentimentChart->setOptions([
            'maintainAspectRatio' => false,
            'responsive' => true,
        ]);

        // 3. Chart - Activity Over Time
        $dates = [];
        foreach ($publications as $p) {
            $date = $p->getPublicationDate()?->format('Y-m-d') ?? 'Unknown';
            $dates[$date]['pub'] = ($dates[$date]['pub'] ?? 0) + 1;
        }
        foreach ($comments as $c) {
            $date = $c->getCommentDate()?->format('Y-m-d') ?? 'Unknown';
            $dates[$date]['com'] = ($dates[$date]['com'] ?? 0) + 1;
        }
        ksort($dates);

        // Debug: Log the dates and counts

        $activityChart = $chartBuilder->createChart(Chart::TYPE_LINE);
        $activityChart->setData([
            'labels' => array_keys($dates),
            'datasets' => [
                [
                    'label' => 'Publications',
                    'data' => array_map(fn($d) => $d['pub'] ?? 0, $dates),
                    'borderColor' => 'rgba(54, 162, 235, 1)',
                    'backgroundColor' => 'rgba(54, 162, 235, 0.2)',
                    'fill' => true,
                ],
                [
                    'label' => 'Comments',
                    'data' => array_map(fn($d) => $d['com'] ?? 0, $dates),
                    'borderColor' => 'rgba(255, 99, 132, 1)',
                    'backgroundColor' => 'rgba(255, 99, 132, 0.2)',
                    'fill' => true,
                ]
            ],
        ]);
        $activityChart->setOptions([
            'responsive' => true,
            'scales' => ['y' => ['beginAtZero' => true]]
        ]);

        // 4. Chart - Most Reported Publications
        $topReports = [];
        $reportCounts = []; // For debugging
        foreach ($publications as $p) {
            $reportCount = $p->getReportCount() ?? 0;
            $reportCounts[$p->getTitle()] = $reportCount; // Collect report counts for debugging
            if ($reportCount > 0) {
                $topReports[$p->getTitle()] = $reportCount;
            }
        }
        arsort($topReports);
        $topReports = array_slice($topReports, 0, 10);

        // Debug: Log the top reports and all report counts
      
        $topReportedChart = $chartBuilder->createChart(Chart::TYPE_BAR);
        $topReportedChart->setData([
            'labels' => array_keys($topReports),
            'datasets' => [[
                'label' => 'Reports per Publication',
                'backgroundColor' => 'rgba(153, 102, 255, 0.5)',
                'borderColor' => 'rgba(153, 102, 255, 1)',
                'data' => array_values($topReports),
            ]],
        ]);
        $topReportedChart->setOptions([
            'responsive' => true,
            'scales' => ['y' => ['beginAtZero' => true]]
        ]);

        return $this->render('admin/forumCharts.html.twig', [
            'statusChart' => $statusChart,
            'sentimentChart' => $sentimentChart,
            'activityChart' => $activityChart,
            'topReportedChart' => $topReportedChart,
        ]);
    }
}