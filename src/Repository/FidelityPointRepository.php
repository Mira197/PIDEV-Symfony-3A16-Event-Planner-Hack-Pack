<?php

namespace App\Repository;

use App\Entity\FidelityPoint;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class FidelityPointRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, FidelityPoint::class);
    }

    public function getUserPoints(User $user): int
    {
        // Récupérer les points gagnés
        $earned = $this->createQueryBuilder('p')
            ->select('SUM(p.points)')
            ->where('p.user = :user AND p.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'earned')
            ->getQuery()
            ->getSingleScalarResult();

        // Récupérer les points utilisés
        $used = $this->createQueryBuilder('p')
            ->select('SUM(p.points)')
            ->where('p.user = :user AND p.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'used')
            ->getQuery()
            ->getSingleScalarResult();

        // Retourner la différence entre les points gagnés et utilisés
        return (int) ($earned ?? 0) - (int) ($used ?? 0);
    }
}
