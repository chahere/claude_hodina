<?php

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Entity\HodinaSetting;
use Doctrine\ORM\EntityManagerInterface;

class OrderReferenceGenerator
{
    private const MAX_REFERENCE_ATTEMPTS = 100;

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function ensureReference(CustomerOrder $order): string
    {
        if ($order->getOrderReference()) {
            return $order->getOrderReference();
        }

        $date = $order->getSubmittedAt() ?? new \DateTimeImmutable();
        $prefix = $this->getPrefix();
        $datePart = $date->format('Ymd');
        $dailyNumber = $this->resolveNextDailyNumber($prefix, $datePart);

        for ($attempt = 0; $attempt < self::MAX_REFERENCE_ATTEMPTS; ++$attempt) {
            $reference = $prefix . $datePart . $dailyNumber;

            if (!$this->referenceExists($reference, $order)) {
                $order
                    ->setOrderReference($reference)
                    ->setDailyOrderNumber($dailyNumber)
                    ->setOrderReferenceDate($date);

                return $reference;
            }

            ++$dailyNumber;
        }

        throw new \RuntimeException('Impossible de générer une référence de commande unique.');
    }

    private function resolveNextDailyNumber(string $prefix, string $datePart): int
    {
        $pattern = $prefix . $datePart . '%';

        $maxDailyNumber = (int) $this->em->getRepository(CustomerOrder::class)
            ->createQueryBuilder('o')
            ->select('COALESCE(MAX(o.dailyOrderNumber), 0)')
            ->where('o.orderReference LIKE :pattern')
            ->setParameter('pattern', $pattern)
            ->getQuery()
            ->getSingleScalarResult();

        return max(1, $maxDailyNumber + 1);
    }

    private function referenceExists(string $reference, CustomerOrder $order): bool
    {
        $qb = $this->em->getRepository(CustomerOrder::class)
            ->createQueryBuilder('o')
            ->select('COUNT(o.id)')
            ->where('o.orderReference = :reference')
            ->setParameter('reference', $reference);

        if ($order->getId() !== null) {
            $qb
                ->andWhere('o.id != :orderId')
                ->setParameter('orderId', $order->getId());
        }

        return (int) $qb->getQuery()->getSingleScalarResult() > 0;
    }

    public function getPrefix(): string
    {
        $setting = $this->em->getRepository(HodinaSetting::class)->findOneBy([
            'settingKey' => HodinaSetting::KEY_ORDER_REFERENCE_PREFIX,
        ]);

        $prefix = $setting?->getValueOrDefault('hodina') ?? 'hodina';
        $prefix = trim($prefix);

        return $prefix !== '' ? $prefix : 'hodina';
    }
}
