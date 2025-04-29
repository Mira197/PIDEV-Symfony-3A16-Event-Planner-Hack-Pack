<?php

namespace App\Repository;

use App\Entity\User;
use App\Entity\WalletTransaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<WalletTransaction>
 *
 * @method WalletTransaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method WalletTransaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method WalletTransaction[]    findAll()
 * @method WalletTransaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class WalletTransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, WalletTransaction::class);
    }

    /**
     * Calculer le solde total du wallet pour un utilisateur donné
     */
    public function getUserWalletBalance(int $userId): float
    {
        $qb = $this->createQueryBuilder('w')
            ->select('SUM(w.amount)')
            ->where('w.user = :userId')
            ->setParameter('userId', $userId);

        return (float) $qb->getQuery()->getSingleScalarResult();
    }

    public function getAvailableCredit(User $user): float
    {
        $qb = $this->createQueryBuilder('w')
            ->select('SUM(w.amount)')
            ->where('w.user = :user')
            ->setParameter('user', $user);

        $result = $qb->getQuery()->getSingleScalarResult();

        return $result ? (float) $result : 0.0;
    }
    public function calculateWalletBalance(User $user): float
    {
        // Récupérer les dépôts
        $deposits = $this->createQueryBuilder('w')
            ->select('SUM(w.amount)')
            ->where('w.user = :user AND w.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'deposit')
            ->getQuery()
            ->getSingleScalarResult();
    
        // Récupérer les paiements
        $payments = $this->createQueryBuilder('w')
            ->select('SUM(w.amount)')
            ->where('w.user = :user AND w.type = :type')
            ->setParameter('user', $user)
            ->setParameter('type', 'payment')
            ->getQuery()
            ->getSingleScalarResult();
    
        $deposits = (float) ($deposits ?? 0.0);
        $payments = (float) ($payments ?? 0.0);
        $balance = $deposits - $payments;
    
        // Afficher dans la console
        // echo "User {$user->getId()} - Deposits: $deposits, Payments: $payments, Balance: $balance\n";
    
        return $balance;
    }
}
