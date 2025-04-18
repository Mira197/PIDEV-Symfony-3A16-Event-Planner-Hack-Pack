<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\User;
use App\Form\PublicationType;
use App\Repository\CommentRepository;
use App\Repository\PublicationRepository;
use App\Repository\ReportRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;

class PublicationClientController extends AbstractController
{
    #[Route('/forum', name: 'app_publication_client')]
    public function index(PublicationRepository $publicationRepository): Response
    {
        $publications = $publicationRepository->findBy([], ['publication_date' => 'DESC']);

        return $this->render('publicationclient.html.twig', [
            'publications' => $publications,
        ]);
    }

    #[Route('/publication/new', name: 'app_publication_new')]
public function new(Request $request, EntityManagerInterface $em, UserRepository $userRepository): Response
{
    $publication = new Publication();
    $form = $this->createForm(PublicationType::class, $publication);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $inputUsername = $form->get('username')->getData();
        $user = $userRepository->find(49);
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

            // ✅ Redirection avec succès
            return $this->redirectToRoute('app_publication_client', ['post_success' => 1]);
        }
    }

    return $this->render('newPublication.html.twig', [
        'form' => $form->createView(),
    ]);
}
    


   #[Route('/publication/{id}/edit', name: 'app_publication_edit')]
public function edit(
    Request $request,
    Publication $publication,
    EntityManagerInterface $em,
    UserRepository $userRepository
): Response {
    $form = $this->createForm(PublicationType::class, $publication);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $inputUsername = $form->get('username')->getData();
        $user = $userRepository->find(49); // 🔁 utilisateur test

        if (!$user || $inputUsername !== $user->getUsername()) {
            $form->get('username')->addError(
                new FormError('The username does not match your account.')
            );
        } else {
            $publication->setUser($user);

            // ✅ Récupération manuelle de l'image depuis le champ "image_file"
            $uploadedImage = $request->files->get('image_file');
            if ($uploadedImage instanceof \Symfony\Component\HttpFoundation\File\UploadedFile) {
                $imageData = file_get_contents($uploadedImage->getPathname());
                $publication->setImage($imageData);
            }

            $em->flush();

            $this->addFlash('success', 'Publication updated successfully.');
            return $this->redirectToRoute('app_publication_client', [
                'edit_success' => 1
            ]);
        }
    }

    return $this->render('editPublication.html.twig', [
        'form' => $form->createView(),
        'publication' => $publication
    ]);
}


    #[Route('/publication/delete/{id}', name: 'app_publication_delete')]
    public function delete(EntityManagerInterface $em, PublicationRepository $publicationRepository, int $id): Response
    {
        $publication = $publicationRepository->find($id);
        if (!$publication) {
            throw $this->createNotFoundException('Publication not found.');
        }

        $em->remove($publication);
        $em->flush();

        $this->addFlash('success', 'Publication supprimée avec succès.');
        return $this->redirectToRoute('app_publication_client', ['post_deleted' => 1]);
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
    return $this->render('admin/reports.html.twig', [
        'reports' => $reports,
    ]);
}

#[Route('/admin/comments', name: 'app_comment_list')]
public function listComments(CommentRepository $repo): Response
{
    $comments = $repo->findAll();
    return $this->render('admin/comments.html.twig', [
        'comments' => $comments,
    ]);
}

}
