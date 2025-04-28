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
use Symfony\Component\HttpClient\HttpClient;
use App\Service\PurgomalumService;

class PublicationClientController extends AbstractController
{
    private $purgomalumService;

    // Symfony injects the PurgomalumService automatically
    public function __construct(PurgomalumService $purgomalumService)
    {
        $this->purgomalumService = $purgomalumService;
    }

    #[Route('/forum', name: 'app_publication_client')]
    public function index(Request $request, PublicationRepository $publicationRepository, PublicationTranslationService $translationService): Response
    {
        // Retrieve publications sorted by publication date (most recent first)
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
            $this->addFlash('debug', "All query parameters: " . json_encode($allQueryParams));
            $this->addFlash('debug', "Publication ID: {$publication->getPublicationId()}, Parameter: {$paramName}, Target Language: " . ($targetLanguage ?? 'null'));

            // Prepare data for the template
            $data = [
                'publication' => $publication, // Keep the Publication object to access untranslated fields
                'title' => $publication->getTitle(), // Original title
                'description' => $publication->getDescription(), // Original description
            ];

            $this->addFlash('debug', "Original text before translation - Title: {$data['title']}, Description: {$data['description']}");

            // Apply translation if a target language is specified
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
        SessionInterface $session,
        PurgomalumService $purgomalumService
    ): Response {
        // Check if the user is logged in via session
        $userId = $session->get('user_id');
    
        if (!$userId) {
            $this->addFlash('error', 'You must be logged in to create a publication.');
            return $this->redirectToRoute('login');
        }
    
        $user = $userRepository->find($userId);
    
        if (!$user) {
            $this->addFlash('error', 'User not found. Please log in again.');
            return $this->redirectToRoute('login');
        }
    
        $publication = new Publication();
        $form = $this->createForm(PublicationType::class, $publication, [
            'user' => $user,  // Passer l'utilisateur connecté à la vue
        ]);    
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $publication->setUser($user);
        $publication->setPublicationDate(new \DateTimeImmutable());
            $title = $publication->getTitle();
            $description = $publication->getDescription();
    
            try {
                // Check the title for inappropriate content
                if ($purgomalumService->containsProfanity($title)) {
                    $form->get('title')->addError(new FormError('The title contains inappropriate words.'));
                }
    
                // Check the description for inappropriate content
                if ($purgomalumService->containsProfanity($description)) {
                    $form->get('description')->addError(new FormError('The description contains inappropriate words.'));
                }
    
                // If errors were added, they will be displayed in the form
                if ($form->getErrors(true)->count() > 0) {
                    return $this->render('newPublication.html.twig', [
                        'form' => $form->createView(),
                        'username' => $user->getUsername(),
                    ]);
                }
    
                $publication->setUser($user);
                $publication->setPublicationDate(new \DateTimeImmutable());
    
                // Handle image upload
                $uploadedImage = $request->files->get('image_file');
                if ($uploadedImage) {
                    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif'];
                    $maxSize = 5 * 1024 * 1024; // 5 MB
                    if (!in_array($uploadedImage->getMimeType(), $allowedTypes)) {
                        $form->addError(new FormError('Please upload a valid image (JPEG, PNG, GIF).'));
                        return $this->render('newPublication.html.twig', [
                            'form' => $form->createView(),
                            'username' => $user->getUsername(),
                        ]);
                    }
                    if ($uploadedImage->getSize() > $maxSize) {
                        $form->addError(new FormError('The image is too large. The maximum size is 5 MB.'));
                        return $this->render('newPublication.html.twig', [
                            'form' => $form->createView(),
                            'username' => $user->getUsername(),
                        ]);
                    }
                    $imageData = file_get_contents($uploadedImage->getPathname());
                    $publication->setImage($imageData);
                }
    
                $em->persist($publication);
                $em->flush();
    
                $this->addFlash('success', 'Publication created successfully.');
                return $this->redirectToRoute('app_publication_client', ['post_success' => 1]);
            } catch (\Exception $e) {
                $form->addError(new FormError('Error while verifying the content: ' . $e->getMessage()));
                return $this->render('newPublication.html.twig', [
                    'form' => $form->createView(),
                    'username' => $user->getUsername(),
                ]);
            }
        }
    
        return $this->render('newPublication.html.twig', [
            'form' => $form->createView(),
            'username' => $user->getUsername(), // Pass the username to the template
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
            $user = $userRepository->find(49); // 🔁 Test user

            if (!$user || $inputUsername !== $user->getUsername()) {
                $form->get('username')->addError(
                    new FormError('The username does not match your account.')
                );
            } else {
                $publication->setUser($user);

                // ✅ Manually retrieve the image from the "image_file" field
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

        $this->addFlash('success', 'Publication deleted successfully.');
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