<?php

namespace App\Controller\admin;

use App\Entity\Publication;
use App\Form\PublicationType;
use App\Repository\CommentRepository;
use App\Repository\PublicationRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Report;
use App\Form\ReportTypeAdmin;


#[Route('/admin/publication')]
class PublicationAdminController extends AbstractController
{
    #[Route('/dashboard', name: 'admin_publication_dashboard')]
    public function forumDashboard(
        PublicationRepository $publicationRepository,
        CommentRepository $commentRepository,
        ReportRepository $reportRepository,
        UserRepository $userRepository
    ): Response {
        $publications = $publicationRepository->findAll();
        $reports = $reportRepository->findAll();
        $totalPosts = count($publications);
        $totalComments = $commentRepository->count([]);
        $reportedPosts = $reportRepository->count(['status' => 'Pending']);
        $activeUsers = $userRepository->count([]);

        return $this->render('admin/forumDashboard.html.twig', [
            'publications' => $publications,
            'reports' => $reports,
            'totalPosts' => $totalPosts,
            'totalComments' => $totalComments,
            'reportedPosts' => $reportedPosts,
            'activeUsers' => $activeUsers
        ]);
    }

   #[Route('/admin/forum', name: 'forum_dashboard')]

    public function redirectToForumDashboard(): Response
    {
        return $this->redirectToRoute('admin_publication_dashboard');
    }

    #[Route('/edit/{id}', name: 'publication_edit')]
    public function edit(Publication $publication, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            return $this->redirectToRoute('admin_publication_dashboard');
        }

        return $this->render('editPublication.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/delete/{id}', name: 'publication_delete')]
    public function delete(Publication $publication, EntityManagerInterface $em): Response
    {
        $em->remove($publication);
        $em->flush();

        return $this->redirectToRoute('admin_publication_dashboard');
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
        return $this->redirectToRoute('admin_publication_dashboard');
    }

    #[Route('/report/edit/{id}', name: 'report_edit')]
public function editReport(Report $report, Request $request, EntityManagerInterface $em): Response
{
    $form = $this->createForm(ReportTypeAdmin::class, $report);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Report status updated.');
        return $this->redirectToRoute('admin_publication_dashboard');
    }

    return $this->render('admin/reportEdit.html.twig', [
        'form' => $form->createView(),
        'report' => $report,
    ]);
}

#[Route('/report/delete/{id}', name: 'report_delete')]
public function deleteReport(Report $report, EntityManagerInterface $em): Response
{
    $em->remove($report);
    $em->flush();

    $this->addFlash('success', 'Report deleted successfully.');
    return $this->redirectToRoute('admin_publication_dashboard');
}


    
}
