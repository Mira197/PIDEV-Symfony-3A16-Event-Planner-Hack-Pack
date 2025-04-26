<?php

namespace App\Controller;

use App\Entity\Event;
use App\Repository\LocationRepository;
use App\Repository\BookingRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

#[Route('/locations')]
class LocationController extends AbstractController
{
    private $tomtomApiKey;

    public function __construct()
    {
        $this->tomtomApiKey = $_ENV['TOMTOM_API_KEY'];
    }

    #[Route('/suggested/{eventId}', name: 'suggested_locations')]
    public function suggested(
        int $eventId,
        Request $request,
        LocationRepository $locationRepo,
        BookingRepository $bookingRepo,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository

    ): Response {
        //session
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        // ✅ Récupération de l'événement
        $event = $em->getRepository(Event::class)->find($eventId);
        if (!$event || $event->getUser() !== $user) {
            throw $this->createAccessDeniedException("Unauthorized");
        }

        /*if (!$event || ($event->getUser() !== ($this->getUser() ?? $em->getRepository(\App\Entity\User::class)->find(49)))) {
            throw $this->createAccessDeniedException("Unauthorized");
        }*/

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
            'queryMinPrice' => $queryMinPrice,
            'tomtomApiKey' => $this->tomtomApiKey
        ]);
    }

    #[Route('/suggested/ajax/{eventId}', name: 'suggested_locations_ajax', methods: ['GET'])]
    public function suggestedAjax(
        int $eventId,
        Request $request,
        LocationRepository $locationRepo,
        BookingRepository $bookingRepo,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        //session
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        $event = $em->getRepository(Event::class)->find($eventId);
        if (!$event || $event->getUser() !== $user) {
            return new Response("Unauthorized or event not found", 403);
        }
        /*if (!$event) {
            return new Response("Event not found", 404);
        }*/

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

    #[Route('/suggested/mapdata/{eventId}', name: 'suggested_locations_mapdata', methods: ['GET'])]
    public function mapData(
        int $eventId,
        LocationRepository $locationRepo,
        EntityManagerInterface $em,
        SessionInterface $session,
        UserRepository $userRepository
    ): JsonResponse {
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);
        $event = $em->getRepository(Event::class)->find($eventId);

        if (!$event || $event->getUser() !== $user) {
            return new JsonResponse(['error' => 'Unauthorized'], 403);
        }

        $locations = $locationRepo->findAll();
        $data = [];

        foreach ($locations as $loc) {
            $cityName = ucfirst(strtolower($loc->getCity()->value));
            $coords = $this->geocodeCity($cityName);
            if ($coords['lat'] === null || $coords['lng'] === null) {
                continue;
            }

            $data[] = [
                'id' => $loc->getIdLocation(),
                'name' => $loc->getName(),
                'lat' => $coords['lat'],
                'lng' => $coords['lng'],
                'city' => $loc->getCity()->value,
                'price' => $loc->getPrice(),
                'image' => $loc->getImageFilename() ? '/uploads/' . $loc->getImageFilename() : '/images/default.jpg',
            ];
        }


        return new JsonResponse($data);
    }

    private function geocodeCity(string $city): array
    {
        //$apiKey = '';
        $cityEncoded = urlencode($city . ', Tunisia');
        $url = "https://api.tomtom.com/search/2/geocode/{$cityEncoded}.json?key=" . $this->tomtomApiKey;

        $response = file_get_contents($url);
        $json = json_decode($response, true);

        if (!empty($json['results'][0]['position'])) {
            return [
                'lat' => $json['results'][0]['position']['lat'],
                'lng' => $json['results'][0]['position']['lon']
            ];
        }

        return ['lat' => null, 'lng' => null];
    }

    #[Route('/{id}', name: 'location_show', methods: ['GET'])]
    public function show(
        int $id,
        LocationRepository $locationRepo,
        SessionInterface $session,
        UserRepository $userRepository
    ): Response {
        $userId = $session->get('user_id');
        $user = $userRepository->find($userId);

        $location = $locationRepo->find($id);

        if (!$location) {
            throw $this->createNotFoundException('Location not found');
        }

        if ($location->getImageData()) {
            $location->base64Image = base64_encode(stream_get_contents($location->getImageData()));
        }

        return $this->render('location/showLocation.html.twig', [
            'location' => $location,
            'user' => $user,
            'tomtomApiKey' => $this->tomtomApiKey
        ]);
    }



}
