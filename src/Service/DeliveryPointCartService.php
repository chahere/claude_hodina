<?php

namespace App\Service;

use App\Entity\DeliveryPoint;
use App\Entity\DeliveryPointTimeWindow;
use App\Entity\Product;
use App\Entity\ProductDeliveryPoint;

final class DeliveryPointCartService
{
    public const METHOD_STANDARD = 'STANDARD';
    public const METHOD_DELIVERY_POINT = 'DELIVERY_POINT';
    public const SLOT_INTERVAL_MINUTES = 30;

    /**
     * @param array<string, mixed> $cartData
     * @return array<string, mixed>
     */
    public function analyzeCart(array $cartData): array
    {
        $items = $cartData['items'] ?? [];
        $hasDeliveryPointProducts = false;
        $requiresDeliveryPoint = false;
        $allowsStandardDelivery = true;
        $requiredProducts = [];
        $pointEnabledProducts = [];

        foreach ($items as $item) {
            if (!is_array($item) || !isset($item['product']) || !$item['product'] instanceof Product) {
                continue;
            }

            /** @var Product $product */
            $product = $item['product'];

            if ($product->allowsDeliveryPoint()) {
                $hasDeliveryPointProducts = true;
                $pointEnabledProducts[] = $product;
            }

            if ($product->requiresDeliveryPoint()) {
                $requiresDeliveryPoint = true;
                $allowsStandardDelivery = false;
                $requiredProducts[] = $product;
            }
        }

        $productsForPoints = $requiredProducts !== [] ? $requiredProducts : $pointEnabledProducts;
        $availablePoints = $this->resolveAvailablePoints($productsForPoints, $requiredProducts !== []);
        $conflictMessage = null;

        if ($requiresDeliveryPoint && $availablePoints === []) {
            $conflictMessage = 'Ce panier contient des produits à point de remise imposé, mais aucun point commun actif n’est disponible. Commande ces produits séparément ou contacte Hodina.';
        }

        return [
            'hasDeliveryPointProducts' => $hasDeliveryPointProducts,
            'requiresDeliveryPoint' => $requiresDeliveryPoint,
            'allowsStandardDelivery' => $allowsStandardDelivery,
            'allowsDeliveryPoint' => $availablePoints !== [],
            'maximumOrderLeadTimeHours' => $this->getMaximumOrderLeadTimeHours($cartData),
            'availablePoints' => array_values($availablePoints),
            'timeWindowsByPoint' => $this->buildActiveTimeWindowsByPoint($availablePoints),
            'defaultMethod' => $requiresDeliveryPoint ? self::METHOD_DELIVERY_POINT : self::METHOD_STANDARD,
            'conflictMessage' => $conflictMessage,
        ];
    }

    /**
     * @param array<string, mixed> $analysis
     * @return list<array<string, mixed>>
     */
    public function buildPointChoices(array $analysis): array
    {
        $points = [];
        $timeWindowsByPoint = $analysis['timeWindowsByPoint'] ?? [];

        foreach (($analysis['availablePoints'] ?? []) as $point) {
            if (!$point instanceof DeliveryPoint || $point->getId() === null) {
                continue;
            }

            $deliveryCommune = $point->getDeliveryCommune();
            $points[] = [
                'id' => $point->getId(),
                'name' => $point->getName(),
                'code' => $point->getCode(),
                'type' => $point->getType(),
                'typeLabel' => $point->getTypeLabel(),
                'line1' => $point->getLine1(),
                'line2' => $point->getLine2(),
                'postalCode' => $point->getPostalCode() ?: $deliveryCommune->getPostalCode(),
                'commune' => $point->getCommuneName() ?: $deliveryCommune->getName(),
                'zone' => $deliveryCommune->getTerritory(),
                'publicInstructions' => $point->getPublicInstructions(),
                'courierInstructions' => $point->getCourierInstructions(),
                'gpsLatitude' => $point->getGpsLatitude(),
                'gpsLongitude' => $point->getGpsLongitude(),
                'gpsAccuracyMeters' => $point->getGpsAccuracyMeters(),
                'timeWindows' => $timeWindowsByPoint[$point->getId()] ?? [],
            ];
        }

        return $points;
    }

