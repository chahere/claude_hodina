<?php

namespace App\Dto;

/**
 * Résultat de calcul logistique pour un panier.
 *
 * J5F-B prépare la donnée métier. L'affichage panier et le gel dans
 * CustomerOrder seront branchés plus tard en J5G.
 */
final class CartLogisticsPreview
{
    public const RELATION_SAME_COMMUNE = 'SAME_COMMUNE';
    public const RELATION_NEIGHBOR_COMMUNE = 'NEIGHBOR_COMMUNE';
    public const RELATION_REMOTE_COMMUNE = 'REMOTE_COMMUNE';
    public const RELATION_OTHER_TERRITORY = 'OTHER_TERRITORY';
    public const RELATION_UNKNOWN = 'UNKNOWN';

    /** @param list<string> $warnings */
    public function __construct(
        public readonly bool $addressRequired,
        public readonly ?string $clientCommuneName,
        public readonly ?string $clientTerritory,
        public readonly bool $requiresBarge,
        public readonly bool $hasNeighborSeller,
        public readonly bool $hasRemoteSeller,
        public readonly bool $hasUnknownSellerCommune,
        public readonly string $relationLevel,
        public readonly ?float $estimatedDeliveryFee,
        public readonly ?float $estimatedCourierPayout,
        public readonly ?float $estimatedDeliveryMargin,
        public readonly ?string $pricingZoneName,
        public readonly ?string $pricingZoneCode,
        public readonly string $message,
        public readonly array $warnings = [],
        public readonly ?string $routeSummary = null,
        public readonly int $landHopCount = 0,
        public readonly int $bargeHopCount = 0,
        public readonly int $totalHopCount = 0,
        public readonly int $sellerCount = 0,
        public readonly int $collectionPointCount = 0,
        public readonly ?float $customerLocalDeliveryFee = null,
        public readonly ?float $courierLocalPayout = null,
        public readonly ?float $routeCustomerExtraFee = null,
        public readonly ?float $routeCourierExtraPayout = null,
        public readonly ?float $multiSellerCustomerExtraFee = null,
        public readonly ?float $customerDeliveryFeeCap = null,
        public readonly ?float $uncappedCustomerDeliveryFee = null,
        public readonly bool $customerDeliveryFeeCapApplied = false,
        public readonly ?float $courierPayoutCap = null,
        public readonly ?float $uncappedCourierPayout = null,
        public readonly bool $courierPayoutCapApplied = false,
        /** @var list<array<string, mixed>> */
        public readonly array $sellerRouteDetails = [],
        /** @var list<array<string, mixed>> */
        public readonly array $collectionRouteDetails = [],
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            addressRequired: (bool) ($data['addressRequired'] ?? true),
            clientCommuneName: isset($data['clientCommuneName']) ? (string) $data['clientCommuneName'] : null,
            clientTerritory: isset($data['clientTerritory']) ? (string) $data['clientTerritory'] : null,
            requiresBarge: (bool) ($data['requiresBarge'] ?? false),
            hasNeighborSeller: (bool) ($data['hasNeighborSeller'] ?? false),
            hasRemoteSeller: (bool) ($data['hasRemoteSeller'] ?? false),
            hasUnknownSellerCommune: (bool) ($data['hasUnknownSellerCommune'] ?? false),
            relationLevel: (string) ($data['relationLevel'] ?? self::RELATION_UNKNOWN),
            estimatedDeliveryFee: isset($data['estimatedDeliveryFee']) ? (float) $data['estimatedDeliveryFee'] : null,
            estimatedCourierPayout: isset($data['estimatedCourierPayout']) ? (float) $data['estimatedCourierPayout'] : null,
            estimatedDeliveryMargin: isset($data['estimatedDeliveryMargin']) ? (float) $data['estimatedDeliveryMargin'] : null,
            pricingZoneName: isset($data['pricingZoneName']) ? (string) $data['pricingZoneName'] : null,
            pricingZoneCode: isset($data['pricingZoneCode']) ? (string) $data['pricingZoneCode'] : null,
            message: (string) ($data['message'] ?? 'Hodina calculera les frais de livraison selon ton adresse et les vendeurs du panier.'),
            warnings: is_array($data['warnings'] ?? null) ? array_values($data['warnings']) : [],
            routeSummary: isset($data['routeSummary']) ? (string) $data['routeSummary'] : null,
            landHopCount: (int) ($data['landHopCount'] ?? 0),
            bargeHopCount: (int) ($data['bargeHopCount'] ?? 0),
            totalHopCount: (int) ($data['totalHopCount'] ?? 0),
            sellerCount: (int) ($data['sellerCount'] ?? 0),
            collectionPointCount: (int) ($data['collectionPointCount'] ?? 0),
            customerLocalDeliveryFee: isset($data['customerLocalDeliveryFee']) ? (float) $data['customerLocalDeliveryFee'] : null,
            courierLocalPayout: isset($data['courierLocalPayout']) ? (float) $data['courierLocalPayout'] : null,
            routeCustomerExtraFee: isset($data['routeCustomerExtraFee']) ? (float) $data['routeCustomerExtraFee'] : null,
            routeCourierExtraPayout: isset($data['routeCourierExtraPayout']) ? (float) $data['routeCourierExtraPayout'] : null,
            multiSellerCustomerExtraFee: isset($data['multiSellerCustomerExtraFee']) ? (float) $data['multiSellerCustomerExtraFee'] : null,
            customerDeliveryFeeCap: isset($data['customerDeliveryFeeCap']) ? (float) $data['customerDeliveryFeeCap'] : null,
            uncappedCustomerDeliveryFee: isset($data['uncappedCustomerDeliveryFee']) ? (float) $data['uncappedCustomerDeliveryFee'] : null,
            customerDeliveryFeeCapApplied: (bool) ($data['customerDeliveryFeeCapApplied'] ?? false),
            courierPayoutCap: isset($data['courierPayoutCap']) ? (float) $data['courierPayoutCap'] : null,
            uncappedCourierPayout: isset($data['uncappedCourierPayout']) ? (float) $data['uncappedCourierPayout'] : null,
            courierPayoutCapApplied: (bool) ($data['courierPayoutCapApplied'] ?? false),
            sellerRouteDetails: is_array($data['sellerRouteDetails'] ?? null) ? array_values($data['sellerRouteDetails']) : [],
            collectionRouteDetails: is_array($data['collectionRouteDetails'] ?? null) ? array_values($data['collectionRouteDetails']) : [],
        );
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'addressRequired' => $this->addressRequired,
            'clientCommuneName' => $this->clientCommuneName,
            'clientTerritory' => $this->clientTerritory,
            'requiresBarge' => $this->requiresBarge,
            'hasNeighborSeller' => $this->hasNeighborSeller,
            'hasRemoteSeller' => $this->hasRemoteSeller,
            'hasUnknownSellerCommune' => $this->hasUnknownSellerCommune,
            'relationLevel' => $this->relationLevel,
            'estimatedDeliveryFee' => $this->estimatedDeliveryFee,
            'estimatedCourierPayout' => $this->estimatedCourierPayout,
            'estimatedDeliveryMargin' => $this->estimatedDeliveryMargin,
            'pricingZoneName' => $this->pricingZoneName,
            'pricingZoneCode' => $this->pricingZoneCode,
            'message' => $this->message,
            'warnings' => $this->warnings,
            'routeSummary' => $this->routeSummary,
            'landHopCount' => $this->landHopCount,
            'bargeHopCount' => $this->bargeHopCount,
            'totalHopCount' => $this->totalHopCount,
            'sellerCount' => $this->sellerCount,
            'collectionPointCount' => $this->collectionPointCount,
            'customerLocalDeliveryFee' => $this->customerLocalDeliveryFee,
            'courierLocalPayout' => $this->courierLocalPayout,
            'routeCustomerExtraFee' => $this->routeCustomerExtraFee,
            'routeCourierExtraPayout' => $this->routeCourierExtraPayout,
            'multiSellerCustomerExtraFee' => $this->multiSellerCustomerExtraFee,
            'customerDeliveryFeeCap' => $this->customerDeliveryFeeCap,
            'uncappedCustomerDeliveryFee' => $this->uncappedCustomerDeliveryFee,
            'customerDeliveryFeeCapApplied' => $this->customerDeliveryFeeCapApplied,
            'courierPayoutCap' => $this->courierPayoutCap,
            'uncappedCourierPayout' => $this->uncappedCourierPayout,
            'courierPayoutCapApplied' => $this->courierPayoutCapApplied,
            'sellerRouteDetails' => $this->sellerRouteDetails,
            'collectionRouteDetails' => $this->collectionRouteDetails,
        ];
    }
}
