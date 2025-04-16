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
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Report;
use App\Form\ReportTypeAdmin;
use Symfony\Component\Form\FormError;
use Symfony\Component\Security\Core\Security;


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
    // Route pour afficher la page (optionnelle si popup uniquement)
/*#[Route('/add', name: 'app_publication_new_admin', methods: ['GET'])]
public function showForm(): Response
{
    // Si tu veux afficher la page avec form classique un jour
    return $this->render('admin/newPublicationAdmin.html.twig');
}
    // Affichage éventuel du formulaire (optionnel, si jamais tu veux accéder à /admin/publication/add manuellement)
#[Route('/admin/publication/add', name: 'app_publication_new_admin', methods: ['GET'])]
public function showFormP(): Response
{
    return $this->render('admin/newPublicationAdmin.html.twig');
}

// 🔥 Route utilisée pour la soumission AJAX du popup
#[Route('/admin/publication/ajax-add', name: 'app_publication_ajax_add', methods: ['POST'])]
public function ajaxAdd(Request $request, EntityManagerInterface $em, UserRepository $userRepository): JsonResponse
{
    $username = $request->request->get('username');
    $title = $request->request->get('title');
    $description = $request->request->get('description');
    $imageFile = $request->files->get('image_file');
    //$user = $this->getUser();
    $user = $userRepository->find(44); // utilisateur simulé
    if (!$user || $username !== $user->getUsername()) {
        return new JsonResponse(['success' => false, 'message' => '❌ Username does not match test account.'], 400);
    }

    $publication = new Publication();
    $publication->setUser($user);
    $publication->setTitle($title);
    $publication->setDescription($description);
    $publication->setPublicationDate(new \DateTimeImmutable());

    if ($imageFile) {
        $imageData = file_get_contents($imageFile->getPathname());
        $publication->setImage($imageData);
    }

    $em->persist($publication);
    $em->flush();

    return new JsonResponse(['success' => true, 'message' => '✅ Post created successfully.']);
}*/
#[Route('/add', name: 'app_publication_new_admin')]
public function new(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
{
    $publication = new Publication();
    $form = $this->createForm(PublicationType::class, $publication);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $inputUsername = $form->get('username')->getData();

        // 👇 Utilisateur de test avec ID 44
       $user = $userRepository->find(44);
        //$user = $this->getUser();
        if (!$user || $inputUsername !== $user->getUsername()) {
            $form->get('username')->addError(
                new FormError('The username does not match your test account.')
            );
        } else {
            $publication->setUser($user);
            $publication->setPublicationDate(new \DateTimeImmutable());

            $uploadedImage = $request->files->get('image_file');
            if ($uploadedImage) {
                $imageData = file_get_contents($uploadedImage->getPathname());
                $publication->setImage($imageData);
            }

            $em->persist($publication);
            $em->flush();

            return $this->redirectToRoute('app_publication_list');
        }
    }

    return $this->render('admin/newPublicationAdmin.html.twig', [
        'form' => $form->createView(),
    ]);
}


    


    
    #[Route('/edit/{id}', name: 'publication_edit_admin')]
