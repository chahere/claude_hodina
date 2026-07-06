<?php

namespace App\Service;

use App\Entity\CourierPayout;
use App\Entity\CourierPayoutLine;
use App\Entity\Customer;
use App\Entity\CustomerOrder;
use Doctrine\ORM\EntityManagerInterface;

final class CourierPayoutService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, due: \DateTimeImmutable, label: string}
     */
    public function getCurrentPeriod(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now');
        $day = (int) $now->format('d');

        if ($day <= 15) {
            $start = $now->modify('first day of this month')->setTime(0, 0, 0);
            $end = $start->setDate((int) $start->format('Y'), (int) $start->format('m'), 15)->setTime(23, 59, 59);
            $due = $end;
        } else {
            $start = $now->setDate((int) $now->format('Y'), (int) $now->format('m'), 16)->setTime(0, 0, 0);
            $end = $now->modify('last day of this month')->setTime(23, 59, 59);
            $due = $this->buildSecondHalfDueDate($now);
        }

        return $this->formatPeriod($start, $end, $due);
    }

    /**
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, due: \DateTimeImmutable, label: string}
     */
    public function getPreviousPeriod(?\DateTimeImmutable $now = null): array
    {
        $now ??= new \DateTimeImmutable('now');
        $day = (int) $now->format('d');

        if ($day <= 15) {
            $previousMonth = $now->modify('first day of previous month');
            $start = $previousMonth->setDate((int) $previousMonth->format('Y'), (int) $previousMonth->format('m'), 16)->setTime(0, 0, 0);
            $end = $previousMonth->modify('last day of this month')->setTime(23, 59, 59);
            $due = $this->buildSecondHalfDueDate($previousMonth);
        } else {
            $start = $now->modify('first day of this month')->setTime(0, 0, 0);
            $end = $start->setDate((int) $start->format('Y'), (int) $start->format('m'), 15)->setTime(23, 59, 59);
            $due = $end;
        }

        return $this->formatPeriod($start, $end, $due);
    }

    /**
     * Génère ou complète les rémunérations DRAFT d'une période.
     * Les paiements VALIDATED/PAID/CANCELED ne sont jamais recalculés.
     * Une commande déjà présente dans une ligne de rémunération n'est jamais reprise.
     *
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, due: \DateTimeImmutable, label: string} $period
     * @return array{created: int, updated: int, skippedOrders: int, lines: int, payouts: list<CourierPayout>}
     */
    public function generateForPeriod(array $period): array
    {
        $ordersByCourier = $this->findDeliveredOrdersByCourier($period['start'], $period['end']);
        $created = 0;
        $updated = 0;
        $skippedOrders = 0;
        $lines = 0;
        $payouts = [];

        foreach ($ordersByCourier as $bundle) {
            $courier = $bundle['courier'];
            $orders = $bundle['orders'];

            if (!$courier instanceof Customer || $orders === []) {
                continue;
            }

            $payout = $this->findPayoutForCourierAndPeriod($courier, $period['start'], $period['end']);
            $isNew = false;

            if (!$payout instanceof CourierPayout) {
                $payout = (new CourierPayout())
                    ->setCourier($courier)
                    ->setPeriodStart($period['start'])
                    ->setPeriodEnd($period['end'])
                    ->setPaymentDueDate($period['due']);
                $isNew = true;
            }

            if ($payout->getStatus() !== CourierPayout::STATUS_DRAFT) {
                $skippedOrders += count($orders);
                continue;
            }

            $lineAdded = false;
            foreach ($orders as $order) {
                if ($this->findPayoutLineForOrder($order) instanceof CourierPayoutLine) {
                    ++$skippedOrders;
                    continue;
                }

                $line = $this->buildLineFromOrder($payout, $order);
                $payout->addLine($line);
                $this->entityManager->persist($line);
                $lineAdded = true;
                ++$lines;
            }

            if ($lineAdded) {
                if ($isNew) {
                    $this->entityManager->persist($payout);
                }

                $payout->recalculateTotals();
                $payouts[] = $payout;
                $isNew ? ++$created : ++$updated;
            }
        }

        $this->entityManager->flush();

        return [
            'created' => $created,
            'updated' => $updated,
            'skippedOrders' => $skippedOrders,
            'lines' => $lines,
            'payouts' => $payouts,
        ];
    }

    /**
     * Simule la génération d'une période sans écrire en base.
     *
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, due: \DateTimeImmutable, label: string} $period
     * @return array{created: int, updated: int, skippedOrders: int, lines: int, payouts: list<array{courierId: int|null, courierLabel: string, ordersCount: int, totalAmount: float, mode: string}>}
     */
    public function previewGenerationForPeriod(array $period): array
    {
        $ordersByCourier = $this->findDeliveredOrdersByCourier($period['start'], $period['end']);
        $created = 0;
        $updated = 0;
        $skippedOrders = 0;
        $lines = 0;
        $payouts = [];

        foreach ($ordersByCourier as $bundle) {
            $courier = $bundle['courier'];
            $orders = $bundle['orders'];

            if (!$courier instanceof Customer || $orders === []) {
                continue;
            }

            $existingPayout = $this->findPayoutForCourierAndPeriod($courier, $period['start'], $period['end']);
            if ($existingPayout instanceof CourierPayout && $existingPayout->getStatus() !== CourierPayout::STATUS_DRAFT) {
                $skippedOrders += count($orders);
                continue;
            }

            $ordersCount = 0;
            $total = 0.0;

            foreach ($orders as $order) {
                if ($this->findPayoutLineForOrder($order) instanceof CourierPayoutLine) {
                    ++$skippedOrders;
                    continue;
                }

                ++$ordersCount;
                ++$lines;
                $total += $this->getCourierPayoutForOrder($order);
            }

            if ($ordersCount === 0) {
                continue;
            }

            $isNew = !$existingPayout instanceof CourierPayout;
            $isNew ? ++$created : ++$updated;

            $payouts[] = [
                'courierId' => $courier->getId(),
                'courierLabel' => $this->formatCustomerLabel($courier),
                'ordersCount' => $ordersCount,
                'totalAmount' => round($total, 2),
                'mode' => $isNew ? 'create' : 'update',
            ];
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skippedOrders' => $skippedOrders,
            'lines' => $lines,
            'payouts' => $payouts,
        ];
    }

    /**
     * Estimation de la période en cours affichée au livreur, sans créer de paiement.
     * Elle s'appuie uniquement sur les commandes DELIVERED rattachées au livreur.
     *
     * @return array{periodLabel: string, paymentDueLabel: string, totalAmount: float, ordersCount: int, lines: list<array{orderReference: string, deliveredAtLabel: string, commune: string, amount: float}>}
     */
    public function buildCurrentEstimateForCourier(Customer $courier): array
    {
        $period = $this->getCurrentPeriod();
        $orders = $this->findDeliveredOrdersForCourier($courier, $period['start'], $period['end']);
        $lines = [];
        $total = 0.0;

        foreach ($orders as $order) {
            $amount = $this->getCourierPayoutForOrder($order);
            $total += $amount;
            $lines[] = [
                'orderReference' => $order->getOrderReference() ?: ('Commande #' . $order->getId()),
                'deliveredAtLabel' => $this->formatDateTime($order->getDeliveredAt()),
                'commune' => trim((string) $order->getDeliveryAddressCommune()) ?: 'Commune à confirmer',
                'amount' => $amount,
            ];
        }

        return [
            'periodLabel' => $period['label'],
            'paymentDueLabel' => $period['due']->format('d/m/Y'),
            'totalAmount' => round($total, 2),
            'ordersCount' => count($orders),
            'lines' => $lines,
        ];
    }

    /** @return list<CourierPayout> */
    public function findPayoutsForCourier(Customer $courier, array $statuses, int $limit = 10): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('payout')
            ->from(CourierPayout::class, 'payout')
            ->andWhere('payout.courier = :courier')
            ->andWhere('payout.status IN (:statuses)')
            ->setParameter('courier', $courier)
            ->setParameter('statuses', $statuses)
            ->orderBy('payout.periodStart', 'DESC')
            ->addOrderBy('payout.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return array<int, array{courier: Customer, orders: list<CustomerOrder>}>
     */
    private function findDeliveredOrdersByCourier(\DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd): array
    {
        $orders = $this->entityManager->createQueryBuilder()
            ->select('o', 'courier', 'customer')
            ->from(CustomerOrder::class, 'o')
            ->innerJoin('o.assignedCourier', 'courier')->addSelect('courier')
            ->leftJoin('o.customer', 'customer')->addSelect('customer')
            ->andWhere('o.status = :status')
            ->andWhere('o.deliveredAt >= :periodStart')
            ->andWhere('o.deliveredAt <= :periodEnd')
            ->setParameter('status', CustomerOrder::STATUS_DELIVERED)
            ->setParameter('periodStart', $periodStart)
            ->setParameter('periodEnd', $periodEnd)
            ->orderBy('o.deliveredAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();

        $byCourier = [];
        foreach ($orders as $order) {
            if (!$order instanceof CustomerOrder || !$order->getAssignedCourier() instanceof Customer) {
                continue;
            }

            $courier = $order->getAssignedCourier();
            $courierId = $courier->getId();
            if ($courierId === null) {
                continue;
            }

            $byCourier[$courierId] ??= ['courier' => $courier, 'orders' => []];
            $byCourier[$courierId]['orders'][] = $order;
        }

        return $byCourier;
    }

    /** @return list<CustomerOrder> */
    private function findDeliveredOrdersForCourier(Customer $courier, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd): array
    {
        return $this->entityManager->createQueryBuilder()
            ->select('o')
            ->from(CustomerOrder::class, 'o')
            ->andWhere('o.status = :status')
            ->andWhere('o.assignedCourier = :courier')
            ->andWhere('o.deliveredAt >= :periodStart')
            ->andWhere('o.deliveredAt <= :periodEnd')
            ->setParameter('status', CustomerOrder::STATUS_DELIVERED)
            ->setParameter('courier', $courier)
            ->setParameter('periodStart', $periodStart)
            ->setParameter('periodEnd', $periodEnd)
            ->orderBy('o.deliveredAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();
    }

    private function findPayoutForCourierAndPeriod(Customer $courier, \DateTimeImmutable $periodStart, \DateTimeImmutable $periodEnd): ?CourierPayout
    {
        return $this->entityManager->getRepository(CourierPayout::class)->findOneBy([
            'courier' => $courier,
            'periodStart' => $periodStart,
            'periodEnd' => $periodEnd,
        ]);
    }

    private function findPayoutLineForOrder(CustomerOrder $order): ?CourierPayoutLine
    {
        return $this->entityManager->getRepository(CourierPayoutLine::class)->findOneBy([
            'customerOrder' => $order,
        ]);
    }

    private function buildLineFromOrder(CourierPayout $payout, CustomerOrder $order): CourierPayoutLine
    {
        $amount = $this->getCourierPayoutForOrder($order);
        $snapshot = $this->getDeliveryLogisticsPreviewSnapshot($order);

        return (new CourierPayoutLine())
            ->setCourierPayout($payout)
            ->setCustomerOrder($order)
            ->setOrderReference($order->getOrderReference() ?: ('Commande #' . $order->getId()))
            ->setDeliveredAt($order->getDeliveredAt() ?? new \DateTimeImmutable())
            ->setCustomerCommune(trim((string) $order->getDeliveryAddressCommune()) ?: null)
            ->setCourierPayoutAmount($amount)
            ->setDeliveryFeeCustomer($order->getDeliveryFee())
            ->setSnapshot([
                'orderId' => $order->getId(),
                'orderReference' => $order->getOrderReference(),
                'deliveredAt' => $order->getDeliveredAt()?->format(\DateTimeInterface::ATOM),
                'customerCommune' => $order->getDeliveryAddressCommune(),
                'customerTotal' => (float) $order->getTotal(),
                'customerDeliveryFee' => (float) $order->getDeliveryFee(),
                'courierPayoutAmount' => $amount,
                'logisticsPreview' => $snapshot,
            ]);
    }

    public function getCourierPayoutForOrder(CustomerOrder $order): float
    {
        $snapshot = $this->getDeliveryLogisticsPreviewSnapshot($order);

        if (isset($snapshot['estimatedCourierPayout'])) {
            return max(0.0, round((float) $snapshot['estimatedCourierPayout'], 2));
        }

        if (isset($snapshot['courierPayout'])) {
            return max(0.0, round((float) $snapshot['courierPayout'], 2));
        }

        return max(0.0, round((float) $order->getDeliveryFee(), 2));
    }

    /** @return array<string, mixed> */
    private function getDeliveryLogisticsPreviewSnapshot(CustomerOrder $order): array
    {
        $snapshot = $order->getDeliveryLogisticsSnapshot();
        if (!is_array($snapshot)) {
            return [];
        }

        $preview = $snapshot['preview'] ?? null;
        if (is_array($preview)) {
            return $preview;
        }

        return $snapshot;
    }

    private function buildSecondHalfDueDate(\DateTimeImmutable $monthDate): \DateTimeImmutable
    {
        $lastDay = (int) $monthDate->modify('last day of this month')->format('d');
        $dueDay = min(30, $lastDay);

        return $monthDate
            ->setDate((int) $monthDate->format('Y'), (int) $monthDate->format('m'), $dueDay)
            ->setTime(23, 59, 59);
    }

    /**
     * @return array{start: \DateTimeImmutable, end: \DateTimeImmutable, due: \DateTimeImmutable, label: string}
     */
    private function formatPeriod(\DateTimeImmutable $start, \DateTimeImmutable $end, \DateTimeImmutable $due): array
    {
        return [
            'start' => $start,
            'end' => $end,
            'due' => $due,
            'label' => sprintf('%s → %s', $start->format('d/m/Y'), $end->format('d/m/Y')),
        ];
    }

    private function formatCustomerLabel(Customer $customer): string
    {
        $name = trim(sprintf('%s %s', $customer->getFirstName(), (string) $customer->getLastName()));

        if ($name !== '') {
            return $name;
        }

        $email = trim((string) $customer->getEmail());

        return $email !== '' ? $email : sprintf('Livreur #%s', $customer->getId() ?? '?');
    }

    private function formatDateTime(?\DateTimeImmutable $date): string
    {
        return $date instanceof \DateTimeImmutable ? $date->format('d/m/Y H:i') : 'Date non renseignée';
    }
}
