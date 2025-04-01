<?php

namespace App\Controller;

use App\Repository\EventRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class EventController extends AbstractController
{
    #[Route('/events', name: 'app_events')]
    public function index(EventRepository $eventRepository, Request $request): Response
    {
        $events = $eventRepository->findAll();
        foreach ($events as $event) {
            if ($event->getImageData()) {
                $event->base64Image = base64_encode(stream_get_contents($event->getImageData()));
            }
        }

        return $this->render('event/events.html.twig', [
            'events' => $events,
        ]);
    }
}
