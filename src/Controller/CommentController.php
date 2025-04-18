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
use App\Repository\UserRepository;


class CommentController extends AbstractController
{
    #[Route('/comment/new/{id}', name: 'app_comment_new')]
    public function new(
        Publication $publication,
        Request $request,
        EntityManagerInterface $em,
        Security $security,
        UserRepository $userRepository
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Récupère le nom d'utilisateur saisi manuellement
            $inputUsername = $form->get('username')->getData();
           // $user = $security->getUser();
            $user = $userRepository->find(49);
    
            // Vérifie si l'utilisateur est connecté et correspond
            if (!$user || $inputUsername !== $user->getUsername()) {
                $form->get('username')->addError(
                    new \Symfony\Component\Form\FormError("The username does not match your connected account.")
                );
    
                return $this->render('newComment.html.twig', [
                    'form' => $form->createView(),
                    'publication' => $publication,
                ]);
            }
    
            $comment->setUser($user);
            $comment->setPublication($publication);
            $comment->setCommentDate(new \DateTimeImmutable());
    
            $em->persist($comment);
            $em->flush();
    
            $this->addFlash('success', 'Comment added successfully!');
            return $this->redirectToRoute('app_publication_client', ['comment_success' => 1]);
        }
    
        return $this->render('newComment.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
        ]);
    }
    

    #[Route('/comment/edit/{id}', name: 'app_comment_edit')]
    public function edit(
        Comment $comment,
        Request $request,
        EntityManagerInterface $em,
        Security $security,
        UserRepository $userRepository
    ): Response {
        //$user = $security->getUser();
        $user = $userRepository->find(49);
    
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
                return $this->redirectToRoute('app_publication_client', ['comment_edited' => 1]);
            }
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
    return $this->redirectToRoute('app_forum', ['comment_deleted' => 1]);
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
