<?php

namespace App\Repository;
use App\Entity\Location;
use App\Entity\Booking;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
//use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Query\Parameter;

/**
 * @extends ServiceEntityRepository<Booking>
 */
class BookingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Booking::class);
    }

    // ✅ Ajoute cette méthode ici :
    public function findConflicts(Location $location, \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('b')
            ->andWhere('b.location = :location')
            ->andWhere('b.start_date < :end')
            ->andWhere('b.end_date > :start')
            ->setParameters(new \Doctrine\Common\Collections\ArrayCollection([
                new Parameter('location', $location),
                new Parameter('start', $start),
                new Parameter('end', $end),
            ]))
            ->getQuery()
            ->getResult();
    }

    public function searchByKeyword(string $keyword): array
    {
        return $this->createQueryBuilder('b')
            ->leftJoin('b.event', 'e')
            ->leftJoin('b.location', 'l')
            ->addSelect('e', 'l')
            ->where('e.name LIKE :kw OR l.name LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%')
            ->getQuery()
            ->getResult();
    }


    //    /**
//     * @return Booking[] Returns an array of Booking objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('b.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

    //    public function findOneBySomeField($value): ?Booking
//    {
//        return $this->createQueryBuilder('b')
//            ->andWhere('b.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
