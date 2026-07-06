<?php

namespace App\Service;

use App\Dto\CartLogisticsPreview;
use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\DeliveryCommune;
use App\Entity\DeliveryCommuneConnection;
use App\Entity\DeliveryPricingZone;
use App\Entity\HodinaSetting;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service métier pour centraliser les règles logistiques Hodina.
 *
 * J5G-B4 : le service ne s'appuie plus sur l'ancien voisinage simple
 * DeliveryCommune.neighboringCommunes pour qualifier les trajets. Il lit la
 * carte logistique éditable en base via delivery_commune_connection.
 *
 * Règle structurante conservée : la barge reste d'abord une règle métier
 * territoriale Petite-Terre / Grande-Terre. Le chemin détecté sert à expliquer
 * le trajet et à récupérer les suppléments éventuels portés par les liaisons.
 */
final class DeliveryLogisticsService
{
    /** @var array<string, int> */
    private const RELATION_PRIORITY = [
        CartLogisticsPreview::RELATION_UNKNOWN => 0,
        CartLogisticsPreview::RELATION_SAME_COMMUNE => 1,
        CartLogisticsPreview::RELATION_NEIGHBOR_COMMUNE => 2,
        CartLogisticsPreview::RELATION_REMOTE_COMMUNE => 3,
        CartLogisticsPreview::RELATION_OTHER_TERRITORY => 4,
    ];

    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {}

