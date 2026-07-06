<?php

namespace App\Repository;

use App\Entity\DeliveryPointTimeWindow;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<DeliveryPointTimeWindow>
 */
class DeliveryPointTimeWindowRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DeliveryPointTimeWindow::class);
    }
}
