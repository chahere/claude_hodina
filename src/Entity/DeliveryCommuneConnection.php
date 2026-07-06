<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Lien logistique entre deux communes / points logistiques.
 *
 * Cette entité remplace progressivement la relation ManyToMany simple
 * DeliveryCommune.neighboringCommunes pour les calculs avancés J5G-B.
 *
 * Elle permet de distinguer :
 * - LAND  : route terrestre classique ;
 * - BARGE : traversée maritime Petite-Terre / Grande-Terre.
 */
#[ORM\Entity]
#[ORM\Table(name: 'delivery_commune_connection')]
#[ORM\Index(name: 'IDX_8D0FF29B38BE8975', columns: ['from_commune_id'])]
#[ORM\Index(name: 'IDX_8D0FF29B3429AD0F', columns: ['to_commune_id'])]
#[ORM\UniqueConstraint(name: 'UNIQ_DELIVERY_COMMUNE_CONNECTION_DIRECTION', columns: ['from_commune_id', 'to_commune_id', 'link_type'])]
class DeliveryCommuneConnection
{
    public const LINK_TYPE_LAND = 'LAND';
    public const LINK_TYPE_BARGE = 'BARGE';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DeliveryCommune::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DeliveryCommune $fromCommune;

    #[ORM\ManyToOne(targetEntity: DeliveryCommune::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DeliveryCommune $toCommune;

    #[ORM\Column(length: 20)]
    private string $linkType = self::LINK_TYPE_LAND;

    /**
     * Si true, le service pourra considérer que le lien inverse est aussi
     * utilisable, même si la ligne inverse n'existe pas encore.
     */
    #[ORM\Column]
    private bool $isBidirectional = true;

    /**
     * Poids simple du lien pour le plus court chemin.
     *
     * Pour le pilote, chaque lien vaut 1. Plus tard, on pourra pondérer
     * certains trajets plus coûteux.
     */
    #[ORM\Column]
    private int $hopCount = 1;

    /**
     * Supplément éventuel client propre à ce lien.
     *
     * Nullable volontairement : si vide sur une liaison LAND, le réglage
     * Hodina global de coût de traversée de commune sera utilisé.
     * Pour BARGE, la valeur reste spécifique à la liaison maritime.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $customerExtraFee = null;

    /**
     * Supplément éventuel livreur propre à ce lien.
     *
     * Même logique de fallback global que customerExtraFee pour LAND.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $courierExtraPayout = null;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $internalNote = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s → %s (%s)',
            isset($this->fromCommune) ? $this->fromCommune->getName() : 'Départ',
            isset($this->toCommune) ? $this->toCommune->getName() : 'Arrivée',
            $this->linkType,
        );
    }

    public function getId(): ?int { return $this->id; }

    public function getFromCommune(): DeliveryCommune { return $this->fromCommune; }
    public function setFromCommune(DeliveryCommune $fromCommune): self { $this->fromCommune = $fromCommune; $this->touch(); return $this; }

    public function getToCommune(): DeliveryCommune { return $this->toCommune; }
    public function setToCommune(DeliveryCommune $toCommune): self { $this->toCommune = $toCommune; $this->touch(); return $this; }

    public function getLinkType(): string { return $this->linkType; }
    public function setLinkType(string $linkType): self { $this->linkType = mb_strtoupper(trim($linkType)); $this->touch(); return $this; }

    public function isBidirectional(): bool { return $this->isBidirectional; }
    public function setIsBidirectional(bool $isBidirectional): self { $this->isBidirectional = $isBidirectional; $this->touch(); return $this; }

    public function getHopCount(): int { return $this->hopCount; }
    public function setHopCount(int $hopCount): self { $this->hopCount = max(1, $hopCount); $this->touch(); return $this; }

    public function getCustomerExtraFee(): ?string { return $this->customerExtraFee; }
    public function setCustomerExtraFee(string|float|int|null $customerExtraFee): self
    {
        $this->customerExtraFee = $customerExtraFee !== null ? number_format((float) $customerExtraFee, 2, '.', '') : null;
        $this->touch();

        return $this;
    }

    public function getCourierExtraPayout(): ?string { return $this->courierExtraPayout; }
    public function setCourierExtraPayout(string|float|int|null $courierExtraPayout): self
    {
        $this->courierExtraPayout = $courierExtraPayout !== null ? number_format((float) $courierExtraPayout, 2, '.', '') : null;
        $this->touch();

        return $this;
    }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; $this->touch(); return $this; }

    public function getInternalNote(): ?string { return $this->internalNote; }
    public function setInternalNote(?string $internalNote): self { $this->internalNote = $internalNote; $this->touch(); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