    /**
     * Produit un aperçu logistique pour un panier détaillé.
     *
     * Le format attendu est celui de CartService::getDetailedCart() :
     * [
     *   'items' => [
     *     ['product' => Product, 'qty' => 1, ...],
     *   ],
     * ]
     *
     * J5G-B4 calcule désormais le trajet vendeur -> client depuis les
     * liaisons réelles delivery_commune_connection. Le checkout figera plus
     * tard les valeurs définitives dans CustomerOrder.
     *
     * @param array<string, mixed> $detailedCart
     */
    public function previewForCart(?Address $address, array $detailedCart): CartLogisticsPreview
    {
        if (!$address) {
            return $this->addressRequiredPreview();
        }

        $clientCommune = $this->findActiveCommuneByName($address->getCommune());
        if (!$clientCommune) {
            return new CartLogisticsPreview(
                addressRequired: false,
                clientCommuneName: $address->getCommune(),
                clientTerritory: null,
                requiresBarge: false,
                hasNeighborSeller: false,
                hasRemoteSeller: false,
                hasUnknownSellerCommune: true,
                relationLevel: CartLogisticsPreview::RELATION_UNKNOWN,
                estimatedDeliveryFee: null,
                estimatedCourierPayout: null,
                estimatedDeliveryMargin: null,
                pricingZoneName: null,
                pricingZoneCode: null,
                message: 'Cette commune de livraison doit encore être paramétrée par Hodina pour calculer les frais automatiquement.',
                warnings: [sprintf('Commune client non paramétrée : %s', $address->getCommune())],
            );
        }

        $relationLevel = CartLogisticsPreview::RELATION_SAME_COMMUNE;
        $requiresBarge = false;
        $hasNeighborSeller = false;
        $hasRemoteSeller = false;
        $hasUnknownSellerCommune = false;
        $warnings = [];
        $sellerRouteDetails = [];
        /** @var array<string, bool> $distinctSellerKeys */
        $distinctSellerKeys = [];
        /** @var array<string, bool> $distinctSellerCommuneKeys */
        $distinctSellerCommuneKeys = [];
        $maxLandHopCount = 0;
        $maxBargeHopCount = 0;
        $maxTotalHopCount = 0;
        $maxCustomerExtraFee = 0.0;
        $maxCourierExtraPayout = 0.0;
        $maxLandCustomerExtraFee = 0.0;
        $maxLandCourierExtraPayout = 0.0;
        $maxBargeCustomerExtraFee = 0.0;
        $maxBargeCourierExtraPayout = 0.0;

        foreach (($detailedCart['items'] ?? []) as $item) {
            if (!is_array($item) || !isset($item['product']) || !$item['product'] instanceof Product) {
                continue;
            }

            $product = $item['product'];
            $seller = $product->getSeller();
            $sellerKey = $seller->getId() !== null
                ? sprintf('id:%d', $seller->getId())
                : sprintf('name:%s', $this->normalizeCommuneName($seller->getName()));
            $distinctSellerKeys[$sellerKey] = true;

            $sellerCommune = $this->resolveSellerLogisticsCommune($product);

            if ($sellerCommune && $sellerCommune->isActive()) {
                $communeKey = $sellerCommune->getId() !== null
                    ? sprintf('id:%d', $sellerCommune->getId())
                    : sprintf('name:%s', $this->normalizeCommuneName($sellerCommune->getName()));
                $distinctSellerCommuneKeys[$communeKey] = true;
            }

            if (!$sellerCommune || !$sellerCommune->isActive()) {
                $hasUnknownSellerCommune = true;
                $warnings[] = sprintf(
                    'Commune logistique manquante ou inactive pour le vendeur "%s".',
                    $seller->getName(),
                );
                $relation = CartLogisticsPreview::RELATION_UNKNOWN;
                $route = null;
            } else {
                $route = $this->findShortestRoute($sellerCommune, $clientCommune);
                $relation = $this->getCommuneRelationFromRoute($clientCommune, $sellerCommune, $route);

                if (!$route['found']) {
                    $warnings[] = sprintf(
                        'Aucun trajet logistique actif trouvé entre "%s" et "%s".',
                        $sellerCommune->getName(),
                        $clientCommune->getName(),
                    );
                }
            }

            if ($route !== null) {
                $territoryRequiresBarge = $clientCommune->getTerritory() !== $sellerCommune?->getTerritory();
                $requiresBarge = $requiresBarge || $territoryRequiresBarge || (bool) $route['requiresBarge'];
                $maxLandHopCount = max($maxLandHopCount, (int) $route['landHopCount']);
                $maxBargeHopCount = max($maxBargeHopCount, (int) $route['bargeHopCount']);
                $maxTotalHopCount = max($maxTotalHopCount, (int) $route['totalHopCount']);
                $maxCustomerExtraFee = max($maxCustomerExtraFee, (float) ($route['customerExtraFee'] ?? 0.0));
                $maxCourierExtraPayout = max($maxCourierExtraPayout, (float) ($route['courierExtraPayout'] ?? 0.0));
                $maxLandCustomerExtraFee = max($maxLandCustomerExtraFee, (float) ($route['landCustomerExtraFee'] ?? 0.0));
                $maxLandCourierExtraPayout = max($maxLandCourierExtraPayout, (float) ($route['landCourierExtraPayout'] ?? 0.0));
                $maxBargeCustomerExtraFee = max($maxBargeCustomerExtraFee, (float) ($route['bargeCustomerExtraFee'] ?? 0.0));
                $maxBargeCourierExtraPayout = max($maxBargeCourierExtraPayout, (float) ($route['bargeCourierExtraPayout'] ?? 0.0));

                if ($territoryRequiresBarge && $route['found'] && !(bool) $route['requiresBarge']) {
                    $warnings[] = sprintf(
                        'Trajet PT/GT détecté entre "%s" et "%s", mais aucune liaison BARGE active n’est présente dans le chemin.',
                        $sellerCommune?->getName(),
                        $clientCommune->getName(),
                    );
                }

                $sellerRouteDetails[] = [
                    'sellerName' => $seller->getName(),
                    'sellerCommuneName' => $sellerCommune?->getName(),
                    'clientCommuneName' => $clientCommune->getName(),
                    'found' => (bool) $route['found'],
                    'requiresBarge' => (bool) $route['requiresBarge'],
                    'landHopCount' => (int) $route['landHopCount'],
                    'bargeHopCount' => (int) $route['bargeHopCount'],
                    'totalHopCount' => (int) $route['totalHopCount'],
                    'customerExtraFee' => (float) ($route['customerExtraFee'] ?? 0.0),
                    'courierExtraPayout' => (float) ($route['courierExtraPayout'] ?? 0.0),
                    'landCustomerExtraFee' => (float) ($route['landCustomerExtraFee'] ?? 0.0),
                    'landCourierExtraPayout' => (float) ($route['landCourierExtraPayout'] ?? 0.0),
                    'bargeCustomerExtraFee' => (float) ($route['bargeCustomerExtraFee'] ?? 0.0),
                    'bargeCourierExtraPayout' => (float) ($route['bargeCourierExtraPayout'] ?? 0.0),
                    'pathNames' => $route['pathNames'],
                    'linkTypes' => $route['linkTypes'],
                    'summary' => $route['summary'],
                ];
            }

            if ($relation === CartLogisticsPreview::RELATION_OTHER_TERRITORY) {
                $requiresBarge = true;
            }

            if ($relation === CartLogisticsPreview::RELATION_NEIGHBOR_COMMUNE) {
                $hasNeighborSeller = true;
            }

            if ($relation === CartLogisticsPreview::RELATION_REMOTE_COMMUNE) {
                $hasRemoteSeller = true;
            }

            if (self::RELATION_PRIORITY[$relation] > self::RELATION_PRIORITY[$relationLevel]) {
                $relationLevel = $relation;
            }
        }

        $pricingZone = $this->getPricingZoneForRequirement($clientCommune, $requiresBarge);
        if (!$pricingZone->isActive()) {
            $warnings[] = sprintf('Zone tarifaire inactive : %s.', $pricingZone->getCode());
        }

        $collectionRouteDetails = $this->buildCollectionRouteDetails($sellerRouteDetails);
        $sellerCount = count($distinctSellerKeys);
        $collectionPointCount = count($distinctSellerCommuneKeys);

        $deliveryAmounts = $this->calculateDeliveryAmounts(
            $clientCommune,
            $maxCustomerExtraFee,
            $maxCourierExtraPayout,
            $collectionPointCount,
        );

        if ($requiresBarge && $maxBargeCustomerExtraFee <= 0.0) {
            $warnings[] = 'Coût de barge non renseigné sur la liaison BARGE : seul le forfait local de la commune livrée est appliqué.';
        }

        return new CartLogisticsPreview(
            addressRequired: false,
            clientCommuneName: $clientCommune->getName(),
            clientTerritory: $clientCommune->getTerritory(),
            requiresBarge: $requiresBarge,
            hasNeighborSeller: $hasNeighborSeller,
            hasRemoteSeller: $hasRemoteSeller,
            hasUnknownSellerCommune: $hasUnknownSellerCommune,
            relationLevel: $relationLevel,
            estimatedDeliveryFee: $deliveryAmounts['customerDeliveryFee'],
            estimatedCourierPayout: $deliveryAmounts['courierPayout'],
            estimatedDeliveryMargin: $deliveryAmounts['deliveryMargin'],
            pricingZoneName: $pricingZone->getName(),
            pricingZoneCode: $pricingZone->getCode(),
            message: $this->buildMessage($relationLevel, $hasUnknownSellerCommune, $maxLandHopCount, $maxBargeHopCount),
            warnings: array_values(array_unique($warnings)),
            routeSummary: $this->buildRouteSummary($collectionRouteDetails),
            landHopCount: $maxLandHopCount,
            bargeHopCount: $maxBargeHopCount,
            totalHopCount: $maxTotalHopCount,
            sellerCount: $sellerCount,
            collectionPointCount: $collectionPointCount,
            customerLocalDeliveryFee: $deliveryAmounts['customerLocalDeliveryFee'],
            courierLocalPayout: $deliveryAmounts['courierLocalPayout'],
            routeCustomerExtraFee: $deliveryAmounts['routeCustomerExtraFee'],
            routeCourierExtraPayout: $deliveryAmounts['routeCourierExtraPayout'],
            multiSellerCustomerExtraFee: $deliveryAmounts['multiSellerCustomerExtraFee'],
            customerDeliveryFeeCap: $deliveryAmounts['customerDeliveryFeeCap'],
            uncappedCustomerDeliveryFee: $deliveryAmounts['uncappedCustomerDeliveryFee'],
            customerDeliveryFeeCapApplied: $deliveryAmounts['customerDeliveryFeeCapApplied'],
            courierPayoutCap: $deliveryAmounts['courierPayoutCap'],
            uncappedCourierPayout: $deliveryAmounts['uncappedCourierPayout'],
            courierPayoutCapApplied: $deliveryAmounts['courierPayoutCapApplied'],
            sellerRouteDetails: $sellerRouteDetails,
            collectionRouteDetails: $collectionRouteDetails,
        );
    }

