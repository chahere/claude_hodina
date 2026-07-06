<?php

namespace App\Repository;

use App\Entity\AddressLocality;
use App\Entity\DeliveryCommune;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AddressLocality>
 */
class AddressLocalityRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AddressLocality::class);
    }

    /** @return list<AddressLocality> */
    public function findActiveForCheckout(): array
    {
        return $this->createQueryBuilder('locality')
            ->leftJoin('locality.deliveryCommune', 'commune')
            ->addSelect('commune')
            ->andWhere('locality.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('locality.sortOrder', 'ASC')
            ->addOrderBy('locality.name', 'ASC')
            ->getQuery()
            ->getResult();
    }

    public function findOneActiveCompatible(string $name, ?DeliveryCommune $deliveryCommune = null, ?string $postalCode = null): ?AddressLocality
    {
        $normalizedName = AddressLocality::normalizeName($name);
        if ($normalizedName === '') {
            return null;
        }

        $qb = $this->createQueryBuilder('locality')
            ->leftJoin('locality.deliveryCommune', 'commune')
            ->addSelect('commune')
            ->andWhere('locality.isActive = :active')
            ->andWhere('locality.normalizedName = :normalizedName')
            ->setParameter('active', true)
            ->setParameter('normalizedName', $normalizedName)
            ->setMaxResults(2);

        if ($deliveryCommune instanceof DeliveryCommune) {
            $qb->andWhere('locality.deliveryCommune = :deliveryCommune')
                ->setParameter('deliveryCommune', $deliveryCommune);
        }

        if ($postalCode !== null && trim($postalCode) !== '') {
            $qb->andWhere('(locality.postalCode IS NULL OR locality.postalCode = :postalCode)')
                ->setParameter('postalCode', trim($postalCode));
        }

        $results = $qb->getQuery()->getResult();

        return count($results) === 1 ? $results[0] : null;
    }
}
