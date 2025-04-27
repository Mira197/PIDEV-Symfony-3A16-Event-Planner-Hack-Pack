<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\LocationRepository;
use App\Repository\BookingRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/locations')]
class LocationController extends AbstractController
{
    #[Route('/suggested/{eventId}', name: 'suggested_locations')]
    public function suggested(
        int $eventId,
        Request $request,
        LocationRepository $locationRepo,
        BookingRepository $bookingRepo,
        EntityManagerInterface $em
    ): Response {
        // ✅ Récupération de l'événement
        $event = $em->getRepository(Event::class)->find($eventId);

        if (!$event || ($event->getUser() !== ($this->getUser() ?? $em->getRepository(\App\Entity\User::class)->find(49)))) {
            throw $this->createAccessDeniedException("Unauthorized");
        }

        // ✅ Récupération des filtres GET
        $queryCity = $request->query->get('city');
        $queryCapacity = $request->query->get('capacity');
        $queryMaxPrice = $request->query->get('maxPrice');
        $queryMinPrice = $request->query->get('minPrice');

        // Nettoyage des données (convertir les valeurs)
        $queryCapacity = is_numeric($queryCapacity) ? (int) $queryCapacity : null;
        $queryMaxPrice = is_numeric($queryMaxPrice) ? (float) $queryMaxPrice : null;
        $queryMinPrice = is_numeric($queryMinPrice) ? (float) $queryMinPrice : null;

        // ✅ Chargement de tous les lieux
        $allLocations = $locationRepo->findAll();

        // ✅ Filtrage manuel
        $suggested = array_filter($allLocations, function ($loc) use ($event, $bookingRepo, $queryCity, $queryCapacity, $queryMaxPrice, $queryMinPrice) {
            $cityMatch = !$queryCity || strcasecmp($loc->getCity()->value, $queryCity) === 0;

            $enoughCapacity = true;
            if ($queryCapacity !== null) {
                if ($queryCapacity === 101) {
                    $enoughCapacity = $loc->getCapacity() > 100;
                } elseif ($queryCapacity !== 0) {
                    $enoughCapacity = $loc->getCapacity() <= $queryCapacity;
                }
            }

            $aboveMinBudget = !$queryMinPrice || $loc->getPrice() >= $queryMinPrice;
            $withinBudget = !$queryMaxPrice || $loc->getPrice() <= $queryMaxPrice;

            // Appliquer les filtres cumulés
            if (!$cityMatch || !$enoughCapacity || !$aboveMinBudget || !$withinBudget) {
                return false;
            }

            // Ne pas exclure les lieux déjà réservés (juste suggestion)
            return true;
        });

        // ✅ Conversion image en base64 si nécessaire
        foreach ($suggested as $loc) {
            if ($loc->getImageData()) {
                $loc->base64Image = base64_encode(stream_get_contents($loc->getImageData()));
            }
        }

        // ✅ Rendu
        return $this->render('location/suggested.html.twig', [
            'event' => $event,
            'locations' => $suggested,
            'queryCity' => $queryCity,
            'queryCapacity' => $queryCapacity,
            'queryMaxPrice' => $queryMaxPrice,
            'queryMinPrice' => $queryMinPrice
        ]);
    }

    #[Route('/suggested/ajax/{eventId}', name: 'suggested_locations_ajax', methods: ['GET'])]
    public function suggestedAjax(
        int $eventId,
        Request $request,
        LocationRepository $locationRepo,
        BookingRepository $bookingRepo,
        EntityManagerInterface $em
    ): Response {
        $event = $em->getRepository(Event::class)->find($eventId);
        if (!$event) {
            return new Response("Event not found", 404);
        }

        $queryCity = $request->query->get('city');
        $queryCapacity = $request->query->get('capacity');
        $queryMaxPrice = $request->query->get('maxPrice');
        $queryMinPrice = $request->query->get('minPrice');

        $queryCapacity = is_numeric($queryCapacity) ? (int) $queryCapacity : null;
        $queryMaxPrice = is_numeric($queryMaxPrice) ? (float) $queryMaxPrice : null;
        $queryMinPrice = is_numeric($queryMinPrice) ? (float) $queryMinPrice : null;

        $allLocations = $locationRepo->findAll();
        $suggested = array_filter($allLocations, function ($loc) use ($event, $bookingRepo, $queryCity, $queryCapacity, $queryMaxPrice, $queryMinPrice) {
            $cityMatch = !$queryCity || strcasecmp($loc->getCity()->value, $queryCity) === 0;

            $enoughCapacity = true;
            if ($queryCapacity !== null) {
                if ($queryCapacity === 101) {
                    $enoughCapacity = $loc->getCapacity() > 100;
                } elseif ($queryCapacity !== 0) {
                    $enoughCapacity = $loc->getCapacity() <= $queryCapacity;
                }
            }

            $aboveMinBudget = !$queryMinPrice || $loc->getPrice() >= $queryMinPrice;
            $withinBudget = !$queryMaxPrice || $loc->getPrice() <= $queryMaxPrice;

            return $cityMatch && $enoughCapacity && $aboveMinBudget && $withinBudget;
        });

        foreach ($suggested as $loc) {
            if ($loc->getImageData()) {
                $loc->base64Image = base64_encode(stream_get_contents($loc->getImageData()));
            }
        }

        return $this->render('location/_suggested_venues_cards.html.twig', [
            'locations' => $suggested,
            'event' => $event
        ]);
    }

}