public function edit(Publication $publication, Request $request, EntityManagerInterface $em, Security $security, UserRepository $userRepository): Response
{
    $form = $this->createForm(PublicationType::class, $publication);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $inputUsername = $form->get('username')->getData();
        //$user = $security->getUser();
        $user = $userRepository->find(44); // Utilisateur de test ou temporaire

        if (!$user || $inputUsername !== $user->getUsername()) {
            $form->get('username')->addError(
                new FormError('The username does not match your account.')
            );
        } else {
            $publication->setUser($user);

            // 📷 Gestion du changement d'image
            $uploadedImage = $request->files->get('image_file');
            if ($uploadedImage) {
                $imageData = file_get_contents($uploadedImage->getPathname());
                $publication->setImage($imageData);
            }

            $em->flush();

            $this->addFlash('success', 'Publication updated successfully.');
            return $this->redirectToRoute('app_publication_list');
        }
    }

    return $this->render('admin/editPublicationAdmin.html.twig', [
        'form' => $form->createView(),
        'publication' => $publication,
    ]);
}

    

    
    #[Route('/delete/{id}', name: 'publication_delete', methods: ['POST'])]
    public function delete(Publication $publication, Request $request, EntityManagerInterface $em): Response
    {
        $em->remove($publication);
        $em->flush();
    
        // Si la requête est AJAX, retourne un JSON
        if ($request->isXmlHttpRequest()) {
            return $this->json(['success' => true, 'message' => 'Publication deleted successfully.']);
        }
    
        // Sinon redirige normalement
        $this->addFlash('success', 'Publication deleted.');
        return $this->redirectToRoute('app_publication_list');
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
    return $this->redirectToRoute('app_report_list'); // change this route if needed
}

#[Route('/comment/edit/{id}', name: 'app_comment_editAdmin')]
    public function editcomment(
        Comment $comment,
        Request $request,
        EntityManagerInterface $em,
        Security $security,
        UserRepository $userRepository
    ): Response {
        //$user = $security->getUser();
        $user = $userRepository->find(44);
    
        // Vérifie si l'utilisateur connecté est bien l'auteur du commentaire
        if (!$user || $comment->getUser() !== $user) {
            throw $this->createAccessDeniedException("You are not allowed to edit this comment.");
        }
    
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Vérifie le champ `username`
            $inputUsername = $form->get('username')->getData();
            if ($inputUsername !== $user->getUsername()) {
                $form->get('username')->addError(
                    new \Symfony\Component\Form\FormError("The username does not match your connected account.")
                );
            } else {
                // Si tout est ok, sauvegarde les modifications
                $em->flush();
                $this->addFlash('success', 'Comment updated successfully.');
                return $this->redirectToRoute('app_comment_list', ['comment_edited' => 1]);
            }
        }
    
        return $this->render('admin/CommentEditAdmin.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
        ]);
    }
    #[Route('/comment/delete/{id}', name: 'app_comment_deleteAdmin')]
public function deleteComment(Comment $comment, EntityManagerInterface $em, Security $security): Response
{
   /* if ($comment->getUser() !== $security->getUser()) {
        throw $this->createAccessDeniedException("You are not allowed to delete this comment.");
    }*/

    $em->remove($comment);
    $em->flush();

    $this->addFlash('success', 'Comment deleted successfully.');
    return $this->redirectToRoute('app_comment_list', ['comment_deleted' => 1]);
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


#[Route('/admin/publications', name: 'app_publication_list')]
public function listPublications(PublicationRepository $repo): Response
{
    $publications = $repo->findAll();

    return $this->render('admin/postAdmin.html.twig', [
        'publications' => $publications,
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
#[Route('/admin/comments', name: 'app_comment_list')]
public function listComments(CommentRepository $repo): Response
{
    $comments = $repo->findAll();

    return $this->render('admin/commentAdmin.html.twig', [
        'comments' => $comments,
    ]);
}

#[Route('/admin/publication/search', name: 'admin_publication_search')]
public function search(Request $request, PublicationRepository $publicationRepository): Response
{
    $term = $request->query->get('q');
    
    $queryBuilder = $publicationRepository->createQueryBuilder('p')
        ->leftJoin('p.user', 'u')
        ->addSelect('u');

    if ($term) {
        $queryBuilder
            ->andWhere('LOWER(p.title) LIKE :term OR LOWER(p.description) LIKE :term')
            ->setParameter('term', '%' . strtolower($term) . '%');
    }

    $queryBuilder->orderBy('p.publication_date', 'DESC');

    $publications = $queryBuilder->getQuery()->getResult();

    return $this->render('admin/_publication_rows.html.twig', [
        'publications' => $publications,
    ]);
}





    
}