    public function isDeliveryPointAllowed(array $analysis, DeliveryPoint $deliveryPoint): bool
    {
        $id = $deliveryPoint->getId();

        if ($id === null) {
            return false;
        }

        foreach (($analysis['availablePoints'] ?? []) as $point) {
            if ($point instanceof DeliveryPoint && $point->getId() === $id) {
                return true;
            }
        }

        return false;
    }

    public function isTimeWindowAllowed(DeliveryPoint $deliveryPoint, DeliveryPointTimeWindow $timeWindow): bool
    {
        return $timeWindow->isActive()
            && $timeWindow->getDeliveryPoint()->getId() === $deliveryPoint->getId();
    }

    public function findMatchingTimeWindow(
        DeliveryPoint $deliveryPoint,
        \DateTimeImmutable $requestedDate,
        \DateTimeImmutable $requestedTime,
        ?int $requestedTimeWindowId = null
    ): ?DeliveryPointTimeWindow {
        $requestedWeekday = (int) $requestedDate->format('N');
        $requestedMinutes = $this->minutesFromTime($requestedTime);

        if (!$this->isValidSlotStartMinutes($requestedMinutes)) {
            return null;
        }

        $matching = [];

        foreach ($deliveryPoint->getTimeWindows() as $timeWindow) {
            if (!$timeWindow instanceof DeliveryPointTimeWindow || !$this->isTimeWindowAllowed($deliveryPoint, $timeWindow)) {
                continue;
            }

            if ($requestedTimeWindowId !== null && $requestedTimeWindowId > 0 && $timeWindow->getId() !== $requestedTimeWindowId) {
                continue;
            }

            if (!$this->timeWindowMatchesDateAndSlot($timeWindow, $requestedWeekday, $requestedMinutes)) {
                continue;
            }

            $matching[] = $timeWindow;
        }

        usort($matching, static function (DeliveryPointTimeWindow $left, DeliveryPointTimeWindow $right): int {
            if ($left->getSortOrder() === $right->getSortOrder()) {
                return strcmp($left->getStartTime()->format('H:i'), $right->getStartTime()->format('H:i'));
            }

            return $left->getSortOrder() <=> $right->getSortOrder();
        });

        return $matching[0] ?? null;
    }

    private function timeWindowMatchesDateAndSlot(DeliveryPointTimeWindow $timeWindow, int $requestedWeekday, int $requestedMinutes): bool
    {
        $weekday = $timeWindow->getWeekday();
        if ($weekday !== null && $weekday !== $requestedWeekday) {
            return false;
        }

        $startMinutes = $this->minutesFromTime($timeWindow->getStartTime());
        $endMinutes = $this->minutesFromTime($timeWindow->getEndTime());

        return $requestedMinutes >= $startMinutes
            && ($requestedMinutes + self::SLOT_INTERVAL_MINUTES) <= $endMinutes;
    }

    private function isValidSlotStartMinutes(int $minutes): bool
    {
        return $minutes >= 0
            && $minutes < 24 * 60
            && ($minutes % self::SLOT_INTERVAL_MINUTES) === 0;
    }

    private function minutesFromTime(\DateTimeInterface $time): int
    {
        return ((int) $time->format('H')) * 60 + (int) $time->format('i');
    }

    public function formatAppointmentLabel(\DateTimeImmutable $requestedDate, \DateTimeImmutable $requestedTime): string
    {
        return sprintf(
            '%s à %s',
            $requestedDate->format('d/m/Y'),
            $requestedTime->format('H:i')
        );
    }

    /**
     * @param array<string, mixed> $cartData
     */
    public function getMaximumOrderLeadTimeHours(array $cartData): int
    {
        $maximum = 0;

        foreach (($cartData['items'] ?? []) as $item) {
            if (!is_array($item) || !isset($item['product']) || !$item['product'] instanceof Product) {
                continue;
            }

            $leadTimeHours = $item['product']->getMinimumOrderLeadTimeHours();
            if ($leadTimeHours !== null && $leadTimeHours > $maximum) {
                $maximum = $leadTimeHours;
            }
        }

        return $maximum;
    }

