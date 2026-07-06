<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\SmsLog;
use App\Entity\HodinaSetting;
use App\Service\Sms\SmsService;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerOrderWorkflowService
{
    public function __construct(
        private readonly OrderReferenceGenerator $orderReferenceGenerator,
        private readonly SmsService $smsService,
        private readonly CustomerOrderNotificationService $notificationService,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function canConfirm(CustomerOrder $order): bool
    {
        return $order->getStatus() === CustomerOrder::STATUS_PENDING_VALIDATION;
    }

    public function canCancel(CustomerOrder $order): bool
    {
        return in_array($order->getStatus(), [
            CustomerOrder::STATUS_PENDING_VALIDATION,
            CustomerOrder::STATUS_CONFIRMED,
        ], true);
    }

    public function canPrepare(CustomerOrder $order): bool
    {
        return $order->getStatus() === CustomerOrder::STATUS_CONFIRMED;
    }

    public function canMarkReady(CustomerOrder $order): bool
    {
        return $order->getStatus() === CustomerOrder::STATUS_PREPARING;
    }

    public function canMarkDeliveredByAdmin(CustomerOrder $order): bool
    {
        return in_array($order->getStatus(), [
            CustomerOrder::STATUS_READY_FOR_PICKUP,
            CustomerOrder::STATUS_PICKED_UP,
            CustomerOrder::STATUS_OUT_FOR_DELIVERY,
        ], true);
    }

    public function canTakeForDelivery(CustomerOrder $order): bool
    {
        return $order->getStatus() === CustomerOrder::STATUS_READY_FOR_PICKUP
            && null === $order->getAssignedCourier();
    }

    public function canStartDelivery(CustomerOrder $order, Customer $courier): bool
    {
        return $order->getStatus() === CustomerOrder::STATUS_PICKED_UP
            && $order->getAssignedCourier()?->getId() === $courier->getId()
            && $order->areAllSellerCollectionsDone();
    }

    public function canMarkDeliveredByCourier(CustomerOrder $order, Customer $courier): bool
    {
        return $order->getStatus() === CustomerOrder::STATUS_OUT_FOR_DELIVERY
            && $order->getAssignedCourier()?->getId() === $courier->getId();
    }

    public function confirm(CustomerOrder $order): SmsLog
    {
        return $this->transition(
            $order,
            [CustomerOrder::STATUS_PENDING_VALIDATION],
            CustomerOrder::STATUS_CONFIRMED,
            'confirmedAt',
            'Ta commande %s est validée. Elle va être préparée.'
        );
    }

    public function cancel(CustomerOrder $order): SmsLog
    {
        return $this->transition(
            $order,
            [CustomerOrder::STATUS_PENDING_VALIDATION, CustomerOrder::STATUS_CONFIRMED],
            CustomerOrder::STATUS_CANCELED,
            'canceledAt',
            'Ta commande %s ne peut pas être validée pour le moment. Notre équipe peut te recontacter si besoin.'
        );
    }

    public function cancelByCustomer(CustomerOrder $order): SmsLog
    {
        return $this->transition(
            $order,
            [CustomerOrder::STATUS_PENDING_VALIDATION, CustomerOrder::STATUS_CONFIRMED],
            CustomerOrder::STATUS_CANCELED,
            'canceledAt',
            'Ta commande %s a bien été annulée à ta demande. Merci pour ton retour.'
        );
    }

    public function markPreparing(CustomerOrder $order): SmsLog
    {
        return $this->transition(
            $order,
            [CustomerOrder::STATUS_CONFIRMED],
            CustomerOrder::STATUS_PREPARING,
            'preparingAt',
            'Ta commande %s est en cours de préparation.'
        );
    }

    public function markReady(CustomerOrder $order): SmsLog
    {
        return $this->transition(
            $order,
            [CustomerOrder::STATUS_PREPARING],
            CustomerOrder::STATUS_READY_FOR_PICKUP,
            'readyAt',
            'Ta commande %s est prête. Nous allons organiser la livraison.'
        );
    }

    public function markDeliveredByAdmin(CustomerOrder $order): SmsLog
    {
        return $this->transition(
            $order,
            [
                CustomerOrder::STATUS_READY_FOR_PICKUP,
                CustomerOrder::STATUS_PICKED_UP,
                CustomerOrder::STATUS_OUT_FOR_DELIVERY,
            ],
            CustomerOrder::STATUS_DELIVERED,
            'deliveredAt',
            'Ta commande %s a été livrée. Merci pour ta confiance.'
        );
    }

    public function takeForDelivery(CustomerOrder $order, Customer $courier): SmsLog
    {
        if (!$this->canTakeForDelivery($order)) {
            throw new \DomainException('Cette commande ne peut pas être prise en charge par un livreur.');
        }

        $now = new \DateTimeImmutable();
        $orderReference = $this->orderReferenceGenerator->ensureReference($order);

        $order
            ->setStatus(CustomerOrder::STATUS_PICKED_UP)
            ->setAssignedCourier($courier)
            ->setCourierAssignedAt($now);

        $this->applyCourierPayoutCapSnapshot($order, $courier);

        $customerPhone = (string) ($order->getCustomer()->getPhone() ?? '');
        $smsMessage = $this->buildCustomerSmsMessage(
            $order,
            $orderReference,
            'Ta commande %s est prise en charge par notre livreur. Nous te prévenons au départ en livraison.'
        );

        $smsLog = $this->smsService->sendForOrder(
            $order,
            $customerPhone,
            $smsMessage,
            'customer_order_picked_up',
            'customer'
        );

        $this->notificationService->sendStatusEmailToCustomer($order, CustomerOrder::STATUS_PICKED_UP);

        return $smsLog;
    }

    public function startDelivery(CustomerOrder $order, Customer $courier): void
    {
        if ($order->getStatus() !== CustomerOrder::STATUS_PICKED_UP
            || $order->getAssignedCourier()?->getId() !== $courier->getId()
        ) {
            throw new \DomainException('Seul le livreur assigné peut démarrer la livraison de cette commande.');
        }

        if (!$order->areAllSellerCollectionsDone()) {
            throw new \DomainException('Toutes les collectes vendeur doivent être validées avant de démarrer la livraison client.');
        }

        $this->orderReferenceGenerator->ensureReference($order);

        $order
            ->setStatus(CustomerOrder::STATUS_OUT_FOR_DELIVERY)
            ->setOutForDeliveryAt(new \DateTimeImmutable());

        // J5Q-D0 : le départ en livraison ne déclenche plus le SMS générique
        // `customer_order_out_for_delivery`. Le client reçoit déjà l'information
        // avec le code de réception envoyé par CustomerDeliveryCodeService.
        $this->entityManager->flush();
    }

    public function markDeliveredByCourier(CustomerOrder $order, Customer $courier): SmsLog
    {
        if (!$this->canMarkDeliveredByCourier($order, $courier)) {
            throw new \DomainException('Seul le livreur assigné peut marquer cette commande comme livrée.');
        }

        return $this->transition(
            $order,
            [CustomerOrder::STATUS_OUT_FOR_DELIVERY],
            CustomerOrder::STATUS_DELIVERED,
            'deliveredAt',
            'Ta commande %s a été livrée. Merci pour ta confiance.'
        );
    }


    private function applyCourierPayoutCapSnapshot(CustomerOrder $order, Customer $courier): void
    {
        $snapshot = $order->getDeliveryLogisticsSnapshot();
        if (!is_array($snapshot)) {
            return;
        }

        $preview = $snapshot['preview'] ?? null;
        $target = is_array($preview) ? $preview : $snapshot;

        $uncappedCourierPayout = isset($target['uncappedCourierPayout'])
            ? (float) $target['uncappedCourierPayout']
            : (float) ($target['estimatedCourierPayout'] ?? 0.0);

        if ($uncappedCourierPayout <= 0.0) {
            return;
        }

        $courierSpecificCap = $courier->getCourierPayoutCapAsFloat();
        $globalCap = $this->getGlobalDeliveryCourierPayoutCap();
        $effectiveCap = $courierSpecificCap ?? $globalCap;
        $capSource = $courierSpecificCap !== null ? 'courier' : 'global';

        if ($effectiveCap <= 0.0) {
            $target['uncappedCourierPayout'] = round($uncappedCourierPayout, 2);
            $target['courierPayoutCap'] = null;
            $target['courierPayoutCapSource'] = null;
            $target['courierPayoutCapApplied'] = false;
            $target['estimatedCourierPayout'] = round($uncappedCourierPayout, 2);
            $target['estimatedDeliveryMargin'] = round(((float) ($target['estimatedDeliveryFee'] ?? 0.0)) - $uncappedCourierPayout, 2);
            if (is_array($preview)) {
                $snapshot['preview'] = $target;
            } else {
                $snapshot = $target;
            }
            $order->setDeliveryLogisticsSnapshot($snapshot);
            return;
        }

        $effectivePayout = min($uncappedCourierPayout, $effectiveCap);

        $target['uncappedCourierPayout'] = round($uncappedCourierPayout, 2);
        $target['courierPayoutCap'] = round($effectiveCap, 2);
        $target['courierPayoutCapSource'] = $capSource;
        $target['courierPayoutCapApplied'] = $uncappedCourierPayout > $effectiveCap;
        $target['estimatedCourierPayout'] = round($effectivePayout, 2);
        $target['estimatedDeliveryMargin'] = round(((float) ($target['estimatedDeliveryFee'] ?? 0.0)) - $effectivePayout, 2);

        if (is_array($preview)) {
            $snapshot['preview'] = $target;
        } else {
            $snapshot = $target;
        }

        $order->setDeliveryLogisticsSnapshot($snapshot);
    }

    private function getGlobalDeliveryCourierPayoutCap(): float
    {
        $setting = $this->entityManager
            ->getRepository(HodinaSetting::class)
            ->findOneBy(['settingKey' => HodinaSetting::KEY_GLOBAL_DELIVERY_COURIER_PAYOUT_CAP]);

        if (!$setting instanceof HodinaSetting) {
            return 20.0;
        }

        $value = str_replace(',', '.', trim((string) $setting->getValue()));

        return $value !== '' ? max(0.0, round((float) $value, 2)) : 20.0;
    }

    /**
     * @param array<int, string> $allowedFromStatuses
     */
    private function transition(
        CustomerOrder $order,
        array $allowedFromStatuses,
        string $targetStatus,
        string $dateField,
        string $smsTemplate
    ): SmsLog {
        if (!in_array($order->getStatus(), $allowedFromStatuses, true)) {
            throw new \DomainException('Transition de statut non autorisée pour cette commande.');
        }

        $now = new \DateTimeImmutable();
        $orderReference = $this->orderReferenceGenerator->ensureReference($order);

        $order->setStatus($targetStatus);

        match ($dateField) {
            'confirmedAt' => $order->setConfirmedAt($now),
            'preparingAt' => $order->setPreparingAt($now),
            'readyAt' => $order->setReadyAt($now),
            'deliveredAt' => $order->setDeliveredAt($now),
            'canceledAt' => $order->setCanceledAt($now),
            default => null,
        };

        $customerPhone = (string) ($order->getCustomer()->getPhone() ?? '');
        $smsMessage = $this->buildCustomerSmsMessage($order, $orderReference, $smsTemplate);

        $smsLog = $this->smsService->sendForOrder(
            $order,
            $customerPhone,
            $smsMessage,
            'customer_order_' . strtolower($targetStatus),
            'customer'
        );

        $this->notificationService->sendStatusEmailToCustomer($order, $targetStatus);

        return $smsLog;
    }

    private function buildCustomerSmsMessage(CustomerOrder $order, string $orderReference, string $smsTemplate): string
    {
        $firstName = trim($order->getCustomer()->getFirstName() ?? '');
        $greetingName = $firstName !== '' ? $firstName : 'client';

        return sprintf(
            'Gégé %s, Hodina – ' . $smsTemplate,
            $greetingName,
            $orderReference
        );
    }
}
