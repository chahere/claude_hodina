<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Address;
use App\Service\DeliveryCommuneMatcherService;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

final class DeliverableAddressValidator extends ConstraintValidator
{
    public function __construct(
        private readonly DeliveryCommuneMatcherService $deliveryCommuneMatcher,
    ) {
    }

    public function validate(mixed $value, Constraint $constraint): void
    {
        if (!$value instanceof Address) {
            return;
        }

        $line1 = trim($value->getLine1());
        $commune = trim($value->getCommune());
        $postalCode = trim((string) $value->getPostalCode());
        $deliveryZone = $value->getDeliveryZone();

        if ($line1 === '') {
            $this->context
                ->buildViolation('La première ligne de l’adresse ne doit pas être vide.')
                ->atPath('line1')
                ->addViolation();

            return;
        }

        if ($deliveryZone === null) {
            $this->context
                ->buildViolation('La zone de livraison/facturation est obligatoire.')
                ->atPath('deliveryZone')
                ->addViolation();

            return;
        }

        if (!$this->deliveryCommuneMatcher->isValidFrenchPostalCode($postalCode)) {
            $this->context
                ->buildViolation('Le code postal doit contenir exactement 5 chiffres.')
                ->atPath('postalCode')
                ->addViolation();

            return;
        }

        if ($value->isBilling() && $deliveryZone->getCode() === DeliveryCommuneMatcherService::ZONE_AUTRE) {
            return;
        }

        if ($deliveryZone->getCode() === DeliveryCommuneMatcherService::ZONE_AUTRE) {
            $this->context
                ->buildViolation('Une adresse de livraison doit utiliser Petite-Terre ou Grande-Terre, pas la zone Autre.')
                ->atPath('deliveryZone')
                ->addViolation();

            return;
        }

        if ($commune === '') {
            $this->context
                ->buildViolation($value->isBilling() ? 'La commune de facturation est obligatoire.' : 'La commune de livraison est obligatoire.')
                ->atPath('commune')
                ->addViolation();

            return;
        }

        $deliveryCommune = $this->deliveryCommuneMatcher->resolveByCommuneName($commune);

        if (!$deliveryCommune) {
            $this->context
                ->buildViolation($this->deliveryCommuneMatcher->buildValidationMessage($commune, $postalCode))
                ->atPath('commune')
                ->addViolation();

            return;
        }

        $expectedPostalCode = trim((string) $deliveryCommune->getPostalCode());
        if ($expectedPostalCode !== '' && $postalCode !== $expectedPostalCode) {
            $this->context
                ->buildViolation(sprintf(
                    'Le code postal %s ne correspond pas à la commune %s. Le code postal attendu est %s.',
                    $postalCode,
                    $deliveryCommune->getName(),
                    $expectedPostalCode
                ))
                ->atPath('postalCode')
                ->addViolation();

            return;
        }

        if ($deliveryZone->getCode() !== $deliveryCommune->getTerritory()) {
            $this->context
                ->buildViolation(sprintf(
                    'La commune %s appartient à %s, pas à la zone %s.',
                    $deliveryCommune->getName(),
                    $this->formatZoneLabel($deliveryCommune->getTerritory()),
                    $this->formatZoneLabel($deliveryZone->getCode())
                ))
                ->atPath('deliveryZone')
                ->addViolation();
        }
    }

    private function formatZoneLabel(string $zoneCode): string
    {
        return match ($zoneCode) {
            'PT' => 'Petite-Terre (PT)',
            'GT' => 'Grande-Terre (GT)',
            DeliveryCommuneMatcherService::ZONE_AUTRE => 'Autre',
            default => $zoneCode,
        };
    }
}
