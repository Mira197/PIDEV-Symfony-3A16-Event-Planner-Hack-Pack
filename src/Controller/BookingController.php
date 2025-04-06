<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Event;
use App\Entity\Location;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\LocationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/bookings')]
class BookingController extends AbstractController
{
    #[Route('/new/{id}', name: 'booking_new', methods: ['GET', 'POST'])]
    public function book(
        Request $request,
        Event $event,
        EntityManagerInterface $em,
        LocationRepository $locationRepo,
        BookingRepository $bookingRepo
    ): Response {
        $booking = new Booking();
        $form = $this->createForm(BookingType::class, $booking);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $location = $booking->getLocation();
            $start = $booking->getStartDate();
            $end = $booking->getEndDate();

            // 🔒 Vérification conflit
            $conflicts = $bookingRepo->findConflicts($location, $start, $end);
            if ($conflicts) {
                $this->addFlash('error', 'This location is already booked for that period.');
            } else {
                $booking->setEvent($event);
                $em->persist($booking);
                $em->flush();

                $this->addFlash('success', 'Booking created!');
                return $this->redirectToRoute('app_events');
            }
        }

        return $this->renderForm('booking/newBooking.html.twig', [
            'form' => $form,
            'event' => $event,
        ]);
    }

    #[Route('/my-bookings', name: 'booking_list', methods: ['GET'])]
    public function myBookings(EntityManagerInterface $em): Response
    {
        $user = $this->getUser() ?? $em->getRepository(User::class)->find(3);
        $events = $em->getRepository(Event::class)->findBy(['user' => $user]);
        $bookings = $em->getRepository(Booking::class)->findBy(['event' => $events]);

        return $this->render('booking/listBooking.html.twig', [
            'bookings' => $bookings,
        ]);
    }
}
