<?php
// src/Service/DashboardStatsService.php
namespace App\Service;

use App\Repository\OrderRepository;
use App\Repository\UserRepository;
use App\Repository\WalletTransactionRepository;
use Doctrine\ORM\EntityManagerInterface;

class DashboardStatsService
{
    private $em;

    public function __construct(EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    public function getNewOrdersCount(): int
    {
        return $this->em->createQuery('
            SELECT COUNT(o.order_id)
            FROM App\Entity\Order o
            WHERE o.status != :cancelled
        ')
        ->setParameter('cancelled', 'CANCELLED')
        ->getSingleScalarResult();
    }

    public function getTotalIncome(): float
    {
        return (float) $this->em->createQuery('
            SELECT SUM(o.total_price)
            FROM App\Entity\Order o
            WHERE o.status IN (:statuses)
        ')
        ->setParameter('statuses', ['CONFIRMED', 'DELIVERED'])
        ->getSingleScalarResult() ?? 0.0;
    }

    public function getTotalExpense(): float
    {
        return (float) $this->em->createQuery('
            SELECT SUM(w.amount)
            FROM App\Entity\WalletTransaction w
            WHERE w.type = :payment
        ')
        ->setParameter('payment', 'payment')
        ->getSingleScalarResult() ?? 0.0;
    }

    
}
