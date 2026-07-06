<?php

namespace App\EventSubscriber;

use App\Entity\ProductImage;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityPersistedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductImagePositionSubscriber implements EventSubscriberInterface
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeEntityPersistedEvent::class => ['onBeforePersist'],
        ];
    }

    public function onBeforePersist(BeforeEntityPersistedEvent $event): void
    {
        $entity = $event->getEntityInstance();
        if (!$entity instanceof ProductImage) {
            return;
        }

        // Si déjà défini, on ne touche pas
        if ($entity->getPosition() !== null) {
            return;
        }

        $product = $entity->getProduct();
        if (!$product) {
            $entity->setPosition(0);
            return;
        }

        // max(position) pour ce produit
        $qb = $this->em->createQueryBuilder();
        $max = $qb->select('COALESCE(MAX(pi.position), -1)')
            ->from(ProductImage::class, 'pi')
            ->where('pi.product = :p')
            ->setParameter('p', $product)
            ->getQuery()
            ->getSingleScalarResult();

        $entity->setPosition(((int) $max) + 1);
    }
}
