<?php

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserRepository extends ServiceEntityRepository
{
    
    private $passwordHasher;

    public function __construct(ManagerRegistry $registry, UserPasswordHasherInterface $passwordHasher)
    {
        parent::__construct($registry, User::class);
        $this->passwordHasher = $passwordHasher;
    }

    public function findUserByEmailAndPassword(string $email, string $password)
    {
        $user = $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$user) {
            return false; // Utilisateur non trouvé
        }

        if (!$this->passwordHasher->isPasswordValid($user, $password)) {
            return false; // Mot de passe incorrect
        }

        return $user;
    }

    public function findByCriteria($criteria): array
    {
        $queryBuilder = $this->createQueryBuilder('u');

        if (!empty($criteria['lastName'])) {
            $queryBuilder->andWhere('u.lastName LIKE :lastName')
                ->setParameter('lastName', '%' . $criteria['lastName'] . '%');
        }

        if (!empty($criteria['firstName'])) {
            $queryBuilder->andWhere('u.firstName LIKE :firstName')
                ->setParameter('firstName', '%' . $criteria['firstName'] . '%');
        }

        if (!empty($criteria['role'])) {
            $queryBuilder->andWhere('u.role = :role')
                ->setParameter('role', $criteria['role']);
        }

        if (!empty($criteria['numtel'])) {
            $queryBuilder->andWhere('u.numTel = :numtel')
                ->setParameter('numtel', $criteria['numtel']);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    public function findBySearchQuery($searchQuery)
    {
        return $this->createQueryBuilder('u')
            ->andWhere('u.lastName LIKE :query OR u.firstName LIKE :query OR u.role LIKE :query OR u.numtel LIKE :query')
            ->setParameter('query', '%' . $searchQuery . '%')
            ->getQuery()
            ->getResult();
    }

    public function countUsersByRole(): array
    {
        return $this->createQueryBuilder('u')
            ->select('u.role, COUNT(u.idUser) AS userCount')
            ->groupBy('u.role')
            ->getQuery()
            ->getResult();
    }
    
    public function findOneByEmail(string $email): ?User
    {
        return $this->createQueryBuilder('u')
            ->where('u.email = :email')
            ->setParameter('email', $email)
            ->getQuery()
            ->getOneOrNullResult(); // Renvoyer un seul utilisateur ou null
    }
}