    public function previewForOrder(CustomerOrder $order): CartLogisticsPreview
    {
        $address = $order->getDeliveryAddress();

        if (!$address instanceof Address) {
            $address = (new Address())
                ->setCustomer($order->getCustomer())
                ->setLine1((string) ($order->getDeliveryAddressLine1() ?? ''))
                ->setPostalCode((string) ($order->getDeliveryAddressPostalCode() ?? ''))
                ->setCommune((string) ($order->getDeliveryAddressCommune() ?? ''));
        }

        $items = [];
        foreach ($order->getItems() as $orderItem) {
            $items[] = [
                'product' => $orderItem->getProduct(),
                'qty' => $orderItem->getQuantity(),
            ];
        }

        return $this->previewForCart($address, ['items' => $items]);
    }

    public function getCommuneRelation(DeliveryCommune $clientCommune, ?DeliveryCommune $sellerCommune): string
    {
        if (!$sellerCommune || !$sellerCommune->isActive()) {
            return CartLogisticsPreview::RELATION_UNKNOWN;
        }

        return $this->getCommuneRelationFromRoute(
            $clientCommune,
            $sellerCommune,
            $this->findShortestRoute($sellerCommune, $clientCommune),
        );
    }

    public function requiresBarge(DeliveryCommune $clientCommune, DeliveryCommune $sellerCommune): bool
    {
        if ($clientCommune->getTerritory() !== $sellerCommune->getTerritory()) {
            return true;
        }

        $route = $this->findShortestRoute($sellerCommune, $clientCommune);

        return $route['found'] && (bool) $route['requiresBarge'];
    }

    /**
     * Source de vérité verrouillée pour le calcul des trajets et des coûts.
     *
     * J5M-C2 ajoute une adresse de retrait vendeur pour guider le livreur sur
     * le terrain. Cette adresse ne doit jamais remplacer la commune logistique
     * du vendeur dans les calculs J5G-B4 : coûts, barge, BFS, communes de
     * collecte distinctes et snapshot logistique restent basés sur
     * Seller::deliveryCommune.
     */
    private function resolveSellerLogisticsCommune(Product $product): ?DeliveryCommune
    {
        return $product->getSeller()->getDeliveryCommune();
    }

    public function getPricingZoneForRequirement(DeliveryCommune $clientCommune, bool $requiresBarge): DeliveryPricingZone
    {
        // J5G-E1D / J5W-A : la commune livrée reste la source de vérité tarifaire.
        // Même si une barge est détectée, on part toujours du forfait local
        // de la commune du client (MAMOUDZOU_LOCAL, NORD_LOCAL, CENTRE_LOCAL,
        // SUD_LOCAL, ou zones historiques PT_LOCAL/GT_LOCAL),
        // puis on ajoute le coût de trajet porté par les liaisons logistiques
        // LAND/BARGE. L'ancien champ bargePricingZone est conservé pour
        // compatibilité admin / historique, mais il ne doit plus remplacer le
        // forfait local pendant le pilote.
        return $clientCommune->getLocalPricingZone();
    }

    private function addressRequiredPreview(): CartLogisticsPreview
    {
        return new CartLogisticsPreview(
            addressRequired: true,
            clientCommuneName: null,
            clientTerritory: null,
            requiresBarge: false,
            hasNeighborSeller: false,
            hasRemoteSeller: false,
            hasUnknownSellerCommune: false,
            relationLevel: CartLogisticsPreview::RELATION_UNKNOWN,
            estimatedDeliveryFee: null,
            estimatedCourierPayout: null,
            estimatedDeliveryMargin: null,
            pricingZoneName: null,
            pricingZoneCode: null,
            message: 'Choisis une adresse de livraison pour estimer les frais et les contraintes logistiques.',
        );
    }

