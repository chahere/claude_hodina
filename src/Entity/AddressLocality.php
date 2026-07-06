<?php

namespace App\Entity;

use App\Repository\AddressLocalityRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AddressLocalityRepository::class)]
#[ORM\Table(name: 'address_locality')]
#[ORM\Index(name: 'IDX_ADDRESS_LOCALITY_COMMUNE', columns: ['delivery_commune_id'])]
#[ORM\Index(name: 'IDX_ADDRESS_LOCALITY_ACTIVE', columns: ['is_active'])]
#[ORM\Index(name: 'IDX_ADDRESS_LOCALITY_POSTAL_CODE', columns: ['postal_code'])]
#[ORM\Index(name: 'IDX_ADDRESS_LOCALITY_NORMALIZED_NAME', columns: ['normalized_name'])]
class AddressLocality
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    #[Assert\NotBlank(message: 'Indique le nom de la localité.')]
    #[Assert\Length(max: 120, maxMessage: 'La localité ne doit pas dépasser {{ limit }} caractères.')]
    private string $name = '';

    #[ORM\Column(length: 160)]
    private string $normalizedName = '';

    /**
     * Commune livrable associée à la localité.
     * Elle aide l'UX et le back-office, mais ne remplace jamais Address.commune.
     */
    #[ORM\ManyToOne(targetEntity: DeliveryCommune::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeliveryCommune $deliveryCommune = null;

    /**
     * Code postal indicatif de la localité.
     * Le calcul logistique reste basé sur Address.commune / DeliveryCommune.
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 2, nullable: true)]
    private ?string $countryCode = 'YT';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $sortOrder = 0;

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
        $communeName = $this->deliveryCommune?->getName();

        return $communeName ? sprintf('%s — %s', $this->name, $communeName) : $this->name;
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self
    {
        $this->name = trim($name);
        $this->normalizedName = self::normalizeName($this->name);
        $this->touch();

        return $this;
    }

    public function getNormalizedName(): string { return $this->normalizedName; }
    public function setNormalizedName(string $normalizedName): self
    {
        $this->normalizedName = self::normalizeName($normalizedName);
        $this->touch();

        return $this;
    }

    public function getDeliveryCommune(): ?DeliveryCommune { return $this->deliveryCommune; }
    public function setDeliveryCommune(?DeliveryCommune $deliveryCommune): self
    {
        $this->deliveryCommune = $deliveryCommune;

        if ($deliveryCommune instanceof DeliveryCommune) {
            $postalCode = trim((string) $deliveryCommune->getPostalCode());
            if ($postalCode !== '') {
                $this->postalCode = $postalCode;
            }
        }

        $this->touch();

        return $this;
    }

    public function getPostalCode(): ?string { return $this->postalCode; }
    public function setPostalCode(?string $postalCode): self
    {
        $postalCode = $postalCode !== null ? trim($postalCode) : null;
        $this->postalCode = $postalCode !== '' ? $postalCode : null;
        $this->touch();

        return $this;
    }

    public function getCountryCode(): ?string { return $this->countryCode; }
    public function setCountryCode(?string $countryCode): self
    {
        $countryCode = $countryCode !== null ? strtoupper(trim($countryCode)) : null;
        $this->countryCode = $countryCode !== '' ? mb_substr($countryCode, 0, 2) : null;
        $this->touch();

        return $this;
    }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; $this->touch(); return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; $this->touch(); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    public static function normalizeName(?string $name): string
    {
        $name = trim((string) $name);
        if ($name === '') {
            return '';
        }

        $slugger = new AsciiSlugger('fr');

        return mb_strtolower((string) $slugger->slug($name, '-'));
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
