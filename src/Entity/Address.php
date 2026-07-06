<?php

namespace App\Entity;

use App\Validator as AppAssert;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'address')]
#[ORM\Index(name: 'IDX_ADDRESS_LOCALITY', columns: ['address_locality_id'])]
#[AppAssert\DeliverableAddress]
class Address
{
    public const TYPE_DELIVERY = 'DELIVERY';
    public const TYPE_BILLING = 'BILLING';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'addresses')]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $label = null;

    #[ORM\Column(length: 20, options: ['default' => self::TYPE_DELIVERY])]
    private string $type = self::TYPE_DELIVERY;

    #[ORM\Column(length: 180)]
    #[Assert\Length(max: 180, maxMessage: 'La première ligne de l’adresse ne doit pas dépasser {{ limit }} caractères.')]
    private string $line1 = '';

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $line2 = null;

    #[ORM\Column(length: 20)]
    private string $postalCode = '';

    #[ORM\Column(length: 120)]
    #[Assert\Length(max: 120, maxMessage: 'La commune ne doit pas dépasser {{ limit }} caractères.')]
    private string $commune = '';

    /**
     * Localité précise de l'adresse : village, quartier ou lieu-dit.
     * Elle ne remplace jamais Address.commune et ne calcule jamais les frais.
     */
    #[ORM\ManyToOne(targetEntity: AddressLocality::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?AddressLocality $addressLocality = null;

    #[ORM\Column(length: 120, nullable: true)]
    #[Assert\Length(max: 120, maxMessage: 'La localité ne doit pas dépasser {{ limit }} caractères.')]
    private ?string $localityText = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?DeliveryZone $deliveryZone = null;

    /**
     * Instructions donnees par le client pour retrouver l'adresse.
     * Exemple : "pres du centre commercial Baobab, portail bleu".
     *
     * Champ historique conserve sous le nom DB notes pour eviter un doublon.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    /**
     * Note terrain interne, enrichie par admin/livreur pour les prochaines livraisons.
     * Non affichée au client dans le pilote.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $courierNotes = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $gpsLatitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $gpsLongitude = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $gpsAccuracyMeters = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getCustomer(): Customer { return $this->customer; }
    public function setCustomer(Customer $customer): self { $this->customer = $customer; return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): self { $this->label = $label; return $this; }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self
    {
        $type = strtoupper(trim($type));

        if (!in_array($type, [self::TYPE_DELIVERY, self::TYPE_BILLING], true)) {
            $type = self::TYPE_DELIVERY;
        }

        $this->type = $type;

        return $this;
    }

    public function isDelivery(): bool { return $this->type === self::TYPE_DELIVERY; }
    public function isBilling(): bool { return $this->type === self::TYPE_BILLING; }

    public function getTypeLabel(): string
    {
        return $this->isBilling() ? 'Facturation' : 'Livraison';
    }

    public function getLine1(): string { return $this->line1; }
    public function setLine1(string $line1): self { $this->line1 = trim($line1); return $this; }

    public function getLine2(): ?string { return $this->line2; }
    public function setLine2(?string $line2): self { $this->line2 = $line2 !== null ? trim($line2) : null; return $this; }

    public function getPostalCode(): string
    {
        return $this->postalCode;
    }

    public function setPostalCode(string $postalCode): static
    {
        $this->postalCode = trim($postalCode);

        return $this;
    }

    public function getCommune(): string { return $this->commune; }
    public function setCommune(string $commune): self { $this->commune = trim($commune); return $this; }

    public function getAddressLocality(): ?AddressLocality { return $this->addressLocality; }
    public function setAddressLocality(?AddressLocality $addressLocality): self
    {
        $this->addressLocality = $addressLocality;

        if ($addressLocality instanceof AddressLocality) {
            $this->setLocalityText($addressLocality->getName());
        }

        return $this;
    }

    public function getLocalityText(): ?string { return $this->localityText; }
    public function setLocalityText(?string $localityText): self
    {
        $localityText = $localityText !== null ? trim($localityText) : null;
        $this->localityText = $localityText !== '' ? $localityText : null;

        return $this;
    }

    public function getLocalityLabel(): ?string
    {
        $localityText = trim((string) $this->localityText);
        if ($localityText !== '') {
            return $localityText;
        }

        return $this->addressLocality?->getName();
    }

    public function getDeliveryZone(): ?DeliveryZone { return $this->deliveryZone; }
    public function setDeliveryZone(?DeliveryZone $deliveryZone): self { $this->deliveryZone = $deliveryZone; return $this; }

    public function getNotes(): ?string { return $this->notes; }
    public function setNotes(?string $notes): self
    {
        $notes = $notes !== null ? trim($notes) : null;
        $this->notes = $notes !== '' ? $notes : null;
        return $this;
    }

    public function getDeliveryInstructions(): ?string { return $this->getNotes(); }
    public function setDeliveryInstructions(?string $deliveryInstructions): self { return $this->setNotes($deliveryInstructions); }

    public function getCourierNotes(): ?string { return $this->courierNotes; }
    public function setCourierNotes(?string $courierNotes): self
    {
        $courierNotes = $courierNotes !== null ? trim($courierNotes) : null;
        $this->courierNotes = $courierNotes !== '' ? $courierNotes : null;
        return $this;
    }

    public function getGpsLatitude(): ?string { return $this->gpsLatitude; }
    public function setGpsLatitude(?string $gpsLatitude): self
    {
        $this->gpsLatitude = $this->normalizeGpsDecimal($gpsLatitude, 7);
        return $this;
    }

    public function getGpsLongitude(): ?string { return $this->gpsLongitude; }
    public function setGpsLongitude(?string $gpsLongitude): self
    {
        $this->gpsLongitude = $this->normalizeGpsDecimal($gpsLongitude, 7);
        return $this;
    }

    public function getGpsAccuracyMeters(): ?string { return $this->gpsAccuracyMeters; }
    public function setGpsAccuracyMeters(?string $gpsAccuracyMeters): self
    {
        $this->gpsAccuracyMeters = $this->normalizeGpsDecimal($gpsAccuracyMeters, 2);
        return $this;
    }

    public function hasGpsCoordinates(): bool
    {
        return $this->gpsLatitude !== null && $this->gpsLongitude !== null;
    }

    public function getGpsCoordinatesLabel(): ?string
    {
        if (!$this->hasGpsCoordinates()) {
            return null;
        }

        $label = sprintf('%s, %s', $this->gpsLatitude, $this->gpsLongitude);

        if ($this->gpsAccuracyMeters !== null) {
            $accuracy = rtrim(rtrim($this->gpsAccuracyMeters, '0'), '.');
            $label .= sprintf(' (precision ~%s m)', $accuracy !== '' ? $accuracy : '0');
        }

        return $label;
    }

    public function getGpsMapUrl(): ?string
    {
        if (!$this->hasGpsCoordinates()) {
            return null;
        }

        return sprintf('https://www.google.com/maps?q=%s,%s', $this->gpsLatitude, $this->gpsLongitude);
    }

    private function normalizeGpsDecimal(?string $value, int $scale): ?string
    {
        $value = $value !== null ? trim(str_replace(',', '.', $value)) : '';

        if ($value === '') {
            return null;
        }

        if (!is_numeric($value)) {
            return null;
        }

        return number_format((float) $value, $scale, '.', '');
    }

    public function __toString(): string
    {
        $line1 = trim($this->getLine1() ?? '');
        $postalCommune = trim(sprintf('%s %s', $this->getPostalCode() ?? '', $this->getCommune() ?? ''));
        $zone = $this->getDeliveryZone() ? $this->getDeliveryZone()->getCode() : null;

        $parts = array_filter([
            $this->getTypeLabel(),
            $line1,
            $this->getLocalityLabel(),
            $postalCommune,
            $zone ? 'Zone ' . $zone : null,
        ]);

        if ($parts !== []) {
            return implode(' — ', $parts);
        }

        return 'Adresse #' . $this->getId();
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
