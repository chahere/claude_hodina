<?php

namespace App\Repository;

use App\Entity\CourierPayoutLine;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CourierPayoutLine>
 */
class CourierPayoutLineRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CourierPayoutLine::class);
    }
}
