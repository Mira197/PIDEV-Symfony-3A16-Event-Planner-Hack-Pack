<?php

namespace App\Controller;

use App\Entity\Publication;
use App\Form\PublicationType;
use App\Repository\PublicationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

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
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
    
            /*
            $inputUsername = $form->get('username')->getData();
            $user = $this->getUser();
            if (!$user || $inputUsername !== $user->getUsername()) {
                $this->addFlash('error', 'Le nom d’utilisateur ne correspond pas.');
                return $this->redirectToRoute('app_publication_new');
            }
            $publication->setUser($user);
            */
    
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
    
        return $this->render('newPublication.html.twig', [
            'form' => $form->createView(),
        ]);
    }
    
    
    #[Route('/publication/{id}/edit', name: 'app_publication_edit')]
public function edit(Request $request, Publication $publication, EntityManagerInterface $em): Response
{
    $form = $this->createForm(PublicationType::class, $publication);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $uploadedImage = $request->files->get('image_file');
        if ($uploadedImage) {
            $imageData = file_get_contents($uploadedImage->getPathname());
            $publication->setImage($imageData);
        }

        $em->flush();

        $this->addFlash('success', 'Publication mise à jour avec succès.');
        return $this->redirectToRoute('app_publication_client');
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
        throw new NotFoundHttpException('Publication not found.');
    }

    $em->remove($publication);
    $em->flush();

    $this->addFlash('success', 'Publication supprimée avec succès.');
    return $this->redirectToRoute('app_publication_client');
}


}
