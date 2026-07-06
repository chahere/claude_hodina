<?php

namespace App\Dto;

/**
 * Aperçu public d'un calendrier de passage Hodina pour une zone tarifaire.
 *
 * J5X-B : cette donnée prépare l'affichage panier / fiche produit sans figer
 * une date de livraison garantie. Pendant le pilote, Hodina confirme toujours
 * la date finale après vérification des vendeurs.
 */
final class DeliverySchedulePreview
{
    /** @param list<string> $weekdayLabels */
    public function __construct(
        public readonly string $pricingZoneCode,
        public readonly string $publicLabel,
        public readonly array $weekdayLabels,
        public readonly ?\DateTimeImmutable $nextDeliveryDate,
        public readonly ?string $nextDeliveryDateLabel,
        public readonly ?\DateTimeImmutable $cutoffDateTime,
        public readonly ?string $cutoffLabel,
        public readonly bool $isActive,
        public readonly bool $isCurrentNextSlotOpen,
        public readonly string $message,
        public readonly ?string $warning = null,
        public readonly string $confirmationLabel = 'La date finale est confirmée par Hodina après vérification des vendeurs.',
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'pricingZoneCode' => $this->pricingZoneCode,
            'publicLabel' => $this->publicLabel,
            'weekdayLabels' => $this->weekdayLabels,
            'nextDeliveryDate' => $this->nextDeliveryDate?->format('Y-m-d'),
            'nextDeliveryDateLabel' => $this->nextDeliveryDateLabel,
            'cutoffDateTime' => $this->cutoffDateTime?->format(DATE_ATOM),
            'cutoffLabel' => $this->cutoffLabel,
            'isActive' => $this->isActive,
            'isCurrentNextSlotOpen' => $this->isCurrentNextSlotOpen,
            'message' => $this->message,
            'warning' => $this->warning,
            'confirmationLabel' => $this->confirmationLabel,
        ];
    }
}
