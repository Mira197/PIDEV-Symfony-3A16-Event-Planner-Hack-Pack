<?php

namespace App\Controller;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
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
        SessionInterface $session,
        Request $request,
        EntityManagerInterface $em,
        UserRepository $userRepository
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
    
        // 🔐 Récupération des données de session
        $userId = $session->get('user_id');
        $username = $session->get('username');
        $user = $userRepository->find($userId);
    
        // 🛑 Vérification : utilisateur connecté obligatoire
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to comment.');
            return $this->redirectToRoute('app_login');
        }
    
        if ($form->isSubmitted() && $form->isValid()) {
            // Association du commentaire à l'utilisateur et à la publication
            $comment->setUser($user);
            $comment->setPublication($publication);
            $comment->setCommentDate(new \DateTimeImmutable());
    
            $em->persist($comment);
            $em->flush();
    
            $this->addFlash('success', 'Comment added successfully!');
            return $this->redirectToRoute('app_publication_client', ['comment_success' => 1]);
        }
    
        // Vue initiale du formulaire
        return $this->render('newComment.html.twig', [
            'form' => $form->createView(),
            'publication' => $publication,
            'username' => $username, // pour affichage lecture seule dans la vue
        ]);
    }
    
    #[Route('/comment/edit/{id}', name: 'app_comment_edit')]
    public function edit(
        Comment $comment,
        Request $request,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        // 🔐 Récupérer l'utilisateur via la session
        $userId = $session->get('user_id');
        $username = $session->get('username');
        $user = $userRepository->find($userId);
    
        // 🛑 Vérifie que l'utilisateur est bien l'auteur du commentaire
        if (!$user || $comment->getUser() !== $user) {
            throw $this->createAccessDeniedException("You are not allowed to edit this comment.");
        }
    
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            // 💾 Enregistre les modifications
            $em->flush();
            $this->addFlash('success', 'Comment updated successfully.');
            return $this->redirectToRoute('app_publication_client', ['comment_edited' => 1]);
        }
    
        return $this->render('editComment.html.twig', [
            'form' => $form->createView(),
            'comment' => $comment,
            'username' => $username, // 👈 à utiliser dans le champ readonly du Twig
        ]);
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
