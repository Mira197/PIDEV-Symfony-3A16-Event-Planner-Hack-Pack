<?php

namespace App\Controller;

use App\Repository\EventRepository;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Tattali\CalendarBundle\Service\Calendar as CalendarService;
use Tattali\CalendarBundle\Calendar\Event as CalendarEvent;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;


class CalendarController extends AbstractController
{
    #[Route('/calendar', name: 'calendar')]
    public function index(): Response
    {
        return $this->render('calendar/calendar.html.twig');
    }

    #[Route('/calendar/load', name: 'calendar_load')]
    public function load(
        EventRepository $eventRepo,
        BookingRepository $bookingRepo,
        SessionInterface $session,
        UserRepository $userRepo
    ): JsonResponse {
        $userId = $session->get('user_id');
        $user = $userRepo->find($userId);
        if (!$user) {
            return new JsonResponse([], 204); // no content
        }

        $events = $eventRepo->findBy(['user' => $user]);
        $calendarEvents = [];

        foreach ($events as $event) {
            $isBooked = $bookingRepo->findOneBy(['event' => $event]);

            $calendarEvents[] = [
                'title' => $event->getName(),
                'start' => $event->getStartDate()->format('Y-m-d H:i:s'),
                'end' => $event->getEndDate()->format('Y-m-d H:i:s'),
                'backgroundColor' => $isBooked ? '#f14668' : '#48c78e',
                'borderColor' => $isBooked ? '#e74c3c' : '#2ecc71',
                'textColor' => '#fff',
                'url' => $this->generateUrl('event_show', ['id' => $event->getIdEvent()])
            ];
        }

        return new JsonResponse($calendarEvents);
    }

    #[Route('/calendar/search', name: 'calendar_search')]
    public function search(
        Request $request,
        EventRepository $eventRepo,
        BookingRepository $bookingRepo,
        SessionInterface $session,
        UserRepository $userRepo
    ): JsonResponse {
        $title = $request->query->get('title');
        $status = $request->query->get('status');

        $userId = $session->get('user_id');
        $user = $userRepo->find($userId);
        if (!$user) {
            return new JsonResponse([], 200);
        }

        $qb = $eventRepo->createQueryBuilder('e')
            ->andWhere('e.user = :user')
            ->setParameter('user', $user);

        if (!empty($title)) {
            $qb->andWhere('e.name LIKE :title')
                ->setParameter('title', '%' . $title . '%');
        }

        $events = $qb->getQuery()->getResult();

        $filteredEvents = [];
        foreach ($events as $event) {
            $isBooked = $bookingRepo->findOneBy(['event' => $event]);

            if ($status === 'booked' && !$isBooked)
                continue;
            if ($status === 'not_booked' && $isBooked)
                continue;

            $filteredEvents[] = [
                'title' => $event->getName(),
                'start' => $event->getStartDate()->format('Y-m-d H:i:s'),
                'end' => $event->getEndDate()->format('Y-m-d H:i:s'),
                'backgroundColor' => $isBooked ? '#f14668' : '#48c78e',
                'borderColor' => $isBooked ? '#e74c3c' : '#2ecc71',
                'textColor' => '#fff',
                'url' => $this->generateUrl('event_show', ['id' => $event->getIdEvent()])
            ];
        }

        return new JsonResponse($filteredEvents);
    }

    #[Route('/calendar/export-ics', name: 'calendar_export_ics')]
    public function exportIcs(
        EventRepository $eventRepo,
        BookingRepository $bookingRepo,
        SessionInterface $session,
        UserRepository $userRepo
    ): StreamedResponse {
        $userId = $session->get('user_id');
        $user = $userRepo->find($userId);
        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $events = $eventRepo->findBy(['user' => $user]);

        $content = "BEGIN:VCALENDAR\r\nVERSION:2.0\r\nPRODID:-//Your App//EN\r\n";

        foreach ($events as $event) {
            $start = $event->getStartDate()->format('Ymd\THis');
            $end = $event->getEndDate()->format('Ymd\THis');
            $title = addslashes($event->getName());

            $content .= "BEGIN:VEVENT\r\n";
            $content .= "SUMMARY:{$title}\r\n";
            $content .= "DTSTART:{$start}\r\n";
            $content .= "DTEND:{$end}\r\n";
            $content .= "END:VEVENT\r\n";
        }

        $content .= "END:VCALENDAR\r\n";

        return new StreamedResponse(function () use ($content) {
            echo $content;
        }, 200, [
            'Content-Type' => 'text/calendar; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="planning.ics"',
        ]);
    }

    #[Route('/calendar/export/pdf', name: 'calendar_export_pdf')]
    public function exportPdf(
        EventRepository $eventRepo,
        BookingRepository $bookingRepo,
        SessionInterface $session,
        UserRepository $userRepo,
        \Knp\Snappy\Pdf $knpSnappyPdf
    ): Response {
        $userId = $session->get('user_id');
        $user = $userRepo->find($userId);

        if (!$user) {
            throw $this->createAccessDeniedException();
        }

        $events = $eventRepo->findBy(['user' => $user]);

        $eventData = [];
        $bookedCount = 0;
        foreach ($events as $event) {
            $isBooked = $bookingRepo->findOneBy(['event' => $event]);
            if ($isBooked) {
                $bookedCount++;
            }

            $eventData[] = [
                'name' => $event->getName(),
                'startDate' => $event->getStartDate(),
                'endDate' => $event->getEndDate(),
                'booked' => $isBooked ? true : false,
            ];
        }

        $html = $this->renderView('calendar/calendarpdf.html.twig', [
            'events' => $eventData,
            'total' => count($eventData),
            'booked' => $bookedCount
        ]);

        return new Response(
            $knpSnappyPdf->getOutputFromHtml($html),
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="calendar.pdf"'
            ]
        );
    }

}
