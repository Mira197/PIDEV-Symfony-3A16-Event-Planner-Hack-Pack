<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\User;
use App\Entity\Event;
use App\Form\EventType;
use App\Repository\BookingRepository;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\VarDumper\VarDumper; // pour debug
use Symfony\Component\HttpFoundation\Session\SessionInterface;


#[Route('/events')]
final class EventController extends AbstractController
{
    #[Route(name: 'app_events', methods: ['GET'])]
    public function index(EventRepository $eventRepository, BookingRepository $bookingRepo, Request $request, EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository): Response
    {
        //sans session :$events = $eventRepository->findAll(); and remove $em and $user. $events devient:$events = $eventRepository->findAll();
        //avec session: $user = $this->getUser();

        //user statique
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

        //session avant
        /*$userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        
        if (!$user) {
            $this->addFlash('error', 'You must be logged in to access your events.');
            return $this->redirectToRoute('login');
        }*/
        $userId = $session->get('user_id');

        if (!$userId) {
            return $this->render('event/events.html.twig', ['events' => null]);
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            return $this->render('event/events.html.twig', ['events' => null]);
        }



        $events = $eventRepository->findBy(['user' => $user]);
        foreach ($events as $event) {
            if ($event->getImageData()) {
                $event->base64Image = base64_encode(stream_get_contents($event->getImageData()));
            }
            //  Injecter statut temporaire :booked/not booked
            $hasBooking = $bookingRepo->findOneBy(['event' => $event]);
            $event->status = $hasBooking ? 'booked' : 'not booked';
        }

        return $this->render('event/events.html.twig', [
            'events' => $events,
        ]);
    }


    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository): Response
    {
        //session
        /*$userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user) {
            $this->addFlash('error', 'You must be logged in to create an event.');
            return $this->redirectToRoute('login');
        }*/
        $userId = $session->get('user_id');
        if (!$userId) {
            $this->addFlash('error', 'You must be logged in to create an event.');
            return $this->redirectToRoute('login');
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('login');
        }

        $event = new Event();
        // ✅ Pré-remplir la date si transmise via /events/new?date=YYYY-MM-DD (calendar)
        $initialDate = $request->query->get('date');
        if ($initialDate) {
            try {
                $event->setStartDate(new \DateTime($initialDate . ' 10:00')); // Heure par défaut 10h
                $event->setEndDate(new \DateTime($initialDate . ' 12:00'));   // Heure par défaut 12h
            } catch (\Exception $e) {
                // Optionnel : ignorer ou loguer
            }
        }
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 🔥 Gérer l'image (upload facultatif)
            $imageFile = $form->get('image')->getData();
            if ($imageFile) {
                $binary = file_get_contents($imageFile->getPathname());
                $event->setImageData($binary);

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $event->setImageFilename($newFilename);

                $imageFile->move(
                    $this->getParameter('uploads_directory'),
                    $newFilename
                );
            }

            // 👤 Lier l'utilisateur connecté à l'event
            //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49); // à remplacer plus tard
            $event->setUser($user);

            $em->persist($event);
            $em->flush();

            $this->addFlash('success', 'Event created successfully!');

            return $this->redirectToRoute('app_events', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('event/newEvent.html.twig', [
            'event' => $event,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'event_show', methods: ['GET'])]
    public function show(Event $event, LocationRepository $locationRepo, BookingRepository $bookingRepo, EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository): Response
    {
        //user statique
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

        //session
        $user = $userRepository->find($session->get('user_id'));

        if (!$user || $event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to view this event.');
        }
        /*if ($event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to access this event.');
        }*/

        // Convertir image binaire en base64 si elle existe
        if ($event->getImageData()) {
            $event->base64Image = base64_encode(stream_get_contents($event->getImageData()));
        }
        // Récupérer toutes les locations disponibles (même ville, capacité suffisante, pas de conflit)
        // Charger tous les lieux
        $allLocations = $locationRepo->findAll();

        // Filtrer les lieux compatibles avec l'événement
        $availableLocations = array_filter($allLocations, function (Location $loc) use ($event, $bookingRepo) {
            $sameCity = strtolower($loc->getCity()->value) === strtolower($event->getCity());
            $enoughCapacity = $loc->getCapacity() >= $event->getCapacity();

            if (!$sameCity || !$enoughCapacity) {
                return false;
            }
            // Vérifie les conflits de réservation
            $conflicts = $bookingRepo->findConflicts($loc, $event->getStartDate(), $event->getEndDate());
            return count($conflicts) === 0;
        });
        // Injecter base64 pour les locations (si imageData existe)
        foreach ($availableLocations as $loc) {
            if ($loc->getImageData()) {
                $loc->base64Image = base64_encode(stream_get_contents($loc->getImageData()));
            }
        }

        return $this->render('event/showEvent.html.twig', [
            'event' => $event,
            'locations' => $availableLocations,
        ]);
    }

    #[Route('/{id}/edit', name: 'event_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Event $event, EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository): Response
    {
        //user statique
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

        /*if ($event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to edit this event.');
        }*/
        //session
        $user = $userRepository->find($session->get('user_id'));

        if (!$user || $event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to edit this event.');
        }

        // 🔁 Base64 si image BLOB SANS filename
        $base64Image = null;
        if ($event->getImageData() && !$event->getImageFilename()) {
            $base64Image = base64_encode(stream_get_contents($event->getImageData()));
        }
        $form = $this->createForm(EventType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('image')->getData();

            if ($imageFile) {
                $binary = file_get_contents($imageFile->getPathname());
                $event->setImageData($binary);

                $newFilename = uniqid() . '.' . $imageFile->guessExtension();
                $event->setImageFilename($newFilename);

                try {
                    $imageFile->move(
                        $this->getParameter('uploads_directory'),
                        $newFilename
                    );
                } catch (\Exception $e) {
                    // Optionnel : log ou flash message
                }
            }

            $em->flush();
            $this->addFlash('success', 'Event updated successfully!');

            return $this->redirectToRoute('app_events', [], Response::HTTP_SEE_OTHER);
        }

        return $this->renderForm('event/editEvent.html.twig', [
            'event' => $event,
            'form' => $form,
            'base64Image' => $base64Image, // ✅ injecté ici
        ]);
    }

    #[Route('/{id}', name: 'event_delete', methods: ['POST'])]
    public function delete(Request $request, Event $event, EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository): Response
    {
        //user statique
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

        /*if ($event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to delete this event.');
        }*/
        //session
        $user = $userRepository->find($session->get('user_id'));

        if (!$user || $event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to delete this event.');
        }

        if ($this->isCsrfTokenValid('delete' . $event->getIdEvent(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Event deleted successfully!');
        }

        return $this->redirectToRoute('app_events', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/event/{id}/framevr', name: 'event_framevr')]
    public function framevr(int $id, SessionInterface $session, UserRepository $userRepository): Response
    {
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        // (optionnel) vérifier que l'utilisateur peut voir cet event

        $frameUrl = $_ENV['FRAMEVR_URL'];
        $apiKey = $_ENV['FRAMEVR_API_KEY'];

        return $this->render('event/framevr.html.twig', [
            'frameUrl' => $frameUrl,
            'apiKey' => $apiKey,
        ]);
    }

    #[Route('/event/{id}/framevr-link', name: 'event_add_framevr')]
    public function addFrameVrLink(
        Request $request,
        EventRepository $eventRepo,
        EntityManagerInterface $em,
        int $id
    ): Response {
        $event = $eventRepo->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Event not found');
        }

        if ($request->isMethod('POST')) {
            $link = $request->request->get('frameVrLink');
            $event->setFrameVrLink($link);
            $em->flush();
            return $this->redirectToRoute('event_show', ['id' => $id]);
        }

        return $this->render('event/addFrameVr.html.twig', [
            'event' => $event
        ]);
    }



}
