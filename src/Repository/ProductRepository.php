<?php

namespace App\Repository;

use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }
    public function searchByKeyword(string $keyword): \Doctrine\ORM\QueryBuilder
    {
        return $this->createQueryBuilder('p')
            ->where('p.name LIKE :kw OR p.description LIKE :kw OR p.category LIKE :kw')
            ->setParameter('kw', '%' . $keyword . '%')
            ->orderBy('p.name', 'ASC');
    }
    // src/Repository/ProductRepository.php

    public function findAvailableProductsOrderedByStockRatio(): array
    {
        return $this->createQueryBuilder('p')
            ->innerJoin('p.stock', 's') // 🔥 faire une jointure avec Stock
            ->where('s.available_quantity > 0') // 🔥 vérifier disponible
            ->addSelect(
                'CASE WHEN s.minimum_quantity > 0 THEN (s.available_quantity / s.minimum_quantity) ELSE 999999 END AS HIDDEN stock_ratio'
            )
            ->orderBy('stock_ratio', 'ASC') // 🔥 tri par ratio croissant
            ->getQuery()
            ->getResult();
    }
    
//    /**
//     * @return Product[] Returns an array of Product objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('p.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?Product
//    {
//        return $this->createQueryBuilder('p')
//            ->andWhere('p.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
