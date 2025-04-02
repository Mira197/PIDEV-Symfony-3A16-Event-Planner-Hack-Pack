<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Publication;
use App\Form\CommentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;


class CommentController extends AbstractController
{
    #[Route('/comment/new/{id}', name: 'app_comment_new')]
public function new(
    Publication $publication,
    Request $request,
    EntityManagerInterface $em,
    Security $security
): Response {
    $comment = new Comment();
    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        // Récupération du username saisi manuellement
        $inputUsername = $form->get('username')->getData();
        $user = $security->getUser();

        // Vérification que le username correspond à l'utilisateur connecté
        /*if (!$user || $inputUsername !== $user->getUsername()) {
            $this->addFlash('error', 'Le nom d’utilisateur saisi ne correspond pas à votre compte.');
            return $this->redirectToRoute('app_comment_new', ['id' => $publication->getPublicationId()]);
        }*/


        $comment->setUser($user);
        $comment->setPublication($publication);
        $comment->setCommentDate(new \DateTimeImmutable());

        $em->persist($comment);
        $em->flush();

        $this->addFlash('success', 'Commentaire ajouté avec succès !');

        return $this->redirectToRoute('app_forum');
    }

    return $this->render('newComment.html.twig', [
        'form' => $form->createView(),
        'publication' => $publication,
    ]);
}

#[Route('/comment/edit/{id}', name: 'app_comment_edit')]
public function edit(Comment $comment, Request $request, EntityManagerInterface $em, Security $security): Response
{
    if ($comment->getUser() !== $security->getUser()) {
        throw $this->createAccessDeniedException("You are not allowed to edit this comment.");
    }

    $form = $this->createForm(CommentType::class, $comment);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $em->flush();
        $this->addFlash('success', 'Comment updated successfully.');
        return $this->redirectToRoute('app_forum');
    }

    return $this->render('editComment.html.twig', [
        'form' => $form->createView(),
        'comment' => $comment,
    ]);
}

#[Route('/comment/delete/{id}', name: 'app_comment_delete')]
public function delete(Comment $comment, EntityManagerInterface $em, Security $security): Response
{
   /* if ($comment->getUser() !== $security->getUser()) {
        throw $this->createAccessDeniedException("You are not allowed to delete this comment.");
    }*/

    $em->remove($comment);
    $em->flush();

    $this->addFlash('success', 'Comment deleted successfully.');
    return $this->redirectToRoute('app_forum');
}
#[Route('/forum', name: 'app_forum')]
public function forum(EntityManagerInterface $em): Response
{
    $publications = $em->getRepository(Publication::class)->findAll();

    return $this->render('publicationClient.html.twig', [
        'publications' => $publications
    ]);
}


    

}
