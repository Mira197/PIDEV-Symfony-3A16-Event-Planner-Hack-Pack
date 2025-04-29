<?php

namespace App\Controller\admin;

use App\Entity\Publication;
use App\Entity\Comment;
use App\Form\PublicationType;
use App\Form\CommentType;
use App\Repository\CommentRepository;
use App\Repository\PublicationRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Report;
use App\Form\ReportTypeAdmin;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Knp\Component\Pager\PaginatorInterface;

#[Route('/admin/publication')]
class PublicationAdminController extends AbstractController
{
    private $security;

    public function __construct(Security $security)
    {
        $this->security = $security;
    }

    #[Route('/admin/publications', name: 'app_publication_list')]
    public function listPublications(Request $request, PublicationRepository $repo, PaginatorInterface $paginator): Response
    {
        $queryBuilder = $repo->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u')
            ->orderBy('p.publication_date', 'DESC');

        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );

        return $this->render('admin/postAdmin.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/publication/search', name: 'admin_publication_search')]
    public function search(Request $request, PublicationRepository $publicationRepository, PaginatorInterface $paginator): Response
    {
        $term = $request->query->get('q');
        $status = $request->query->get('status', '');
    
        $queryBuilder = $publicationRepository->createQueryBuilder('p')
            ->leftJoin('p.user', 'u')
            ->addSelect('u');
    
        if ($term) {
            $queryBuilder
                ->andWhere('LOWER(p.title) LIKE :term OR LOWER(p.description) LIKE :term')
                ->setParameter('term', '%' . strtolower($term) . '%');
        }
    
        if ($status && in_array($status, ['Appropriate', 'Inappropriate'])) {
            $queryBuilder
                ->andWhere('p.statut = :status') // Changé de "p.status" à "p.statut"
                ->setParameter('status', $status);
        }
    
        $queryBuilder->orderBy('p.publication_date', 'DESC');
    
        $pagination = $paginator->paginate(
            $queryBuilder,
            $request->query->getInt('page', 1),
            5
        );
    
        return $this->render('admin/_publication_rows.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    #[Route('/admin/reports', name: 'app_report_list')]
public function listReports(ReportRepository $repo): Response
{
    $reports = $repo->findAll();

    return $this->render('admin/reportAdmin.html.twig', [
        'reports' => $reports,
    ]);
}

#[Route('/admin/report/search', name: 'admin_report_search')]
public function searchReport(Request $request, ReportRepository $repo): Response
{
    $query = $request->query->get('q', '');
    $status = $request->query->get('status', '');
    $date = $request->query->get('date', '');

    $qb = $repo->createQueryBuilder('r')
        ->leftJoin('r.publication', 'p')
        ->addSelect('p')
        ->where('LOWER(r.reason) LIKE :q OR LOWER(r.description) LIKE :q OR LOWER(p.title) LIKE :q')
        ->setParameter('q', '%' . strtolower($query) . '%');

    if (!empty($status)) {
        $qb->andWhere('r.status = :status')
           ->setParameter('status', $status);
    }

    if (!empty($date)) {
        $startDate = new \DateTime($date);
        $endDate = (clone $startDate)->modify('+1 day');
    
        $qb->andWhere('r.report_date >= :startDate AND r.report_date < :endDate')
           ->setParameter('startDate', $startDate)
           ->setParameter('endDate', $endDate);
    }    

    $qb->orderBy('r.report_date', 'DESC');

    return $this->render('admin/_report_rows.html.twig', [
        'reports' => $qb->getQuery()->getResult(),
    ]);
}

    #[Route('/dashboard', name: 'admin_publication_dashboard')]
    public function forumDashboard(
        PublicationRepository $publicationRepository,
        CommentRepository $commentRepository,
        ReportRepository $reportRepository,
        UserRepository $userRepository
    ): Response {
        $totalPosts = $publicationRepository->count([]);
        $totalComments = $commentRepository->count([]);
        $reportedPosts = $reportRepository->count(['status' => 'Pending']);
        $activeUsers = $userRepository->count([]);

        $totalPublications = $publicationRepository->count([]);
        $sentiments = $publicationRepository->createQueryBuilder('p')
            ->select('p.sentiment, COUNT(p.publication_id) as count')
            ->groupBy('p.sentiment')
            ->getQuery()
            ->getResult();

        $sentimentStats = [
            'positive' => 0,
            'negative' => 0,
            'neutral' => 0,
        ];

        $total = $totalPublications ?: 1;
        foreach ($sentiments as $sentiment) {
            $label = strtoupper($sentiment['sentiment'] ?? 'NEUTRAL');
            if (in_array($label, ['POSITIVE', 'NEGATIVE', 'NEUTRAL'])) {
                $sentimentStats[strtolower($label)] = round(($sentiment['count'] / $total) * 100, 1);
            }
        }

        return $this->render('admin/forumDashboard.html.twig', [
            'totalPosts' => $totalPosts,
            'totalComments' => $totalComments,
            'reportedPosts' => $reportedPosts,
            'activeUsers' => $activeUsers,
            'sentimentStats' => $sentimentStats,
        ]);
    }

    #[Route('/sentiment-stats', name: 'admin_sentiment_stats')]
    public function sentimentStats(Request $request, PublicationRepository $publicationRepository): Response
    {
        $period = $request->query->get('period', 'all');
        $dateFilter = null;

        if ($period === 'month') {
            $dateFilter = (new \DateTimeImmutable())->modify('-1 month');
        } elseif ($period === 'week') {
            $dateFilter = (new \DateTimeImmutable())->modify('-1 week');
        }

        $queryBuilder = $publicationRepository->createQueryBuilder('p')
            ->select('p.sentiment, COUNT(p.publication_id) as count')
            ->groupBy('p.sentiment');

        if ($dateFilter) {
            $queryBuilder->where('p.publication_date >= :date')
                ->setParameter('date', $dateFilter);
        }

        $sentiments = $queryBuilder->getQuery()->getResult();

        $totalQueryBuilder = $publicationRepository->createQueryBuilder('p');
        if ($dateFilter) {
            $totalQueryBuilder->where('p.publication_date >= :date')
                ->setParameter('date', $dateFilter);
        }

        $total = $totalQueryBuilder->select('COUNT(p.publication_id)')->getQuery()->getSingleScalarResult();
        $total = $total ?: 1;

        $sentimentStats = [
            'positive' => 0,
            'negative' => 0,
            'neutral' => 0,
        ];

        foreach ($sentiments as $sentiment) {
            $label = strtoupper($sentiment['sentiment'] ?? 'NEUTRAL');
            if (in_array($label, ['POSITIVE', 'NEGATIVE', 'NEUTRAL'])) {
                $sentimentStats[strtolower($label)] = round(($sentiment['count'] / $total) * 100, 1);
            }
        }

        return $this->json(['sentimentStats' => $sentimentStats]);
    }

    #[Route('/admin/forum-dashboard', name: 'forum_dashboard')]
    public function dashboard(PublicationRepository $publicationRepository): Response
    {
        $publications = $publicationRepository->findAll();

        $sentimentStats = [
            'positive' => 0,
            'negative' => 0,
            'neutral' => 0,
        ];

        foreach ($publications as $publication) {
            $sentiment = $publication->getSentiment();
            if ($sentiment === 'positive') {
                $sentimentStats['positive']++;
            } elseif ($sentiment === 'negative') {
                $sentimentStats['negative']++;
            } else {
                $sentimentStats['neutral']++;
            }
        }

        $totalPublications = count($publications);
        if ($totalPublications > 0) {
            $sentimentStats['positive'] = round(($sentimentStats['positive'] / $totalPublications) * 100, 2);
            $sentimentStats['negative'] = round(($sentimentStats['negative'] / $totalPublications) * 100, 2);
            $sentimentStats['neutral'] = round(($sentimentStats['neutral'] / $totalPublications) * 100, 2);
        }

        return $this->render('admin/forum_dashboard.html.twig', [
            'sentimentStats' => $sentimentStats,
            'totalPosts' => $totalPublications,
            'totalComments' => 0,
            'reportedPosts' => 0,
            'activeUsers' => 0,
        ]);
    }

    #[Route('/admin/forum', name: 'forum_dashboard')]
    public function redirectToForumDashboard(): Response
    {
        return $this->redirectToRoute('admin_publication_dashboard');
    }

    #[Route('/add', name: 'app_publication_new_admin')]
    public function new(
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'You must be logged in.');
            return $this->redirectToRoute('app_login');
        }

        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setUser($user);
            $publication->setPublicationDate(new \DateTimeImmutable());

            $uploadedImage = $request->files->get('image_file');
            if ($uploadedImage) {
                $imageData = file_get_contents($uploadedImage->getPathname());
                $publication->setImage($imageData);
            }

            $em->persist($publication);
            $em->flush();

            $this->addFlash('success', 'Post created successfully.');
            return $this->redirectToRoute('app_publication_list');
        }

        return $this->render('admin/newPublicationAdmin.html.twig', [
            'form' => $form->createView(),
            'username' => $session->get('username'),
        ]);
    }

    #[Route('/edit/{id}', name: 'publication_edit_admin')]
    public function edit(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        $userId = $session->get('user_id');
        $username = $session->get('username');
        $user = $userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'User session not found.');
            return $this->redirectToRoute('app_publication_list');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setUser($user);

            $uploadedImage = $request->files->get('image_file');
            if ($uploadedImage) {
                $imageData = file_get_contents($uploadedImage->getPathname());
                $publication->setImage($imageData);
            }

            $em->flush();
            $this->addFlash('success', 'Publication updated successfully.');
            return $this->redirectToRoute('app_publication_list');
        }

        return $this->render('admin/editPublicationAdmin.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
            'username' => $username,
        ]);
    }

    #[Route('/delete/{id}', name: 'publication_delete', methods: ['POST'])]
    public function delete(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        CsrfTokenManagerInterface $csrfTokenManager
    ): Response {
        $data = json_decode($request->getContent(), true);
        $submittedToken = $data['_token'] ?? '';

        $expectedTokenId = 'delete_publication_' . $publication->getPublicationId();

        if (!$csrfTokenManager->isTokenValid(new CsrfToken($expectedTokenId, $submittedToken))) {
            return $this->json(['success' => false, 'message' => 'Invalid CSRF token.'], 403);
        }

        $em->remove($publication);
        $em->flush();

        return $this->json(['success' => true, 'message' => 'Publication deleted successfully.']);
    }

    #[Route('/bulk-delete', name: 'publication_bulk_delete', methods: ['POST'])]
    public function bulkDelete(Request $request, PublicationRepository $publicationRepository, EntityManagerInterface $em): Response
    {
        $selectedIds = $request->request->all('selected');

        if (!empty($selectedIds)) {
            foreach ($selectedIds as $id) {
                $publication = $publicationRepository->find($id);
                if ($publication) {
                    $em->remove($publication);
                }
            }
            $em->flush();
        }

        $this->addFlash('success', count($selectedIds) . ' publications deleted successfully.');
        return $this->redirectToRoute('app_publication_list');
    }

    #[Route('/report/edit/{id}', name: 'report_edit')]
    public function editReport(Report $report, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(ReportTypeAdmin::class, $report);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'Report status updated.');
            return $this->redirectToRoute('app_report_list');
        }

        return $this->render('admin/reportEdit.html.twig', [
            'form' => $form->createView(),
            'report' => $report,
        ]);
    }

    #[Route('/admin/publication/report/delete/{id}', name: 'report_delete')]
    public function deleteReport(Report $report, EntityManagerInterface $em): Response
    {
        $em->remove($report);
        $em->flush();

        $this->addFlash('success', 'Report deleted successfully.');
        return $this->redirectToRoute('app_report_list');
    }

    #[Route('/comment/delete/{id}', name: 'app_comment_deleteAdmin')]
    public function deleteComment(Comment $comment, EntityManagerInterface $em, Security $security): Response
    {
        $em->remove($comment);
        $em->flush();

        $this->addFlash('success', 'Comment deleted successfully.');
        return $this->redirectToRoute('app_comment_list', ['comment_deleted' => 1]);
    }

    #[Route('/admin/comments', name: 'app_comment_list')]
    public function listComments(CommentRepository $repo): Response
    {
        $comments = $repo->findAll();

        return $this->render('admin/commentAdmin.html.twig', [
            'comments' => $comments,
        ]);
    }
}