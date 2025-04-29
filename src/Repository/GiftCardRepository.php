<?php

namespace App\Repository;

use App\Entity\GiftCard;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<GiftCard>
 *
 * @method GiftCard|null find($id, $lockMode = null, $lockVersion = null)
 * @method GiftCard|null findOneBy(array $criteria, array $orderBy = null)
 * @method GiftCard[]    findAll()
 * @method GiftCard[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class GiftCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, GiftCard::class);
    }

    public function validateGiftCard(string $code, string $pin): ?GiftCard
{
    // Récupérer la carte cadeau avec le code et le pin
    $giftCard = $this->createQueryBuilder('g')
        ->where('g.code = :code')
        ->andWhere('g.pin = :pin')
        ->andWhere('g.isUsed = false')
        ->setParameter('code', $code)
        ->setParameter('pin', $pin)
        ->getQuery()
        ->getOneOrNullResult();

    // Si la carte cadeau est trouvée, on la marque comme utilisée
    if ($giftCard) {
        $giftCard->setIsUsed(true);
        
        // Utiliser un objet DateTime pour `usedAt` directement
        $giftCard->setUsedAt(new \DateTime()); // Correctement utiliser un objet DateTime ici

        // Persister les modifications
        $this->_em->flush();
    }

    return $giftCard; // Retourne la carte cadeau (null si non trouvée ou invalide)
}

}
