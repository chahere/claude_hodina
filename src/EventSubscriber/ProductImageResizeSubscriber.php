<?php

namespace App\EventSubscriber;

use App\Entity\ProductImage;
use App\Service\ImageResizer;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityPersistedEvent;
use EasyCorp\Bundle\EasyAdminBundle\Event\AfterEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductImageResizeSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly ImageResizer $resizer,
        private readonly string $projectDir,
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            AfterEntityPersistedEvent::class => ['onAfterSave'],
            AfterEntityUpdatedEvent::class => ['onAfterSave'],
        ];
    }

    public function onAfterSave(object $event): void
    {
        $entity = $event->getEntityInstance();

        if (!$entity instanceof ProductImage) {
            return;
        }

        $filename = $entity->getPath();
        if (!$filename) {
            return;
        }

        $abs = $this->projectDir . DIRECTORY_SEPARATOR . 'public' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . $filename;

        $this->resizer->cropSquareAndResize($abs, 800);
    }
}