    private function findActiveCommuneByName(string $name): ?DeliveryCommune
    {
        $normalized = $this->normalizeCommuneName($name);

        if ($normalized === '') {
            return null;
        }

        $communes = $this->entityManager
            ->createQueryBuilder()
            ->select('c')
            ->from(DeliveryCommune::class, 'c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        foreach ($communes as $commune) {
            if (!$commune instanceof DeliveryCommune) {
                continue;
            }

            if ($this->normalizeCommuneName($commune->getName()) === $normalized) {
                return $commune;
            }

            if ($commune->getSlug() !== null && $this->normalizeCommuneName($commune->getSlug()) === $normalized) {
                return $commune;
            }
        }

        return null;
    }

    /**
     * Trouve le plus court chemin entre deux communes à partir de la carte
     * delivery_commune_connection.
     *
     * L'algorithme est volontairement simple : la carte pilote est petite.
     * Il tient tout de même compte de hopCount si un lien est pondéré plus tard.
     *
     * @return array{
     *   found: bool,
     *   pathIds: list<int>,
     *   pathNames: list<string>,
     *   linkTypes: list<string>,
     *   requiresBarge: bool,
     *   landHopCount: int,
     *   bargeHopCount: int,
     *   totalHopCount: int,
     *   customerExtraFee: float,
     *   courierExtraPayout: float,
     *   landCustomerExtraFee: float,
     *   landCourierExtraPayout: float,
     *   bargeCustomerExtraFee: float,
     *   bargeCourierExtraPayout: float,
     *   summary: string|null
     * }
     */
    private function findShortestRoute(DeliveryCommune $fromCommune, DeliveryCommune $toCommune): array
    {
        $fromId = $fromCommune->getId();
        $toId = $toCommune->getId();

        if ($fromId === null || $toId === null) {
            return $this->missingRouteResult();
        }

        if ($fromId === $toId) {
            return [
                'found' => true,
                'pathIds' => [$fromId],
                'pathNames' => [$fromCommune->getName()],
                'linkTypes' => [],
                'requiresBarge' => false,
                'landHopCount' => 0,
                'bargeHopCount' => 0,
                'totalHopCount' => 0,
                'customerExtraFee' => 0.0,
                'courierExtraPayout' => 0.0,
                'landCustomerExtraFee' => 0.0,
                'landCourierExtraPayout' => 0.0,
                'bargeCustomerExtraFee' => 0.0,
                'bargeCourierExtraPayout' => 0.0,
                'summary' => sprintf('%s uniquement', $fromCommune->getName()),
            ];
        }

        $communesById = $this->loadActiveCommunesById();
        $graph = $this->buildConnectionGraph();

        if (!isset($communesById[$fromId], $communesById[$toId])) {
            return $this->missingRouteResult();
        }

        /** @var array<int, int> $distances */
        $distances = [$fromId => 0];
        /** @var array<int, array{previous: int, linkType: string, customerExtraFee: float, courierExtraPayout: float}> $previous */
        $previous = [];
        /** @var list<int> $queue */
        $queue = [$fromId];
        /** @var array<int, bool> $visited */
        $visited = [];

        while ($queue !== []) {
            usort($queue, static fn (int $a, int $b): int => ($distances[$a] ?? PHP_INT_MAX) <=> ($distances[$b] ?? PHP_INT_MAX));
            $currentId = array_shift($queue);

            if ($currentId === null || isset($visited[$currentId])) {
                continue;
            }

            if ($currentId === $toId) {
                break;
            }

            $visited[$currentId] = true;

            foreach (($graph[$currentId] ?? []) as $edge) {
                $neighborId = $edge['to'];
                $weight = max(1, $edge['weight']);
                $candidateDistance = ($distances[$currentId] ?? PHP_INT_MAX) + $weight;

                if ($candidateDistance < ($distances[$neighborId] ?? PHP_INT_MAX)) {
                    $distances[$neighborId] = $candidateDistance;
                    $previous[$neighborId] = [
                        'previous' => $currentId,
                        'linkType' => $edge['linkType'],
                        'customerExtraFee' => $edge['customerExtraFee'],
                        'courierExtraPayout' => $edge['courierExtraPayout'],
                    ];
                    $queue[] = $neighborId;
                }
            }
        }

        if (!isset($previous[$toId])) {
            return $this->missingRouteResult();
        }

        $pathIds = [$toId];
        $linkTypes = [];
        $customerExtraFees = [];
        $courierExtraPayouts = [];
        $cursor = $toId;

        while ($cursor !== $fromId && isset($previous[$cursor])) {
            $linkTypes[] = $previous[$cursor]['linkType'];
            $customerExtraFees[] = (float) $previous[$cursor]['customerExtraFee'];
            $courierExtraPayouts[] = (float) $previous[$cursor]['courierExtraPayout'];
            $cursor = $previous[$cursor]['previous'];
            array_unshift($pathIds, $cursor);
        }

        $linkTypes = array_reverse($linkTypes);
        $customerExtraFees = array_reverse($customerExtraFees);
        $courierExtraPayouts = array_reverse($courierExtraPayouts);
        $pathNames = [];

        foreach ($pathIds as $pathId) {
            $pathNames[] = $communesById[$pathId]->getName();
        }

        $landHopCount = 0;
        $bargeHopCount = 0;
        $landCustomerExtraFee = 0.0;
        $landCourierExtraPayout = 0.0;
        $bargeCustomerExtraFee = 0.0;
        $bargeCourierExtraPayout = 0.0;

        foreach ($linkTypes as $index => $linkType) {
            $customerExtraFee = (float) ($customerExtraFees[$index] ?? 0.0);
            $courierExtraPayout = (float) ($courierExtraPayouts[$index] ?? 0.0);

            if ($linkType === DeliveryCommuneConnection::LINK_TYPE_BARGE) {
                ++$bargeHopCount;
                $bargeCustomerExtraFee += $customerExtraFee;
                $bargeCourierExtraPayout += $courierExtraPayout;
            } else {
                ++$landHopCount;
                $landCustomerExtraFee += $customerExtraFee;
                $landCourierExtraPayout += $courierExtraPayout;
            }
        }

        $customerExtraFee = round($landCustomerExtraFee + $bargeCustomerExtraFee, 2);
        $courierExtraPayout = round($landCourierExtraPayout + $bargeCourierExtraPayout, 2);

        return [
            'found' => true,
            'pathIds' => $pathIds,
            'pathNames' => $pathNames,
            'linkTypes' => $linkTypes,
            'requiresBarge' => $bargeHopCount > 0,
            'landHopCount' => $landHopCount,
            'bargeHopCount' => $bargeHopCount,
            'totalHopCount' => count($linkTypes),
            'customerExtraFee' => $customerExtraFee,
            'courierExtraPayout' => $courierExtraPayout,
            'landCustomerExtraFee' => round($landCustomerExtraFee, 2),
            'landCourierExtraPayout' => round($landCourierExtraPayout, 2),
            'bargeCustomerExtraFee' => round($bargeCustomerExtraFee, 2),
            'bargeCourierExtraPayout' => round($bargeCourierExtraPayout, 2),
            'summary' => $this->buildReadableRoutePath($pathNames, $linkTypes),
        ];
    }

