<?php

namespace App\Service\Sms;

use App\Entity\CustomerOrder;
use App\Entity\OrderItem;
use App\Entity\Seller;

final class OrderSmsMessageBuilder
{
    /**
     * @return array<string, string>
     */
    public function getTemplates(CustomerOrder $order, string $orderReference): array
    {
        $customerName = $this->getCustomerFirstName($order);
        $zone = $this->getZoneLabel($order);
        $total = number_format((float) $order->getTotal(), 2, ',', ' ');
        $items = $this->summarizeItems($order);

        return [
            'customer_confirmed' => sprintf(
                'Gégé %s, Hodina – ta commande %s est validée. Total : %s €. Zone : %s. Nous revenons vers toi pour la livraison.',
                $customerName,
                $orderReference,
                $total,
                $zone
            ),
            'customer_preparing' => sprintf(
                'Gégé %s, Hodina – ta commande %s est en préparation. Nous te prévenons dès qu’elle est prête pour la livraison.',
                $customerName,
                $orderReference
            ),
            'customer_ready' => sprintf(
                'Gégé %s, Hodina – ta commande %s est prête. Livraison en cours d’organisation. Zone : %s.',
                $customerName,
                $orderReference,
                $zone
            ),
            'customer_picked_up' => sprintf(
                'Gégé %s, Hodina – ta commande %s est prise en charge par notre livreur. Nous te prévenons au départ en livraison.',
                $customerName,
                $orderReference
            ),
            'customer_out_for_delivery' => sprintf(
                'Gégé %s, Hodina – ta commande %s est en cours de livraison.',
                $customerName,
                $orderReference
            ),
            'customer_delivered' => sprintf(
                'Gégé %s, Hodina – ta commande %s a été livrée. Merci pour ta confiance.',
                $customerName,
                $orderReference
            ),
            'seller_availability' => sprintf(
                'Hodina – peux-tu confirmer la disponibilité pour la commande %s ? Produits : %s. Merci.',
                $orderReference,
                $items
            ),
            'seller_prepare' => sprintf(
                'Hodina – commande %s validée. Merci de préparer les produits suivants : %s.',
                $orderReference,
                $items
            ),
            'delivery_pickup' => sprintf(
                'Hodina – commande %s prête à récupérer. Zone client : %s. Total commande : %s €.',
                $orderReference,
                $zone,
                $total
            ),
            'free' => '',
        ];
    }

    /**
     * @return array<string, array{label: string, phone: string, recipientType: string}>
     */
    public function getRecipients(CustomerOrder $order): array
    {
        $recipients = [];

        $customerPhone = trim($order->getCustomer()->getPhone());
        if ($customerPhone !== '') {
            $recipients['customer'] = [
                'label' => 'Client — ' . (string) $order->getCustomer(),
                'phone' => $customerPhone,
                'recipientType' => 'customer',
            ];
        }

        foreach ($this->getUniqueSellers($order) as $seller) {
            $phone = trim((string) $seller->getPhone());
            if ($phone === '') {
                continue;
            }

            $recipients['seller_' . $seller->getId()] = [
                'label' => 'Vendeur — ' . (string) $seller,
                'phone' => $phone,
                'recipientType' => 'seller',
            ];
        }

        $recipients['delivery'] = [
            'label' => 'Livreur — numéro à saisir manuellement',
            'phone' => '',
            'recipientType' => 'delivery',
        ];

        $recipients['other'] = [
            'label' => 'Autre — numéro à saisir manuellement',
            'phone' => '',
            'recipientType' => 'other',
        ];

        return $recipients;
    }

    private function getCustomerFirstName(CustomerOrder $order): string
    {
        $firstName = trim($order->getCustomer()->getFirstName() ?? '');

        return $firstName !== '' ? $firstName : 'client';
    }

    private function getZoneLabel(CustomerOrder $order): string
    {
        return $order->getDeliveryZone() ? (string) $order->getDeliveryZone() : 'à confirmer';
    }

    private function summarizeItems(CustomerOrder $order): string
    {
        $lines = [];

        foreach ($order->getItems() as $item) {
            if (!$item instanceof OrderItem) {
                continue;
            }

            $lines[] = sprintf('%d x %s', $item->getQuantity(), $item->getProduct()->getName());
        }

        $summary = implode(', ', $lines);

        if (mb_strlen($summary) > 260) {
            return mb_substr($summary, 0, 257) . '...';
        }

        return $summary !== '' ? $summary : 'voir fiche commande';
    }

    /**
     * @return Seller[]
     */
    private function getUniqueSellers(CustomerOrder $order): array
    {
        $sellers = [];

        foreach ($order->getItems() as $item) {
            if (!$item instanceof OrderItem) {
                continue;
            }

            $seller = $item->getSeller();
            $id = $seller->getId();

            if ($id === null) {
                continue;
            }

            $sellers[$id] = $seller;
        }

        return array_values($sellers);
    }
}
