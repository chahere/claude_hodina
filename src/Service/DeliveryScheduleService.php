<?php

namespace App\Service;

use App\Dto\CartLogisticsPreview;
use App\Dto\DeliverySchedulePreview;
use App\Entity\DeliveryCommune;
use App\Entity\DeliveryPricingZone;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier J5X-B : calendrier de passage par secteur tarifaire.
 *
 * Ce service ne calcule pas le prix de livraison. Il lit uniquement le
 * calendrier public porté par DeliveryPricingZone et produit une promesse UX
 * prudente : "prochain passage possible", jamais une garantie de livraison.
 */
final class DeliveryScheduleService
{
    private const MAYOTTE_TIMEZONE = 'Indian/Mayotte';

    /** @var list<string> */
    private const PUBLIC_ZONE_ORDER = [
        'PT_LOCAL',
        'MAMOUDZOU_LOCAL',
        'SUD_LOCAL',
        'NORD_LOCAL',
        'CENTRE_LOCAL',
    ];

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

    /** @var array<int, string> */
    private const MONTH_LABELS = [
        1 => 'janvier',
        2 => 'février',
        3 => 'mars',
        4 => 'avril',
        5 => 'mai',
        6 => 'juin',
        7 => 'juillet',
        8 => 'août',
        9 => 'septembre',
        10 => 'octobre',
        11 => 'novembre',
        12 => 'décembre',
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    public function buildPreviewFromLogisticsPreview(CartLogisticsPreview $logisticsPreview, ?\DateTimeImmutable $now = null): ?DeliverySchedulePreview
    {
        $pricingZoneCode = trim((string) ($logisticsPreview->pricingZoneCode ?? ''));
        if ($pricingZoneCode === '') {
            return null;
        }

        $pricingZone = $this->entityManager
            ->getRepository(DeliveryPricingZone::class)
            ->findOneBy(['code' => $pricingZoneCode]);

        return $pricingZone instanceof DeliveryPricingZone
            ? $this->buildPreview($pricingZone, $now)
            : null;
    }

    /**
     * Fallback UX pour le panier : certaines sessions peuvent contenir un ancien
     * aperçu logistique mis en cache avant J5X-B, donc sans pricingZoneCode.
     * Dans ce cas, on repart de la commune client déjà calculée côté serveur.
     */
    public function buildPreviewFromClientCommuneName(?string $clientCommuneName, ?\DateTimeImmutable $now = null): ?DeliverySchedulePreview
    {
        $lookup = $this->normalizeCommuneLookup((string) $clientCommuneName);
        if ($lookup === '') {
            return null;
        }

        $communes = $this->entityManager
            ->getRepository(DeliveryCommune::class)
            ->findBy(['isActive' => true]);

        foreach ($communes as $commune) {
            if (!$commune instanceof DeliveryCommune) {
                continue;
            }

            $nameMatches = $this->normalizeCommuneLookup($commune->getName()) === $lookup;
            $slugMatches = $this->normalizeCommuneLookup($commune->getSlug()) === $lookup;

            if (!$nameMatches && !$slugMatches) {
                continue;
            }

            $pricingZone = $commune->getLocalPricingZone();

            return $pricingZone instanceof DeliveryPricingZone
                ? $this->buildPreview($pricingZone, $now)
                : null;
        }

        return null;
    }

    public function buildPreview(DeliveryPricingZone $pricingZone, ?\DateTimeImmutable $now = null): DeliverySchedulePreview
    {
        $now ??= new \DateTimeImmutable('now', new \DateTimeZone(self::MAYOTTE_TIMEZONE));
        $now = $now->setTimezone(new \DateTimeZone(self::MAYOTTE_TIMEZONE));

        $publicLabel = $pricingZone->getPublicLabel() ?: $pricingZone->getName();
        $weekdays = $pricingZone->getDeliveryWeekdays();
        $weekdayLabels = $this->formatWeekdayLabels($weekdays);

        if (!$pricingZone->isDeliveryScheduleActive() || $weekdays === []) {
            return new DeliverySchedulePreview(
                pricingZoneCode: $pricingZone->getCode(),
                publicLabel: $publicLabel,
                weekdayLabels: $weekdayLabels,
                nextDeliveryDate: null,
                nextDeliveryDateLabel: null,
                cutoffDateTime: null,
                cutoffLabel: null,
                isActive: false,
                isCurrentNextSlotOpen: false,
                message: sprintf('Jours de passage à %s à confirmer par Hodina.', $publicLabel),
                warning: 'Planning de livraison non actif pour ce secteur.',
            );
        }

        $slot = $this->findNextOpenSlot(
            $weekdays,
            $pricingZone->getCutoffTime(),
            $pricingZone->getCutoffDaysBefore(),
            $now,
        );

        $message = sprintf('Passages à %s : %s.', $publicLabel, $this->joinLabels($weekdayLabels));

        return new DeliverySchedulePreview(
            pricingZoneCode: $pricingZone->getCode(),
            publicLabel: $publicLabel,
            weekdayLabels: $weekdayLabels,
            nextDeliveryDate: $slot['deliveryDate'] ?? null,
            nextDeliveryDateLabel: isset($slot['deliveryDate']) ? $this->formatDateLabel($slot['deliveryDate']) : null,
            cutoffDateTime: $slot['cutoffDateTime'] ?? null,
            cutoffLabel: isset($slot['cutoffDateTime']) ? 'commande avant ' . $this->formatCutoffLabel($slot['cutoffDateTime']) : null,
            isActive: true,
            isCurrentNextSlotOpen: isset($slot['deliveryDate']),
            message: $message,
            warning: null,
        );
    }

    /** @return list<array<string, mixed>> */
    public function getPublicSectorSchedulePreviews(?\DateTimeImmutable $now = null): array
    {
        $repository = $this->entityManager->getRepository(DeliveryPricingZone::class);
        $previewsByCode = [];

        foreach (self::PUBLIC_ZONE_ORDER as $code) {
            $pricingZone = $repository->findOneBy(['code' => $code]);
            if ($pricingZone instanceof DeliveryPricingZone) {
                $previewsByCode[$code] = $this->buildPreview($pricingZone, $now)->toArray();
            }
        }

        return array_values($previewsByCode);
    }

    /**
     * @param list<int> $weekdays
     * @return array{deliveryDate?: \DateTimeImmutable, cutoffDateTime?: \DateTimeImmutable}
     */
    private function findNextOpenSlot(array $weekdays, ?\DateTimeImmutable $cutoffTime, int $cutoffDaysBefore, \DateTimeImmutable $now): array
    {
        $timezone = new \DateTimeZone(self::MAYOTTE_TIMEZONE);
        $cutoffDaysBefore = max(0, $cutoffDaysBefore);
        $cutoffHour = $cutoffTime ? (int) $cutoffTime->format('H') : 10;
        $cutoffMinute = $cutoffTime ? (int) $cutoffTime->format('i') : 0;

        for ($offset = 0; $offset <= 28; ++$offset) {
            $candidate = $now
                ->setTimezone($timezone)
                ->setTime(0, 0)
                ->modify(sprintf('+%d days', $offset));

            $weekday = (int) $candidate->format('N');
            if (!in_array($weekday, $weekdays, true)) {
                continue;
            }

            $cutoffDateTime = $candidate
                ->modify(sprintf('-%d days', $cutoffDaysBefore))
                ->setTime($cutoffHour, $cutoffMinute);

            if ($now <= $cutoffDateTime) {
                return [
                    'deliveryDate' => $candidate,
                    'cutoffDateTime' => $cutoffDateTime,
                ];
            }
        }

        return [];
    }

    private function normalizeCommuneLookup(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $value = strtr(mb_strtolower($value), [
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
        ]);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    /** @param list<int> $weekdays */
    private function formatWeekdayLabels(array $weekdays): array
    {
        $labels = [];
        foreach ($weekdays as $weekday) {
            if (isset(self::WEEKDAY_LABELS[$weekday])) {
                $labels[] = self::WEEKDAY_LABELS[$weekday];
            }
        }

        return $labels;
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

    private function formatDateLabel(\DateTimeImmutable $date): string
    {
        $weekday = self::WEEKDAY_LABELS[(int) $date->format('N')] ?? mb_strtolower($date->format('l'));
        $month = self::MONTH_LABELS[(int) $date->format('n')] ?? $date->format('m');

        return sprintf('%s %d %s', $weekday, (int) $date->format('j'), $month);
    }

    private function formatCutoffLabel(\DateTimeImmutable $dateTime): string
    {
        $dayLabel = $this->formatDateLabel($dateTime);
        $timeLabel = $dateTime->format('i') === '00'
            ? $dateTime->format('G') . 'h'
            : $dateTime->format('G\hi');

        return sprintf('%s %s', $dayLabel, $timeLabel);
    }
}