    /**
     * Construit le chemin lisible en plaçant la barge sur la liaison concernée.
     *
     * Exemple : Labattoir → Dzaoudzi -barge-> Mamoudzou.
     *
     * @param list<string> $pathNames
     * @param list<string> $linkTypes
     */
    private function buildReadableRoutePath(array $pathNames, array $linkTypes): string
    {
        if ($pathNames === []) {
            return '';
        }

        $summary = (string) $pathNames[0];

        foreach ($linkTypes as $index => $linkType) {
            $nextCommuneName = $pathNames[$index + 1] ?? null;

            if ($nextCommuneName === null || $nextCommuneName === '') {
                continue;
            }

            $summary .= $linkType === DeliveryCommuneConnection::LINK_TYPE_BARGE
                ? ' -barge-> '.$nextCommuneName
                : ' → '.$nextCommuneName;
        }

        return $summary;
    }

    /** @return array<int, DeliveryCommune> */
    private function loadActiveCommunesById(): array
    {
        $communes = $this->entityManager
            ->createQueryBuilder()
            ->select('c')
            ->from(DeliveryCommune::class, 'c')
            ->where('c.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $byId = [];

        foreach ($communes as $commune) {
            if ($commune instanceof DeliveryCommune && $commune->getId() !== null) {
                $byId[$commune->getId()] = $commune;
            }
        }

        return $byId;
    }

    /**
     * @return array<int, list<array{to: int, linkType: string, weight: int, customerExtraFee: float, courierExtraPayout: float}>>
     */
    private function buildConnectionGraph(): array
    {
        $connections = $this->entityManager
            ->createQueryBuilder()
            ->select('cc', 'fc', 'tc')
            ->from(DeliveryCommuneConnection::class, 'cc')
            ->join('cc.fromCommune', 'fc')
            ->join('cc.toCommune', 'tc')
            ->where('cc.isActive = :active')
            ->andWhere('fc.isActive = :active')
            ->andWhere('tc.isActive = :active')
            ->setParameter('active', true)
            ->getQuery()
            ->getResult();

        $graph = [];
        $defaultLandCustomerExtraFee = $this->getGlobalCommuneCrossingCustomerFee();
        $defaultLandCourierExtraPayout = $this->getGlobalCommuneCrossingCourierPayout();

        foreach ($connections as $connection) {
            if (!$connection instanceof DeliveryCommuneConnection) {
                continue;
            }

            $fromId = $connection->getFromCommune()->getId();
            $toId = $connection->getToCommune()->getId();

            if ($fromId === null || $toId === null) {
                continue;
            }

            $this->addGraphEdge(
                $graph,
                $fromId,
                $toId,
                $connection->getLinkType(),
                $connection->getHopCount(),
                $connection->getCustomerExtraFee(),
                $connection->getCourierExtraPayout(),
                $defaultLandCustomerExtraFee,
                $defaultLandCourierExtraPayout,
            );

            if ($connection->isBidirectional()) {
                $this->addGraphEdge(
                    $graph,
                    $toId,
                    $fromId,
                    $connection->getLinkType(),
                    $connection->getHopCount(),
                    $connection->getCustomerExtraFee(),
                    $connection->getCourierExtraPayout(),
                    $defaultLandCustomerExtraFee,
                    $defaultLandCourierExtraPayout,
                );
            }
        }

        return $graph;
    }

    /**
     * @param array<int, list<array{to: int, linkType: string, weight: int, customerExtraFee: float, courierExtraPayout: float}>> $graph
     */
    private function addGraphEdge(
        array &$graph,
        int $fromId,
        int $toId,
        string $linkType,
        int $weight,
        string|float|int|null $customerExtraFee,
        string|float|int|null $courierExtraPayout,
        float $defaultLandCustomerExtraFee,
        float $defaultLandCourierExtraPayout,
    ): void {
        $normalizedLinkType = mb_strtoupper(trim($linkType));

        // Fallback global uniquement pour les liaisons terrestres entre communes.
        // La barge garde son coût spécifique sur la liaison BARGE afin de ne pas
        // mélanger le coût inter-communes et le coût de traversée maritime.
        $useGlobalLandFallback = $normalizedLinkType === DeliveryCommuneConnection::LINK_TYPE_LAND;

        $graph[$fromId][] = [
            'to' => $toId,
            'linkType' => $normalizedLinkType,
            'weight' => max(1, $weight),
            'customerExtraFee' => $this->resolveConnectionExtraFee($customerExtraFee, $useGlobalLandFallback ? $defaultLandCustomerExtraFee : 0.0),
            'courierExtraPayout' => $this->resolveConnectionExtraFee($courierExtraPayout, $useGlobalLandFallback ? $defaultLandCourierExtraPayout : 0.0),
        ];
    }

    /**
     * @param array{
     *   found: bool,
     *   pathIds: list<int>,
     *   pathNames: list<string>,
     *   linkTypes: list<string>,
     *   requiresBarge: bool,
     *   landHopCount: int,
     *   bargeHopCount: int,
     *   totalHopCount: int,
     *   summary: string|null
     * }|null $route
     */
    private function getCommuneRelationFromRoute(DeliveryCommune $clientCommune, DeliveryCommune $sellerCommune, ?array $route): string
    {
        if (!$sellerCommune->isActive()) {
            return CartLogisticsPreview::RELATION_UNKNOWN;
        }

        if ($clientCommune->getId() !== null && $clientCommune->getId() === $sellerCommune->getId()) {
            return CartLogisticsPreview::RELATION_SAME_COMMUNE;
        }

        if ($clientCommune->getTerritory() !== $sellerCommune->getTerritory()) {
            return CartLogisticsPreview::RELATION_OTHER_TERRITORY;
        }

        if (!$route || !$route['found']) {
            return CartLogisticsPreview::RELATION_REMOTE_COMMUNE;
        }

        if ($route['requiresBarge']) {
            return CartLogisticsPreview::RELATION_OTHER_TERRITORY;
        }

        if ((int) $route['totalHopCount'] === 1) {
            return CartLogisticsPreview::RELATION_NEIGHBOR_COMMUNE;
        }

        return CartLogisticsPreview::RELATION_REMOTE_COMMUNE;
    }

    /**
     * @return array{
     *   found: bool,
     *   pathIds: list<int>,
     *   pathNames: list<string>,
     *   linkTypes: list<string>,
     *   requiresBarge: bool,
     *   landHopCount: int,
     *   bargeHopCount: int,
     *   totalHopCount: int,
     *   bargeCustomerExtraFee: float,
     *   bargeCourierExtraPayout: float,
     *   summary: string|null
     * }
     */
    private function missingRouteResult(): array
    {
        return [
            'found' => false,
            'pathIds' => [],
            'pathNames' => [],
            'linkTypes' => [],
            'requiresBarge' => false,
            'landHopCount' => 0,
            'bargeHopCount' => 0,
            'totalHopCount' => 0,
            'customerExtraFee' => 0.0,
            'courierExtraPayout' => 0.0,
            'landCustomerExtraFee' => 0.0,
            'landCourierExtraPayout' => 0.0,
            'bargeCustomerExtraFee' => 0.0,
            'bargeCourierExtraPayout' => 0.0,
            'summary' => null,
        ];
    }

    /**
     * Agrège les lignes panier par commune de collecte pour éviter que
     * l'affichage confonde articles, vendeurs et communes logistiques.
     *
     * @param list<array<string, mixed>> $sellerRouteDetails
     * @return list<array<string, mixed>>
     */
    private function buildCollectionRouteDetails(array $sellerRouteDetails): array
    {
        $collectionRoutes = [];

        foreach ($sellerRouteDetails as $detail) {
            $communeName = isset($detail['sellerCommuneName']) ? trim((string) $detail['sellerCommuneName']) : '';
            $summary = isset($detail['summary']) ? trim((string) $detail['summary']) : '';
            $key = $communeName !== ''
                ? sprintf('commune:%s', $this->normalizeCommuneName($communeName))
                : sprintf('unknown:%s', $this->normalizeCommuneName((string) ($detail['sellerName'] ?? 'vendeur')));

            if (!isset($collectionRoutes[$key])) {
                $collectionRoutes[$key] = [
                    'sellerCommuneName' => $communeName !== '' ? $communeName : null,
                    'clientCommuneName' => $detail['clientCommuneName'] ?? null,
                    'found' => (bool) ($detail['found'] ?? false),
                    'requiresBarge' => (bool) ($detail['requiresBarge'] ?? false),
                    'landHopCount' => (int) ($detail['landHopCount'] ?? 0),
                    'bargeHopCount' => (int) ($detail['bargeHopCount'] ?? 0),
                    'totalHopCount' => (int) ($detail['totalHopCount'] ?? 0),
                    'customerExtraFee' => (float) ($detail['customerExtraFee'] ?? 0.0),
                    'courierExtraPayout' => (float) ($detail['courierExtraPayout'] ?? 0.0),
                    'landCustomerExtraFee' => (float) ($detail['landCustomerExtraFee'] ?? 0.0),
                    'landCourierExtraPayout' => (float) ($detail['landCourierExtraPayout'] ?? 0.0),
                    'bargeCustomerExtraFee' => (float) ($detail['bargeCustomerExtraFee'] ?? 0.0),
                    'bargeCourierExtraPayout' => (float) ($detail['bargeCourierExtraPayout'] ?? 0.0),
                    'summary' => $summary !== '' ? $summary : null,
                    'sellerNames' => [],
                ];
            }

            $sellerName = trim((string) ($detail['sellerName'] ?? ''));
            if ($sellerName !== '') {
                $collectionRoutes[$key]['sellerNames'][$this->normalizeCommuneName($sellerName)] = $sellerName;
            }

            $collectionRoutes[$key]['found'] = (bool) $collectionRoutes[$key]['found'] || (bool) ($detail['found'] ?? false);
            $collectionRoutes[$key]['requiresBarge'] = (bool) $collectionRoutes[$key]['requiresBarge'] || (bool) ($detail['requiresBarge'] ?? false);
            $collectionRoutes[$key]['landHopCount'] = max((int) $collectionRoutes[$key]['landHopCount'], (int) ($detail['landHopCount'] ?? 0));
            $collectionRoutes[$key]['bargeHopCount'] = max((int) $collectionRoutes[$key]['bargeHopCount'], (int) ($detail['bargeHopCount'] ?? 0));
            $collectionRoutes[$key]['totalHopCount'] = max((int) $collectionRoutes[$key]['totalHopCount'], (int) ($detail['totalHopCount'] ?? 0));
            $collectionRoutes[$key]['customerExtraFee'] = max((float) $collectionRoutes[$key]['customerExtraFee'], (float) ($detail['customerExtraFee'] ?? 0.0));
            $collectionRoutes[$key]['courierExtraPayout'] = max((float) $collectionRoutes[$key]['courierExtraPayout'], (float) ($detail['courierExtraPayout'] ?? 0.0));
            $collectionRoutes[$key]['landCustomerExtraFee'] = max((float) $collectionRoutes[$key]['landCustomerExtraFee'], (float) ($detail['landCustomerExtraFee'] ?? 0.0));
            $collectionRoutes[$key]['landCourierExtraPayout'] = max((float) $collectionRoutes[$key]['landCourierExtraPayout'], (float) ($detail['landCourierExtraPayout'] ?? 0.0));
            $collectionRoutes[$key]['bargeCustomerExtraFee'] = max((float) $collectionRoutes[$key]['bargeCustomerExtraFee'], (float) ($detail['bargeCustomerExtraFee'] ?? 0.0));
            $collectionRoutes[$key]['bargeCourierExtraPayout'] = max((float) $collectionRoutes[$key]['bargeCourierExtraPayout'], (float) ($detail['bargeCourierExtraPayout'] ?? 0.0));
        }

        return array_values(array_map(static function (array $detail): array {
            $sellerNames = array_values($detail['sellerNames']);
            $detail['sellerNames'] = $sellerNames;
            $detail['sellerNamesSummary'] = implode(', ', $sellerNames);

            return $detail;
        }, $collectionRoutes));
    }

    /** @param list<array<string, mixed>> $collectionRouteDetails */
    private function buildRouteSummary(array $collectionRouteDetails): ?string
    {
        $summaries = [];

        foreach ($collectionRouteDetails as $detail) {
            if (!($detail['found'] ?? false) || !isset($detail['summary'])) {
                continue;
            }

            $summary = (string) $detail['summary'];
            if ($summary !== '') {
                $summaries[] = $summary;
            }
        }

        $summaries = array_values(array_unique($summaries));

        if ($summaries === []) {
            return null;
        }

        if (count($summaries) === 1) {
            return $summaries[0];
        }

        return sprintf('%d communes de collecte distinctes détectées', count($summaries));
    }

    /**
     * @return array{customerDeliveryFee: float, courierPayout: float, deliveryMargin: float}
     */
    private function calculateDeliveryAmounts(
        DeliveryCommune $clientCommune,
        float $customerExtraFee,
        float $courierExtraPayout,
        int $distinctSellerCommuneCount,
    ): array {
        $localPricingZone = $clientCommune->getLocalPricingZone();

        $customerLocalDeliveryFee = $this->moneyToFloat($localPricingZone->getCustomerDeliveryFee());
        $courierLocalPayout = $this->moneyToFloat($localPricingZone->getCourierPayout());
        $routeCustomerExtraFee = max(0.0, round($customerExtraFee, 2));
        $routeCourierExtraPayout = max(0.0, round($courierExtraPayout, 2));
        $multiSellerCustomerExtraFee = $this->calculateMultiSellerCustomerExtraFee($distinctSellerCommuneCount);

        // Les suppléments existent déjà sur DeliveryCommuneConnection.
        // Ils couvrent désormais les liaisons LAND et BARGE du chemin retenu,
        // sans créer de nouvelle table ni de nouveau modèle tarifaire parallèle.
        $uncappedCustomerDeliveryFee = round(
            $customerLocalDeliveryFee + $routeCustomerExtraFee + $multiSellerCustomerExtraFee,
            2,
        );
        $uncappedCourierPayout = round($courierLocalPayout + $routeCourierExtraPayout, 2);

        // Plafond global de sécurité côté livreur : pendant le pilote, la rémunération
        // ne doit pas dépasser le maximum paramétré par commande. Valeur <= 0 ou réglage
        // vide : pas de plafond.
        $courierPayout = $uncappedCourierPayout;
        $courierPayoutCap = $this->getGlobalDeliveryCourierPayoutCap();
        $courierPayoutCapApplied = false;
        if ($courierPayoutCap > 0.0 && $courierPayout > $courierPayoutCap) {
            $courierPayout = $courierPayoutCap;
            $courierPayoutCapApplied = true;
        }

        // Plafond global de sécurité côté client : il évite qu’un long chemin
        // inter-communes rende les frais de livraison trop élevés pendant le pilote.
        // Valeur <= 0 ou réglage vide : pas de plafond.
        $customerDeliveryFee = $uncappedCustomerDeliveryFee;
        $customerDeliveryFeeCap = $this->getGlobalDeliveryCustomerFeeCap();
        $customerDeliveryFeeCapApplied = false;
        if ($customerDeliveryFeeCap > 0.0 && $customerDeliveryFee > $customerDeliveryFeeCap) {
            $customerDeliveryFee = $customerDeliveryFeeCap;
            $customerDeliveryFeeCapApplied = true;
        }

        return [
            'customerDeliveryFee' => round($customerDeliveryFee, 2),
            'courierPayout' => round($courierPayout, 2),
            'deliveryMargin' => round($customerDeliveryFee - $courierPayout, 2),
            'customerLocalDeliveryFee' => $customerLocalDeliveryFee,
            'courierLocalPayout' => $courierLocalPayout,
            'routeCustomerExtraFee' => $routeCustomerExtraFee,
            'routeCourierExtraPayout' => $routeCourierExtraPayout,
            'multiSellerCustomerExtraFee' => $multiSellerCustomerExtraFee,
            'customerDeliveryFeeCap' => $customerDeliveryFeeCap,
            'uncappedCustomerDeliveryFee' => $uncappedCustomerDeliveryFee,
            'customerDeliveryFeeCapApplied' => $customerDeliveryFeeCapApplied,
            'courierPayoutCap' => $courierPayoutCap,
            'uncappedCourierPayout' => $uncappedCourierPayout,
            'courierPayoutCapApplied' => $courierPayoutCapApplied,
        ];
    }

    private function buildMessage(string $relationLevel, bool $hasUnknownSellerCommune, int $landHopCount = 0, int $bargeHopCount = 0): string
    {
        if ($hasUnknownSellerCommune) {
            return 'Certains vendeurs doivent encore être rattachés à une commune logistique. Hodina confirmera les frais de livraison.';
        }

        return match ($relationLevel) {
            CartLogisticsPreview::RELATION_OTHER_TERRITORY => sprintf(
                'Certains produits de ton panier viennent de vendeurs situés sur une autre île. Le trajet détecté contient %d traversée barge et %d liaison(s) terrestre(s).',
                $bargeHopCount,
                $landHopCount,
            ),
            CartLogisticsPreview::RELATION_REMOTE_COMMUNE => sprintf(
                'Certains produits de ton panier viennent de vendeurs éloignés. Le trajet logistique détecté traverse %d liaison(s) terrestre(s).',
                $landHopCount,
            ),
            CartLogisticsPreview::RELATION_NEIGHBOR_COMMUNE => 'Certains produits viennent d’une commune voisine.',
            CartLogisticsPreview::RELATION_SAME_COMMUNE => 'Livraison calculée automatiquement selon ton adresse.',
            default => 'Hodina calculera les frais de livraison selon ton adresse et les vendeurs du panier.',
        };
    }

    private function normalizeCommuneName(string $name): string
    {
        $value = mb_strtolower(trim($name));
        $value = str_replace(['’', "'", '`'], '-', $value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;

        return trim($value, '-');
    }

    private function getGlobalCommuneCrossingCustomerFee(): float
    {
        return $this->getMoneySetting(HodinaSetting::KEY_GLOBAL_COMMUNE_CROSSING_CUSTOMER_FEE, 0.0);
    }

    private function getGlobalCommuneCrossingCourierPayout(): float
    {
        return $this->getMoneySetting(HodinaSetting::KEY_GLOBAL_COMMUNE_CROSSING_COURIER_PAYOUT, 0.0);
    }

    private function getGlobalDeliveryCustomerFeeCap(): float
    {
        return $this->getMoneySetting(HodinaSetting::KEY_GLOBAL_DELIVERY_CUSTOMER_FEE_CAP, 0.0);
    }

    private function getGlobalDeliveryCourierPayoutCap(): float
    {
        return $this->getMoneySetting(HodinaSetting::KEY_GLOBAL_DELIVERY_COURIER_PAYOUT_CAP, 20.0);
    }

    private function calculateMultiSellerCustomerExtraFee(int $distinctSellerCommuneCount): float
    {
        if ($distinctSellerCommuneCount <= 1) {
            return 0.0;
        }

        $extraPerAdditionalSeller = $this->getMoneySetting(HodinaSetting::KEY_GLOBAL_MULTI_SELLER_EXTRA_CUSTOMER_FEE, 0.0);
        if ($extraPerAdditionalSeller <= 0.0) {
            return 0.0;
        }

        $extra = ($distinctSellerCommuneCount - 1) * $extraPerAdditionalSeller;
        $extraCap = $this->getMoneySetting(HodinaSetting::KEY_GLOBAL_MULTI_SELLER_EXTRA_CUSTOMER_FEE_CAP, 0.0);

        if ($extraCap > 0.0) {
            $extra = min($extra, $extraCap);
        }

        return round(max(0.0, $extra), 2);
    }

    private function getMoneySetting(string $key, float $default): float
    {
        $setting = $this->entityManager
            ->getRepository(HodinaSetting::class)
            ->findOneBy(['settingKey' => $key]);

        if (!$setting instanceof HodinaSetting) {
            return round(max(0.0, $default), 2);
        }

        $value = trim((string) $setting->getValue());

        return $value !== '' ? round(max(0.0, (float) $value), 2) : round(max(0.0, $default), 2);
    }

    private function resolveConnectionExtraFee(string|float|int|null $specificValue, float $defaultValue): float
    {
        if ($specificValue === null || trim((string) $specificValue) === '') {
            return round(max(0.0, $defaultValue), 2);
        }

        return round(max(0.0, (float) $specificValue), 2);
    }

    private function moneyToFloat(string $amount): float
    {
        return round((float) $amount, 2);
    }
}
