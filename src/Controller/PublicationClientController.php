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
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class PublicationClientController extends AbstractController
{
     #[Route('/forum', name: 'app_publication_client')]
    public function index(Request $request, PublicationRepository $publicationRepository, PublicationTranslationService $translationService): Response
    {
        // Récupérer les publications triées par date de publication (plus récent en premier)
        $publications = $publicationRepository->findBy([], ['publication_date' => 'DESC']);
        $this->addFlash('debug', "Number of publications retrieved: " . count($publications));

        $publicationData = [];
        foreach ($publications as $index => $publication) {
            if (!$publication instanceof \App\Entity\Publication) {
                $this->addFlash('error', "Invalid publication at index {$index}: " . (is_object($publication) ? get_class($publication) : gettype($publication)));
                continue;
            }

            $paramName = 'publication_' . $publication->getPublicationId();
            $targetLanguage = $request->query->get($paramName);

            $allQueryParams = $request->query->all();
            $this->addFlash('debug', "All query params: " . json_encode($allQueryParams));
            $this->addFlash('debug', "Publication ID: {$publication->getPublicationId()}, Param: {$paramName}, Target Language: " . ($targetLanguage ?? 'null'));

            // Préparer les données pour le template
            $data = [
                'publication' => $publication, // Garder l'objet Publication pour accéder aux champs non traduits
                'title' => $publication->getTitle(), // Titre original
                'description' => $publication->getDescription(), // Description originale
            ];

            $this->addFlash('debug', "Original text before translation - Title: {$data['title']}, Description: {$data['description']}");

            // Appliquer la traduction si une langue cible est spécifiée
            if (!empty($targetLanguage) && is_string($targetLanguage)) {
                $this->addFlash('debug', "Target language for publication {$publication->getPublicationId()} is valid: {$targetLanguage}");
                try {
                    $data['title'] = $translationService->translate($data['title'], $targetLanguage);
                    $this->addFlash('debug', "Translated title: {$data['title']}");
                    $data['description'] = $translationService->translate($data['description'], $targetLanguage);
                    $this->addFlash('debug', "Translated description: {$data['description']}");
                    $this->addFlash('info', "Publication {$publication->getPublicationId()} translated to {$targetLanguage}: {$data['title']}");
                } catch (\Exception $e) {
                    $this->addFlash('error', "Translation error for publication {$publication->getPublicationId()}: " . $e->getMessage());
                }
            } else {
                $this->addFlash('debug', "No translation applied for publication {$publication->getPublicationId()} because target language is empty or invalid: " . var_export($targetLanguage, true));
            }

            $publicationData[] = $data;
        }

        $this->addFlash('debug', "Number of items in publicationData: " . count($publicationData));
        $this->addFlash('test', "Test flash message to verify display");
        return $this->render('publicationclient.html.twig', [
            'publications' => $publicationData,
        ]);
    }
   
    #[Route('/publication/new', name: 'app_publication_new')]
public function new(
    Request $request,
    EntityManagerInterface $em,
    UserRepository $userRepository,
    SessionInterface $session // Injection du service SessionInterface
): Response {
    // Vérifier si l'utilisateur est connecté via la session
    $userId = $session->get('user_id');
    
    // Si aucun utilisateur n'est trouvé dans la session, rediriger vers la page de login
    if (!$userId) {
        $this->addFlash('error', 'You must be logged in.');
        return $this->redirectToRoute('login'); // Redirige vers la page de connexion
    }
    
    // Récupérer l'utilisateur à partir de l'ID dans la session
    $user = $userRepository->find($userId);
    
    // Vérification si l'utilisateur est valide
    if (!$user) {
        $this->addFlash('error', 'User not found. Please log in again.');
        return $this->redirectToRoute('login'); // Redirige vers la page de connexion
    }
    
    // Création d'une nouvelle publication
    $publication = new Publication();
    $form = $this->createForm(PublicationType::class, $publication, [
        'user' => $user,  // Passer l'utilisateur connecté à la vue
    ]);
    
    $form->handleRequest($request);
    
    // Vérifier si le formulaire est soumis et valide
    if ($form->isSubmitted() && $form->isValid()) {
        // Assigner l'utilisateur à la publication
        $publication->setUser($user);
        $publication->setPublicationDate(new \DateTimeImmutable());
        
        // Gestion de l'upload d'image
        $uploadedImage = $request->files->get('image_file');
        if ($uploadedImage) {
            $imageData = file_get_contents($uploadedImage->getPathname());
            $publication->setImage($imageData);
        }
        
        // Sauvegarde de la publication dans la base de données
        $em->persist($publication);
        $em->flush();
        
        // Ajouter un message flash de succès
        $this->addFlash('success', 'Post created successfully.');
        
        // Rediriger vers la page des publications
        return $this->redirectToRoute('app_publication_client', ['post_success' => 1]);
    }
    
    // Rendu de la vue du formulaire si le formulaire n'est pas soumis ou valide
    return $this->render('newPublication.html.twig', [
        'form' => $form->createView(),
        'username' => $user->getUsername(), // Passer le username à la vue
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



}