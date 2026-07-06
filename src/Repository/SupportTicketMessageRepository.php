<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SupportTicketMessage;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class SupportTicketMessageRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SupportTicketMessage::class);
    }
}
