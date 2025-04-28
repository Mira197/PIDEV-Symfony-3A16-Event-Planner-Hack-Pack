<?php

namespace App\Controller\admin;

use App\Repository\EventRepository;
use App\Repository\LocationRepository;
use App\Repository\BookingRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\Persistence\ManagerRegistry; // Ajout de ManagerRegistry pour la connexion à la base de données

class StatisticsEventsController extends AbstractController
{
    #[Route('/admin/events/statistics', name: 'admin_events_statistics')]
    public function index(EventRepository $eventRepo, LocationRepository $locationRepo, BookingRepository $bookingRepo, ManagerRegistry $doctrine): Response
    {
        $totalEvents = $eventRepo->count([]);
        $totalLocations = $locationRepo->count([]);
        $totalBookings = $bookingRepo->count([]);

        // Taux d'occupation des lieux
        $occupationRate = $totalLocations > 0 ? round(($totalBookings / $totalLocations) * 100, 2) : 0;

        // Événements Booked vs Non Booked
        // Booked Events = nombre unique d'événements qui ont au moins 1 réservation
        $eventsBooked = $bookingRepo->createQueryBuilder('b')
            ->select('COUNT(DISTINCT b.event)')
            ->getQuery()
            ->getSingleScalarResult();

        // Non Booked = total events - events booked
        $eventsNotBooked = $totalEvents - $eventsBooked;

        // 📈 Bookings par mois
        $connection = $doctrine->getConnection(); // ✅ au lieu de $this->getDoctrine()
        $sql = '
        SELECT MONTH(start_date) AS month, COUNT(*) AS count
        FROM booking
        GROUP BY month
        ORDER BY month ASC
        ';
        $stmt = $connection->prepare($sql);
        $resultSet = $stmt->executeQuery();
        $monthlyBookings = $resultSet->fetchAllAssociative();



        // On prépare un tableau vide avec 12 mois
        $bookingsPerMonth = array_fill(0, 12, 0);
        foreach ($monthlyBookings as $booking) {
            $monthIndex = (int) $booking['month'] - 1; // 🔥 Corrige ici aussi (Janvier=0)
            $bookingsPerMonth[$monthIndex] = (int) $booking['count'];
        }
        // Distribution des prix
        $sqlPrice = "
        SELECT 
            CASE 
                WHEN price BETWEEN 0 AND 100 THEN '0-100'
                WHEN price BETWEEN 100 AND 200 THEN '100-200'
                WHEN price BETWEEN 200 AND 500 THEN '200-500'
                WHEN price BETWEEN 500 AND 1000 THEN '500-1000'
            ELSE '1000+' 
            END AS priceRange,
            COUNT(*) AS count
        FROM location
        GROUP BY priceRange
        ORDER BY priceRange
        ";
        $stmtPrice = $connection->prepare($sqlPrice);
        $priceResultSet = $stmtPrice->executeQuery();
        $priceDistribution = $priceResultSet->fetchAllAssociative();

        // Distribution des capacités
        $sqlCapacity = "
        SELECT 
            CASE 
                WHEN capacity BETWEEN 0 AND 50 THEN '0-50'
                WHEN capacity BETWEEN 50 AND 100 THEN '50-100'
                WHEN capacity BETWEEN 200 AND 500 THEN '200-500'
            ELSE '500+'
            END AS capacityRange,
            COUNT(*) AS count
            FROM location
            GROUP BY capacityRange
            ORDER BY capacityRange
        ";
        $stmtCapacity = $connection->prepare($sqlCapacity);
        $capacityResultSet = $stmtCapacity->executeQuery();
        $capacityDistribution = $capacityResultSet->fetchAllAssociative();

        // 🕒 Statistiques de statut de réservation
        $bookings = $bookingRepo->findAll();
        $now = new \DateTime();
        $completedBookings = 0;
        $upcomingBookings = 0;
        $inProgressBookings = 0;

        foreach ($bookings as $booking) {
            if ($booking->getStartDate() > $now) {
                $upcomingBookings++;
            } elseif ($booking->getEndDate() < $now) {
                $completedBookings++;
            } else {
                $inProgressBookings++;
            }
        }


        // 🎯 Top 5 Locations les plus réservées
        $topLocations = $bookingRepo->createQueryBuilder('b')
            ->select('l.name as locationName, COUNT(b.id_booking) as bookingsCount')
            ->join('b.location', 'l')
            ->groupBy('l.id_location')
            ->orderBy('bookingsCount', 'DESC')
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();



        return $this->render('admin/event_admin/statisticsEvents.html.twig', [
            'totalEvents' => $totalEvents,
            'totalLocations' => $totalLocations,
            'totalBookings' => $totalBookings,
            'occupationRate' => $occupationRate,
            'eventsBooked' => $eventsBooked,
            'eventsNotBooked' => $eventsNotBooked,
            //'monthlyBookings' => $monthlyBookings,
            'bookingsPerMonth' => $bookingsPerMonth,
            'topLocations' => $topLocations,
            'priceDistribution' => $priceDistribution,
            'capacityDistribution' => $capacityDistribution,
            'completedBookings' => $completedBookings,
            'upcomingBookings' => $upcomingBookings,
            'inProgressBookings' => $inProgressBookings,
        ]);
    }
}
