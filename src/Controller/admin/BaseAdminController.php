<?php

namespace App\Controller\admin;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

// ✅ Utilisation des bons Repositories
use App\Repository\PublicationRepository;
use App\Repository\CommentRepository;
use App\Repository\UserRepository;
use App\Repository\ReportRepository;

class BaseAdminController extends AbstractController
{
    #[Route('/admin', name: 'admin_dashboard')]
    public function index(): Response
    {
        return $this->render('admin/baseAdmin.html.twig');
    }

    #[Route('/admin/forum', name: 'forum_dashboard')]
    public function forumDashboard(
        PublicationRepository $postRepo, // ← anciennement PostRepository
        CommentRepository $commentRepo,
        UserRepository $userRepo,
        ReportRepository $reportRepo
    ): Response {
        return $this->render('admin/forumDashboard.html.twig', [
            'totalPosts' => $postRepo->count([]),
            'totalComments' => $commentRepo->count([]),
           // 'activeUsers' => $userRepo->count(['isActive' => true]),
            'reportedPosts' => $reportRepo->count(['status' => 'Pending']),
        ]);
    }
}
