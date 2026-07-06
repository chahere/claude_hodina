<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_commune')]
#[ORM\UniqueConstraint(name: 'UNIQ_E8FC6E30989D9B62', columns: ['slug'])]
#[ORM\Index(name: 'IDX_E8FC6E30EA98E376', columns: ['postal_code'])]
#[ORM\Index(name: 'IDX_E8FC6E3015A3C1BC', columns: ['insee_code'])]
class DeliveryCommune
{
    public const TERRITORY_PT = 'PT';
    public const TERRITORY_GT = 'GT';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120, unique: true)]
    private string $name;

    #[ORM\Column(length: 2)]
    private string $territory = self::TERRITORY_GT;

    /**
     * Identifiant lisible stable utilisé pour les imports / seeds.
     *
     * Exemple : dzaoudzi, labattoir, mamoudzou.
     * Il reste nullable pendant la transition J5G-B2 afin de ne pas casser
     * les communes déjà créées en recette.
     */
    #[ORM\Column(length: 160, nullable: true)]
    private ?string $slug = null;

    /**
     * Code postal principal de la commune / du point logistique.
     *
     * Exemple Mayotte : 97615 pour Dzaoudzi / Labattoir / Pamandzi.
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    /**
     * Code INSEE / COG officiel si le point correspond à une commune administrative.
     *
     * Attention : un point logistique comme Labattoir peut partager le code
     * administratif de sa commune parente, ou laisser ce champ vide et utiliser
     * parentInseeCode.
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $inseeCode = null;

    /**
     * Code INSEE de la commune administrative parente pour un point logistique.
     *
     * Exemple : Labattoir est utile comme point terrain Hodina, mais il est
     * rattaché administrativement à Dzaoudzi.
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $parentInseeCode = null;

    /**
     * Permet de distinguer les communes administratives strictes des points
     * utiles terrain. Pour le pilote, les deux restent utilisables dans le
     * calcul logistique.
     */
    #[ORM\Column]
    private bool $isLogisticsPoint = true;

    #[ORM\ManyToOne(targetEntity: DeliveryPricingZone::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DeliveryPricingZone $localPricingZone;

    #[ORM\ManyToOne(targetEntity: DeliveryPricingZone::class)]
    #[ORM\JoinColumn(nullable: false)]
    private DeliveryPricingZone $bargePricingZone;

    /**
     * Ancien voisinage simple paramétré par l'admin.
     *
     * À partir de J5G-B2, les nouvelles règles avancées doivent utiliser
     * DeliveryCommuneConnection pour distinguer un lien terrestre LAND d'un
     * lien maritime BARGE. Cette relation est conservée pour compatibilité
     * avec J5F-A / J5F-B et pourra être migrée progressivement.
     */
    #[ORM\ManyToMany(targetEntity: self::class)]
    #[ORM\JoinTable(name: 'delivery_commune_neighbor')]
    #[ORM\JoinColumn(name: 'commune_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'neighbor_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $neighboringCommunes;

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
        $this->neighboringCommunes = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s (%s)', $this->name, $this->territory);
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); $this->touch(); return $this; }

    public function getTerritory(): string { return $this->territory; }
    public function setTerritory(string $territory): self { $this->territory = $territory; $this->touch(); return $this; }

    public function getSlug(): ?string { return $this->slug; }
    public function setSlug(?string $slug): self
    {
        $this->slug = $slug !== null && trim($slug) !== '' ? $this->normalizeSlug($slug) : null;
        $this->touch();

        return $this;
    }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $postalCode): self
    {
        $this->postalCode = $postalCode !== null && trim($postalCode) !== '' ? trim($postalCode) : null;
        $this->touch();

        return $this;
    }

    public function getInseeCode(): ?string { return $this->inseeCode; }
    public function setInseeCode(?string $inseeCode): self
    {
        $this->inseeCode = $inseeCode !== null && trim($inseeCode) !== '' ? trim($inseeCode) : null;
        $this->touch();

        return $this;
    }

    public function getParentInseeCode(): ?string { return $this->parentInseeCode; }
    public function setParentInseeCode(?string $parentInseeCode): self
    {
        $this->parentInseeCode = $parentInseeCode !== null && trim($parentInseeCode) !== '' ? trim($parentInseeCode) : null;
        $this->touch();

        return $this;
    }

    public function isLogisticsPoint(): bool { return $this->isLogisticsPoint; }
    public function setIsLogisticsPoint(bool $isLogisticsPoint): self { $this->isLogisticsPoint = $isLogisticsPoint; $this->touch(); return $this; }

    public function getLocalPricingZone(): DeliveryPricingZone { return $this->localPricingZone; }
    public function setLocalPricingZone(DeliveryPricingZone $localPricingZone): self { $this->localPricingZone = $localPricingZone; $this->touch(); return $this; }

    public function getBargePricingZone(): DeliveryPricingZone { return $this->bargePricingZone; }
    public function setBargePricingZone(DeliveryPricingZone $bargePricingZone): self { $this->bargePricingZone = $bargePricingZone; $this->touch(); return $this; }

    /** @return Collection<int, self> */
    public function getNeighboringCommunes(): Collection { return $this->neighboringCommunes; }

    public function addNeighboringCommune(self $commune): self
    {
        if ($commune !== $this && !$this->neighboringCommunes->contains($commune)) {
            $this->neighboringCommunes->add($commune);
            $this->touch();
        }

        return $this;
    }

    public function removeNeighboringCommune(self $commune): self
    {
        if ($this->neighboringCommunes->removeElement($commune)) {
            $this->touch();
        }

        return $this;
    }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; $this->touch(); return $this; }

    public function getInternalNote(): ?string { return $this->internalNote; }
    public function setInternalNote(?string $internalNote): self { $this->internalNote = $internalNote; $this->touch(); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    private function normalizeSlug(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = str_replace(['’', "'"], '-', $value);
        $value = preg_replace('/[^a-z0-9]+/i', '-', $value) ?? $value;
        $value = trim($value, '-');

        return $value;
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
