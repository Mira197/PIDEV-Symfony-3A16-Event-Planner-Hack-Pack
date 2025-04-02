<?php

namespace App\Repository;

use App\Entity\CartProduct;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class CartProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CartProduct::class);
    }

    // Exemple : trouver les produits d’un panier donné
    public function findByCartId(int $cartId): array
    {
        return $this->createQueryBuilder('cp')
            ->join('cp.cart', 'c')
            ->where('c.cart_id = :cartId')
            ->setParameter('cartId', $cartId)
            ->getQuery()
            ->getResult();
    }
}
