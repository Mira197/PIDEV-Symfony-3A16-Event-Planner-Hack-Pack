<?php

namespace App\Controller\admin;

use App\Entity\Booking;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use App\Form\BookingType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

#[Route('/admin/booking')]
class BookingAdminController extends AbstractController
{
    #[Route('/', name: 'booking_admin_index', methods: ['GET'])]
    public function index(BookingRepository $bookingRepository): Response
    {
        $bookings = $bookingRepository->findAll();

        return $this->render('admin/booking_admin/indexBookingAdmin.html.twig', [
            'bookings' => $bookings,
        ]);
    }

    #[Route('/ajax/search', name: 'booking_ajax_search', methods: ['GET'])]
    public function ajaxSearch(Request $request, BookingRepository $bookingRepo, NormalizerInterface $normalizer): JsonResponse
    {
        $keyword = $request->query->get('q', '');
        $bookings = $bookingRepo->searchByKeyword($keyword);

        $json = $normalizer->normalize($bookings, 'json', [
            'attributes' => [
                'idBooking',
                'startDate',
                'endDate',
                'event' => ['name'],
                'location' => ['name']
            ]
        ]);

        return new JsonResponse($json);
    }

    #[Route('/{id}/edit', name: 'booking_admin_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Booking $booking, EntityManagerInterface $em): Response
    {
        /*$form = $this->createForm(BookingType::class, $booking);*/
        // ✅ Passe le nom de la location pour l'afficher comme champ en lecture seule
        $form = $this->createForm(BookingType::class, $booking, [
            'location_name' => $booking->getLocation()?->getName(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Conflit ?
            $location = $booking->getLocation();
            $start = $booking->getStartDate();
            $end = $booking->getEndDate();

            $conflicts = $em->getRepository(Booking::class)->findConflicts($location, $start, $end);
            $conflicts = array_filter($conflicts, fn($conflict) => $conflict->getIdBooking() !== $booking->getIdBooking());

            if ($conflicts) {
                $this->addFlash('error', 'This location is already booked for that period.');
            } else {
                $em->flush();
                $this->addFlash('success', 'Booking updated successfully!');
                return $this->redirectToRoute('booking_admin_index');
            }
        }

        return $this->renderForm('admin/booking_admin/editBookingAdmin.html.twig', [
            'form' => $form,
            'booking' => $booking,
        ]);
    }

    #[Route('/{id}', name: 'booking_admin_delete', methods: ['POST'])]
    public function delete(Request $request, Booking $booking, EntityManagerInterface $em): Response
    {
        if ($this->isCsrfTokenValid('delete' . $booking->getIdBooking(), $request->request->get('_token'))) {
            $em->remove($booking);
            $em->flush();
            $this->addFlash('success', 'Booking deleted successfully!');
        }

        return $this->redirectToRoute('booking_admin_index', [], Response::HTTP_SEE_OTHER);
    }

}

