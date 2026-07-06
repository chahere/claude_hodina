<?php

namespace App\Dto;

/**
 * Promesse de livraison affichable sur une fiche produit.
 *
 * J5X-C : ce DTO ne calcule pas les frais et ne garantit pas une date de
 * livraison. Il structure uniquement le message client selon le type de
 * promesse produit : calendrier secteur ou produit sur créneau.
 */
final class ProductDeliveryPromise
{
    /**
     * @param array<string, mixed>|null $selectedSchedule
     * @param list<array<string, mixed>> $sectorSchedules
     * @param array<string, mixed>|null $appointment
     */
    public function __construct(
        public readonly string $mode,
        public readonly string $badgeLabel,
        public readonly string $title,
        public readonly string $summary,
        public readonly ?string $description,
        public readonly bool $isAppointment,
        public readonly ?array $selectedSchedule,
        public readonly array $sectorSchedules,
        public readonly bool $showSectorSchedules,
        public readonly ?array $appointment,
        public readonly string $confirmationLabel = 'La date finale est confirmée par Hodina après vérification des vendeurs.',
    ) {}

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'mode' => $this->mode,
            'badgeLabel' => $this->badgeLabel,
            'title' => $this->title,
            'summary' => $this->summary,
            'description' => $this->description,
            'isAppointment' => $this->isAppointment,
            'selectedSchedule' => $this->selectedSchedule,
            'sectorSchedules' => $this->sectorSchedules,
            'showSectorSchedules' => $this->showSectorSchedules,
            'appointment' => $this->appointment,
            'confirmationLabel' => $this->confirmationLabel,
        ];
    }
}
