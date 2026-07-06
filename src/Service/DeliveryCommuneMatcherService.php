<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\DeliveryCommune;
use App\Entity\DeliveryZone;
use Doctrine\ORM\EntityManagerInterface;

final class DeliveryCommuneMatcherService
{
    public const ZONE_AUTRE = 'AUTRE';

    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    public function resolve(string $commune, ?string $postalCode = null): ?DeliveryCommune
    {
        $normalizedCommune = $this->normalize($commune);
        $normalizedPostalCode = trim((string) $postalCode);

        if ($normalizedCommune === '') {
            return null;
        }

        $communes = $this->findActiveLogisticsCommunes();

        foreach ($communes as $deliveryCommune) {
            $candidateNames = array_filter([
                $deliveryCommune->getName(),
                $deliveryCommune->getSlug(),
            ]);

            foreach ($candidateNames as $candidateName) {
                if (!$this->matchesCommune($normalizedCommune, $this->normalize((string) $candidateName))) {
                    continue;
                }

                if ($normalizedPostalCode !== '') {
                    $expectedPostalCode = trim((string) $deliveryCommune->getPostalCode());

                    if ($expectedPostalCode === '') {
                        return null;
                    }

                    if ($normalizedPostalCode !== $expectedPostalCode) {
                        continue;
                    }
                }

                return $deliveryCommune;
            }
        }

        return null;
    }

    public function resolveCanonicalActiveLogisticsCommune(string $commune, ?string $postalCode = null): ?DeliveryCommune
    {
        $normalizedCommune = $this->normalize($commune);
        $normalizedPostalCode = trim((string) $postalCode);

        if ($normalizedCommune === '') {
            return null;
        }

        foreach ($this->findActiveLogisticsCommunes() as $deliveryCommune) {
            $candidateNames = array_filter([
                $deliveryCommune->getName(),
                $deliveryCommune->getSlug(),
            ]);

            foreach ($candidateNames as $candidateName) {
                if ($normalizedCommune !== $this->normalize((string) $candidateName)) {
                    continue;
                }

                if ($normalizedPostalCode !== '' && $normalizedPostalCode !== trim((string) $deliveryCommune->getPostalCode())) {
                    continue;
                }

                return $deliveryCommune;
            }
        }

        return null;
    }

    public function resolveByCommuneName(string $commune): ?DeliveryCommune
    {
        $normalizedCommune = $this->normalize($commune);

        if ($normalizedCommune === '') {
            return null;
        }

        $communes = $this->findActiveLogisticsCommunes();

        foreach ($communes as $deliveryCommune) {
            foreach (array_filter([$deliveryCommune->getName(), $deliveryCommune->getSlug()]) as $candidateName) {
                if ($this->matchesCommune($normalizedCommune, $this->normalize((string) $candidateName))) {
                    return $deliveryCommune;
                }
            }
        }

        return null;
    }


    /**
     * @return list<DeliveryCommune>
     */
    public function findActiveLogisticsCommunes(): array
    {
        /** @var list<DeliveryCommune> $communes */
        $communes = $this->em->getRepository(DeliveryCommune::class)
            ->createQueryBuilder('c')
            ->andWhere('c.isActive = :active')
            ->andWhere('c.isLogisticsPoint = :logisticsPoint')
            ->setParameter('active', true)
            ->setParameter('logisticsPoint', true)
            ->orderBy('c.name', 'ASC')
            ->getQuery()
            ->getResult();

        return $communes;
    }

    public function findDeliveryZoneForCommune(DeliveryCommune $deliveryCommune): ?DeliveryZone
    {
        return $this->em->getRepository(DeliveryZone::class)->findOneBy([
            'code' => $deliveryCommune->getTerritory(),
            'isActive' => true,
        ]);
    }

    public function findOtherDeliveryZone(): ?DeliveryZone
    {
        return $this->em->getRepository(DeliveryZone::class)->findOneBy([
            'code' => self::ZONE_AUTRE,
            'isActive' => true,
        ]);
    }

    public function isValidFrenchPostalCode(?string $postalCode): bool
    {
        return preg_match('/^\\d{5}$/', trim((string) $postalCode)) === 1;
    }

    public function buildValidationMessage(string $commune, ?string $postalCode = null): string
    {
        $commune = trim($commune);
        $postalCode = trim((string) $postalCode);

        if ($commune === '') {
            return 'La commune de livraison est obligatoire.';
        }

        if ($postalCode !== '') {
            return sprintf(
                'La commune "%s" avec le code postal "%s" n’est pas reconnue comme commune livrable Hodina. Vérifie la commune et le code postal dans Logistique > Communes livrées.',
                $commune,
                $postalCode
            );
        }

        return sprintf(
            'La commune "%s" n’est pas reconnue comme commune livrable Hodina. Vérifie la saisie ou ajoute la commune dans Logistique > Communes livrées.',
            $commune
        );
    }

    private function matchesCommune(string $input, string $candidate): bool
    {
        if ($input === $candidate) {
            return true;
        }

        if ($input !== '' && $candidate !== '' && str_contains($input, $candidate)) {
            return true;
        }

        if ($input !== '' && $candidate !== '' && str_contains($candidate, $input)) {
            return true;
        }

        // Cas terrain fréquent : "Dzaoudzi-Labattoir" doit pouvoir retrouver Labattoir.
        if (str_contains($input, 'labattoir') && str_contains($candidate, 'labattoir')) {
            return true;
        }

        return false;
    }

    private function normalize(string $value): string
    {
        $value = trim(mb_strtolower($value));

        $replacements = [
            'é' => 'e',
            'è' => 'e',
            'ê' => 'e',
            'ë' => 'e',
            'à' => 'a',
            'â' => 'a',
            'ä' => 'a',
            'î' => 'i',
            'ï' => 'i',
            'ô' => 'o',
            'ö' => 'o',
            'ù' => 'u',
            'û' => 'u',
            'ü' => 'u',
            'ç' => 'c',
            '\'' => ' ',
            '’' => ' ',
            '-' => ' ',
            '_' => ' ',
        ];

        $value = strtr($value, $replacements);
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
