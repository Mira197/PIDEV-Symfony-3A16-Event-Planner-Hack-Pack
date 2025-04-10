<?php

namespace App\Controller;

use App\Entity\Location;
use App\Entity\User;
use App\Entity\Event;
use App\Form\EventType;
use App\Repository\BookingRepository;
use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\VarDumper\VarDumper; // pour debug


#[Route('/events')]
final class EventController extends AbstractController
{
    #[Route(name: 'app_events', methods: ['GET'])]
    public function index(EventRepository $eventRepository, Request $request,EntityManagerInterface $em): Response
    {
        //sans session :$events = $eventRepository->findAll(); and remove $em and $user. $events devient:$events = $eventRepository->findAll();
        //avec session: $user = $this->getUser();
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $events = $eventRepository->findBy(['user' => $user]);
        foreach ($events as $event) {
            if ($event->getImageData()) {
                $event->base64Image = base64_encode(stream_get_contents($event->getImageData()));
            }
        }

        return $this->render('event/events.html.twig', [
            'events' => $events,
        ]);
    }


    #[Route('/new', name: 'event_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $em): Response
    {
        $event = new Event();
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

            // 👤 Lier l'utilisateur connecté (provisoire)
            $user = $this->getUser() ?? $em->getRepository(User::class)->find(3); // à remplacer plus tard
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
    public function show(Event $event, LocationRepository $locationRepo,BookingRepository $bookingRepo,EntityManagerInterface $em): Response
    {
        //session : $user = $this->getUser();
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);

        if ($event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to access this event.');
        }

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
    public function edit(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        //session : $user = $this->getUser();
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);

        if ($event->getUser() !== $user) {
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
    public function delete(Request $request, Event $event, EntityManagerInterface $em): Response
    {
        //session : $user = $this->getUser();
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);

        if ($event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to delete this event.');
        }

        if ($this->isCsrfTokenValid('delete' . $event->getIdEvent(), $request->request->get('_token'))) {
            $em->remove($event);
            $em->flush();
            $this->addFlash('success', 'Event deleted successfully!');
        }

        return $this->redirectToRoute('app_events', [], Response::HTTP_SEE_OTHER);
    }

}
