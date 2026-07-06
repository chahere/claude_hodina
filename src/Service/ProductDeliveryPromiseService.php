<?php

namespace App\Service;

use App\Dto\ProductDeliveryPromise;
use App\Entity\DeliveryPricingZone;
use App\Entity\Product;

/**
 * Service J5X-C : construit la promesse de livraison visible sur la fiche
 * produit, sans toucher au calcul des frais ni au checkout.
 */
final class ProductDeliveryPromiseService
{
    /** @var array<int, string> */
    private const WEEKDAY_LABELS = [
        1 => 'lundi',
        2 => 'mardi',
        3 => 'mercredi',
        4 => 'jeudi',
        5 => 'vendredi',
        6 => 'samedi',
        7 => 'dimanche',
    ];

    public function __construct(
        private readonly DeliveryScheduleService $deliveryScheduleService,
    ) {}

    public function buildForProduct(Product $product, ?DeliveryPricingZone $selectedPricingZone = null): ProductDeliveryPromise
    {
        if ($product->isAppointmentDeliveryPromise()) {
            return $this->buildAppointmentPromise($product);
        }

        return $this->buildSectorSchedulePromise($product, $selectedPricingZone);
    }

    private function buildSectorSchedulePromise(Product $product, ?DeliveryPricingZone $selectedPricingZone): ProductDeliveryPromise
    {
        $selectedSchedule = $selectedPricingZone instanceof DeliveryPricingZone
            ? $this->deliveryScheduleService->buildPreview($selectedPricingZone)->toArray()
            : null;

        if ($selectedSchedule !== null) {
            $title = sprintf('Livraison à %s', $selectedSchedule['publicLabel'] ?? 'ta commune');
            $summary = sprintf(
                'Ce produit suit les passages Hodina de ton secteur : %s.',
                implode(', ', $selectedSchedule['weekdayLabels'] ?? []) ?: 'jours à confirmer',
            );
        } else {
            $title = 'Livraison selon ta commune';
            $summary = 'Choisis ta commune au panier pour voir les frais et le prochain passage Hodina.';
        }

        return new ProductDeliveryPromise(
            mode: Product::DELIVERY_PROMISE_MODE_SECTOR_SCHEDULE,
            badgeLabel: 'Selon commune',
            title: $product->getDeliveryPromiseTitle() ?: $title,
            summary: $summary,
            description: $product->getDeliveryPromiseDescription(),
            isAppointment: false,
            selectedSchedule: $selectedSchedule,
            sectorSchedules: $selectedSchedule === null ? $this->deliveryScheduleService->getPublicSectorSchedulePreviews() : [],
            showSectorSchedules: $selectedSchedule === null,
            appointment: null,
        );
    }

    private function buildAppointmentPromise(Product $product): ProductDeliveryPromise
    {
        $appointment = [
            'weekdayLabel' => $this->formatAppointmentWeekdays($product->getAppointmentDeliveryWeekdays()),
            'timeWindowLabel' => $this->formatTimeWindow($product->getAppointmentTimeWindowStart(), $product->getAppointmentTimeWindowEnd()),
            'cutoffLabel' => $this->formatAppointmentCutoff($product->getAppointmentCutoffTime(), $product->getAppointmentCutoffDaysBefore()),
            'leadTimeLabel' => $this->formatLeadTime($product->getMinimumOrderLeadTimeHours()),
        ];

        return new ProductDeliveryPromise(
            mode: Product::DELIVERY_PROMISE_MODE_APPOINTMENT,
            badgeLabel: 'Sur créneau',
            title: $product->getDeliveryPromiseTitle() ?: 'Livraison sur créneau',
            summary: 'Indique l’heure souhaitée à la commande. Hodina confirme ensuite le créneau selon la disponibilité terrain.',
            description: $product->getDeliveryPromiseDescription()
                ?: 'Adapté aux accueils aéroport, cérémonies, événements ou produits frais préparés pour un moment précis.',
            isAppointment: true,
            selectedSchedule: null,
            sectorSchedules: [],
            showSectorSchedules: false,
            appointment: $appointment,
            confirmationLabel: 'Le créneau final est confirmé par Hodina après vérification du vendeur et de la disponibilité terrain.',
        );
    }

    /** @param list<int> $weekdays */
    private function formatAppointmentWeekdays(array $weekdays): string
    {
        if ($weekdays === []) {
            return 'jours à confirmer par Hodina';
        }

        if ($weekdays === [1, 2, 3, 4, 5, 6, 7]) {
            return 'tous les jours';
        }

        $labels = [];
        foreach ($weekdays as $weekday) {
            if (isset(self::WEEKDAY_LABELS[$weekday])) {
                $labels[] = self::WEEKDAY_LABELS[$weekday];
            }
        }

        return $this->joinLabels($labels);
    }

    private function formatTimeWindow(?\DateTimeImmutable $start, ?\DateTimeImmutable $end): string
    {
        if ($start instanceof \DateTimeImmutable && $end instanceof \DateTimeImmutable) {
            return sprintf('entre %s et %s', $this->formatTime($start), $this->formatTime($end));
        }

        if ($start instanceof \DateTimeImmutable) {
            return sprintf('à partir de %s', $this->formatTime($start));
        }

        if ($end instanceof \DateTimeImmutable) {
            return sprintf('jusqu’à %s', $this->formatTime($end));
        }

        return 'horaire à préciser au panier';
    }

    private function formatAppointmentCutoff(?\DateTimeImmutable $cutoffTime, int $daysBefore): string
    {
        $time = $cutoffTime instanceof \DateTimeImmutable ? $this->formatTime($cutoffTime) : '10h';
        $daysBefore = max(0, $daysBefore);

        if ($daysBefore === 0) {
            return sprintf('commande à valider avant %s le jour même', $time);
        }

        if ($daysBefore === 1) {
            return sprintf('commande à valider avant %s la veille', $time);
        }

        return sprintf('commande à valider avant %s, %d jours avant', $time, $daysBefore);
    }

    private function formatLeadTime(?int $minimumOrderLeadTimeHours): ?string
    {
        if ($minimumOrderLeadTimeHours === null || $minimumOrderLeadTimeHours <= 0) {
            return null;
        }

        if ($minimumOrderLeadTimeHours % 24 === 0) {
            $days = (int) ($minimumOrderLeadTimeHours / 24);
            return sprintf('prévoir au moins %d jour%s avant le créneau souhaité', $days, $days > 1 ? 's' : '');
        }

        return sprintf('prévoir au moins %d h avant le créneau souhaité', $minimumOrderLeadTimeHours);
    }

    private function formatTime(\DateTimeImmutable $time): string
    {
        return $time->format('i') === '00'
            ? $time->format('G') . 'h'
            : $time->format('G\hi');
    }

    /** @param list<string> $labels */
    private function joinLabels(array $labels): string
    {
        $labels = array_values(array_filter($labels, static fn (string $label): bool => $label !== ''));
        if ($labels === []) {
            return 'à confirmer';
        }

        if (count($labels) === 1) {
            return $labels[0];
        }

        $last = array_pop($labels);

        return implode(', ', $labels) . ' et ' . $last;
    }
}
