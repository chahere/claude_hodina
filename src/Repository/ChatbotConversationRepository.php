<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ChatbotConversation;
use App\Entity\Customer;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ChatbotConversationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ChatbotConversation::class);
    }

    public function findActiveForCustomer(Customer $customer): ?ChatbotConversation
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.customer = :customer')
            ->andWhere('c.status = :status')
            ->setParameter('customer', $customer)
            ->setParameter('status', ChatbotConversation::STATUS_ACTIVE)
            ->orderBy('c.createdAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