    /**
     * @param array<string, mixed> $cartData
     * @return array{minimumHours: int, earliestAppointment: \DateTimeImmutable}|null
     */
    public function validateMinimumOrderLeadTime(
        array $cartData,
        \DateTimeImmutable $requestedDate,
        \DateTimeImmutable $requestedTime,
        ?\DateTimeImmutable $now = null
    ): ?array {
        $minimumHours = $this->getMaximumOrderLeadTimeHours($cartData);
        if ($minimumHours <= 0) {
            return null;
        }

        $timezone = new \DateTimeZone('Indian/Mayotte');
        $requestedAppointment = new \DateTimeImmutable(
            sprintf('%s %s', $requestedDate->format('Y-m-d'), $requestedTime->format('H:i')),
            $timezone
        );
        $now = $now instanceof \DateTimeImmutable
            ? $now->setTimezone($timezone)
            : new \DateTimeImmutable('now', $timezone);
        $earliestAppointment = $now->modify(sprintf('+%d hours', $minimumHours));

        if ($requestedAppointment < $earliestAppointment) {
            return [
                'minimumHours' => $minimumHours,
                'earliestAppointment' => $earliestAppointment,
            ];
        }

        return null;
    }

    public function formatTimeWindowLabel(DeliveryPointTimeWindow $timeWindow): string
    {
        $label = trim((string) $timeWindow->getLabel());
        $prefix = $label !== '' ? $label . ' · ' : '';

        return sprintf(
            '%s%s %s–%s',
            $prefix,
            $timeWindow->getWeekdayLabel(),
            $timeWindow->getStartTime()->format('H:i'),
            $timeWindow->getEndTime()->format('H:i')
        );
    }

    /**
     * @param list<Product> $products
     * @return array<int, DeliveryPoint>
     */
    private function resolveAvailablePoints(array $products, bool $intersect): array
    {
        $resolved = null;

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            $points = [];
            foreach ($product->getProductDeliveryPoints() as $link) {
                if (!$link instanceof ProductDeliveryPoint || !$link->isActive()) {
                    continue;
                }

                $point = $link->getDeliveryPoint();
                if (!$point->isActive() || $point->getId() === null) {
                    continue;
                }

                $points[$point->getId()] = $point;
            }

            if ($resolved === null) {
                $resolved = $points;
                continue;
            }

            if ($intersect) {
                $resolved = array_intersect_key($resolved, $points);
            } else {
                $resolved += $points;
            }
        }

        $resolved ??= [];

        uasort($resolved, static function (DeliveryPoint $left, DeliveryPoint $right): int {
            if ($left->getSortOrder() === $right->getSortOrder()) {
                return strcasecmp($left->getName(), $right->getName());
            }

            return $left->getSortOrder() <=> $right->getSortOrder();
        });

        return $resolved;
    }

    /**
     * @param array<int, DeliveryPoint> $points
     * @return array<int, list<array<string, mixed>>>
     */
    private function buildActiveTimeWindowsByPoint(array $points): array
    {
        $result = [];

        foreach ($points as $point) {
            if (!$point instanceof DeliveryPoint || $point->getId() === null) {
                continue;
            }

            $windows = [];
            foreach ($point->getTimeWindows() as $timeWindow) {
                if (!$timeWindow instanceof DeliveryPointTimeWindow || !$timeWindow->isActive() || $timeWindow->getId() === null) {
                    continue;
                }

                $windows[] = [
                    'id' => $timeWindow->getId(),
                    'label' => $this->formatTimeWindowLabel($timeWindow),
                    'weekday' => $timeWindow->getWeekday(),
                    'weekdayLabel' => $timeWindow->getWeekdayLabel(),
                    'startTime' => $timeWindow->getStartTime()->format('H:i'),
                    'endTime' => $timeWindow->getEndTime()->format('H:i'),
                    'sortOrder' => $timeWindow->getSortOrder(),
                ];
            }

            usort($windows, static function (array $left, array $right): int {
                if (($left['sortOrder'] ?? 0) === ($right['sortOrder'] ?? 0)) {
                    return strcmp((string) ($left['label'] ?? ''), (string) ($right['label'] ?? ''));
                }

                return ((int) ($left['sortOrder'] ?? 0)) <=> ((int) ($right['sortOrder'] ?? 0));
            });

            $result[$point->getId()] = $windows;
        }

        return $result;
    }
}
