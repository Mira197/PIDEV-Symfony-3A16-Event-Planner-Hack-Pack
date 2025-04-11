<?php

namespace App\Controller\admin;

use App\Entity\Location;
use App\Form\LocationType;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/admin/location')]
class LocationAdminController extends AbstractController
{
    #[Route('/', name: 'location_admin', methods: ['GET'])]
    public function index(LocationRepository $locationRepository): Response
    {
        return $this->render('admin/location_admin/indexLocation.html.twig', [
            'locations' => $locationRepository->findAll(),
        ]);
    }
    #[Route('/search', name: 'location_admin_search', methods: ['GET'])]
    public function search(Request $request, LocationRepository $locationRepository): Response
    {
        $keyword = $request->query->get('q', '');
        $locations = $locationRepository->searchByKeyword($keyword);

        return $this->render('admin/location_admin/indexLocation.html.twig', [
            'locations' => $locations,
            'query' => $keyword,
        ]);
    }

    #[Route('/new', name: 'location_admin_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $location = new Location();
        $form = $this->createForm(LocationType::class, $location);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // 🔥 Gestion de l'image envoyée
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Lire le contenu de l'image en binaire
            $binary = file_get_contents($imageFile->getPathname());
            $location->setImageData($binary);

            // Générer un nom unique et le stocker
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            $location->setImageFilename($newFilename);
            // ✅ Déplacer le fichier vers /public/uploads
            $imageFile->move(
                $this->getParameter('uploads_directory'), // récupère le chemin depuis services.yaml
                $newFilename
            );
        }
            $entityManager->persist($location);
            $entityManager->flush();

            $this->addFlash('success', 'Location added successfully !');

            return $this->redirectToRoute('location_admin', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('admin/location_admin/new.html.twig', [
            'location' => $location,
            'form' => $form,
        ]);
    }
    #[Route('/{id}', name: 'location_admin_show', methods: ['GET'])]
    public function show(Location $location): Response
    {
        return $this->render('admin/location_admin/show.html.twig', [
            'location' => $location,
        ]);
    }

    #[Route('/{id}/edit', name: 'location_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
    $form = $this->createForm(LocationType::class, $location);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            // Lire le contenu de l’image
            $binary = file_get_contents($imageFile->getPathname());
            $location->setImageData($binary);

            // Nom de fichier unique
            $newFilename = uniqid().'.'.$imageFile->guessExtension();
            $location->setImageFilename($newFilename);

            // Déplacement dans /public/uploads
            try {
                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                // Optionnel : message flash ou log d'erreur
            }
        }

        $entityManager->flush();
        $this->addFlash('success', 'Modification réussie !');

        return $this->redirectToRoute('location_admin', [], Response::HTTP_SEE_OTHER);
    }

    return $this->renderForm('admin/location_admin/edit.html.twig', [
        'location' => $location,
        'form' => $form,
    ]);
}
    #[Route('/{id}', name: 'location_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Location $location, EntityManagerInterface $entityManager): Response
    {
    if ($this->isCsrfTokenValid('delete'.$location->getIdLocation(), $request->request->get('_token'))) {
        $entityManager->remove($location);
        $entityManager->flush();
        $this->addFlash('success', 'Location deleted successfully !');
    }

    return $this->redirectToRoute('location_admin', [], Response::HTTP_SEE_OTHER);
    }

   


}
