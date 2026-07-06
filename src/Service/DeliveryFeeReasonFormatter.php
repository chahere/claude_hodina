<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CustomerOrder;

final class DeliveryFeeReasonFormatter
{
    /** @return list<string> */
    public function reasonsFromOrder(CustomerOrder $order): array
    {
        $snapshot = $order->getDeliveryLogisticsSnapshot();
        if (!is_array($snapshot)) {
            return [];
        }

        $preview = $snapshot['preview'] ?? $snapshot;

        return is_array($preview) ? $this->reasonsFromPreviewArray($preview) : [];
    }

    public function formatFromOrder(CustomerOrder $order): ?string
    {
        return $this->formatReasons($this->reasonsFromOrder($order));
    }

    /**
     * @param array<string, mixed> $preview
     * @return list<string>
     */
    public function reasonsFromPreviewArray(array $preview): array
    {
        $reasons = [];
        $landHopCount = max(0, (int) ($preview['landHopCount'] ?? 0));
        $collectionPointCount = max(0, (int) ($preview['collectionPointCount'] ?? 0));
        $requiresBarge = (bool) ($preview['requiresBarge'] ?? false);
        $bargeHopCount = max(0, (int) ($preview['bargeHopCount'] ?? 0));

        if ($landHopCount > 0) {
            $reasons[] = sprintf(
                '%d commune%s traversée%s',
                $landHopCount,
                $landHopCount > 1 ? 's' : '',
                $landHopCount > 1 ? 's' : ''
            );
        } elseif ($collectionPointCount > 1) {
            $reasons[] = 'plusieurs communes de collecte';
        }

        if ($requiresBarge || $bargeHopCount > 0) {
            $reasons[] = 'barge';
        }

        return $reasons;
    }

    /** @param list<string> $reasons */
    public function formatReasons(array $reasons): ?string
    {
        $reasons = array_values(array_filter(array_map(
            static fn (string $reason): string => trim($reason),
            $reasons
        )));

        if ($reasons === []) {
            return null;
        }

        return 'Inclus : '.implode(' + ', $reasons).'.';
    }
}
