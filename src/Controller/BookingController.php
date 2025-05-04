<?php

namespace App\Controller;

use App\Entity\Booking;
use App\Entity\Event;
use App\Entity\Location;
use App\Entity\User;
use App\Form\BookingType;
use App\Repository\BookingRepository;
use App\Repository\LocationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Knp\Snappy\Pdf;

#[Route('/bookings')]
class BookingController extends AbstractController
{
    #[Route('/new/{id}', name: 'booking_new', methods: ['GET', 'POST'])]
    public function book(
        Request $request,
        Event $event,
        EntityManagerInterface $em,
        LocationRepository $locationRepo,
        BookingRepository $bookingRepo,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        //user statique
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

        /*if ($event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to book for this event.');
        }*/
        //session
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user || $event->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to book for this event.');
        }

        $booking = new Booking();

        // 👉 Récupérer la location choisie via une requête GET
        $locationId = $request->query->get('location_id');
        $location = $em->getRepository(Location::class)->findOneBy([
            'id_location' => $locationId
        ]);
        if (!$location) {
            $this->addFlash('error', 'No location selected.');
            return $this->redirectToRoute('suggested_locations', ['eventId' => $event->getIdEvent()]);
        }
        $booking->setLocation($location);

        /*$form = $this->createForm(BookingType::class, $booking);*/
        $form = $this->createForm(BookingType::class, $booking, [
            'location_name' => $location->getName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /*$location = $booking->getLocation();*/
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
    public function myBookings(EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository): Response
    {
        //user statique
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

        //session
        /*$userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user) {
            throw $this->createAccessDeniedException('Please log in to view your bookings.');
        }*/

        $userId = $session->get('user_id');
        // Redirige s'il n'est pas connecté
        if (!$userId) {
            $this->addFlash('error', 'Please log in to view your bookings.');
            return $this->redirectToRoute('login');
        }

        $user = $userRepository->find($userId);
        if (!$user) {
            $this->addFlash('error', 'User not found.');
            return $this->redirectToRoute('login');
        }

        $events = $em->getRepository(Event::class)->findBy(['user' => $user]);
        $bookings = $em->getRepository(Booking::class)->findBy(['event' => $events]);

        // 🕒 Calcul du statut de chaque réservation (sans modifier l'entité)
        $now = new \DateTime();
        foreach ($bookings as $booking) {
            if ($booking->getStartDate() > $now) {
                $booking->status = 'upcoming';
            } elseif ($booking->getEndDate() < $now) {
                $booking->status = 'completed';
            } else {
                $booking->status = 'in_progress';
            }
        }

        return $this->render('booking/listBooking.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/{id}/edit', name: 'booking_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository): Response
    {
        //user statique
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

        /*if ($booking->getEvent()->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to edit this booking.');
        }*/
        //session
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user || $booking->getEvent()->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to edit this booking.');
        }

        /*$form = $this->createForm(BookingType::class, $booking);*/
        $form = $this->createForm(BookingType::class, $booking, [
            'location_name' => $booking->getLocation()?->getName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $location = $booking->getLocation();
            $start = $booking->getStartDate();
            $end = $booking->getEndDate();

            // Conflit ?
            $conflicts = $em->getRepository(Booking::class)->findConflicts($location, $start, $end);

            // ⚠️ Ignore current booking in conflict check (if editing same one)
            $conflicts = array_filter($conflicts, fn($conflict) => $conflict->getIdBooking() !== $booking->getIdBooking());

            if ($conflicts) {
                $this->addFlash('error', 'This location is already booked for that period.');
            } else {
                $em->flush();
                $this->addFlash('success', 'Booking updated successfully!');
                return $this->redirectToRoute('booking_list');
            }
        }

        return $this->renderForm('booking/editBooking.html.twig', [
            'form' => $form,
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}', name: 'booking_delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $em, SessionInterface $session, UserRepository $userRepository): Response
    {
        //user statique
        //$user = $this->getUser() ?? $em->getRepository(User::class)->find(49);

        /*if ($booking->getEvent()->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to delete this booking.');
        }*/
        //session
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        if (!$user || $booking->getEvent()->getUser() !== $user) {
            throw $this->createAccessDeniedException('You are not allowed to delete this booking.');
        }

        if ($this->isCsrfTokenValid('delete' . $booking->getIdBooking(), $request->request->get('_token'))) {
            $em->remove($booking);
            $em->flush();
            $this->addFlash('success', 'Booking deleted successfully!');
        }

        return $this->redirectToRoute('booking_list', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/download', name: 'booking_download', methods: ['GET'])]
    public function downloadPdf(Booking $booking, Pdf $knpSnappyPdf): Response
    {
        $eventImage = null;
        $locationImage = null;

        if ($booking->getEvent()->getImageData()) {
            $eventImage = base64_encode(stream_get_contents($booking->getEvent()->getImageData()));
        }

        if ($booking->getLocation()->getImageData()) {
            $locationImage = base64_encode(stream_get_contents($booking->getLocation()->getImageData()));
        }
        $html = $this->renderView('booking/pdfBooking.html.twig', [
            'booking' => $booking,
            'eventImage' => $eventImage,
            'locationImage' => $locationImage,
        ]);

        $pdfContent = $knpSnappyPdf->getOutputFromHtml($html);

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="booking-details.pdf"'
        ]);
    }


}
