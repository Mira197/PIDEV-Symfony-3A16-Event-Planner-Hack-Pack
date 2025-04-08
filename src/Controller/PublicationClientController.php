<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Entity\User;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
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

            // 👇 Utilisateur de test avec ID 44
           // $user = $userRepository->find(44);
            $user = $this->getUser();
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

                return $this->redirectToRoute('app_publication_client');
            }
        }

        return $this->render('newPublication.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/publication/{id}/edit', name: 'app_publication_edit')]
    public function edit(Request $request, Publication $publication, EntityManagerInterface $em,UserRepository $userRepository): Response
    {
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $inputUsername = $form->get('username')->getData();
            $user = $this->getUser();
            //$user = $userRepository->find(44);
    
            if (!$user || $inputUsername !== $user->getUsername()) {
                $form->get('username')->addError(
                    new FormError('The username does not match your account.')
                );
            } else {
                $publication->setUser($user);
    
                $uploadedImage = $request->files->get('image_file');
                if ($uploadedImage) {
                    $imageData = file_get_contents($uploadedImage->getPathname());
                    $publication->setImage($imageData);
                }
    
                $em->flush();
    
                $this->addFlash('success', 'Publication updated successfully.');
                return $this->redirectToRoute('app_publication_client');
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
        return $this->redirectToRoute('app_publication_client');
    }
}
