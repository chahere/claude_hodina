<?php

namespace App\Entity;

use App\Repository\DeliveryPointRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeliveryPointRepository::class)]
#[ORM\Table(name: 'delivery_point')]
#[ORM\UniqueConstraint(name: 'UNIQ_DELIVERY_POINT_CODE', columns: ['code'])]
#[ORM\Index(name: 'IDX_DELIVERY_POINT_COMMUNE', columns: ['delivery_commune_id'])]
#[ORM\Index(name: 'IDX_DELIVERY_POINT_TYPE_ACTIVE', columns: ['type', 'is_active'])]
class DeliveryPoint
{
    public const TYPE_BARGE = 'BARGE';
    public const TYPE_AIRPORT = 'AIRPORT';
    public const TYPE_PICKUP_RELAY = 'PICKUP_RELAY';
    public const TYPE_SELLER_POINT = 'SELLER_POINT';
    public const TYPE_EVENT = 'EVENT';
    public const TYPE_OTHER = 'OTHER';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 160)]
    private string $name = '';

    #[ORM\Column(length: 80, unique: true)]
    private string $code = '';

    #[ORM\Column(length: 30)]
    private string $type = self::TYPE_PICKUP_RELAY;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(length: 255)]
    private string $line1 = '';

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $line2 = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $postalCode = null;

    #[ORM\Column(length: 120)]
    private string $communeName = '';

    #[ORM\ManyToOne(targetEntity: DeliveryCommune::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private DeliveryCommune $deliveryCommune;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $publicInstructions = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $courierInstructions = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $gpsLatitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $gpsLongitude = null;

    #[ORM\Column(nullable: true)]
    private ?int $gpsAccuracyMeters = null;

    #[ORM\Column]
    private int $sortOrder = 0;

    #[ORM\OneToMany(mappedBy: 'deliveryPoint', targetEntity: DeliveryPointTimeWindow::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $timeWindows;

    #[ORM\OneToMany(mappedBy: 'deliveryPoint', targetEntity: ProductDeliveryPoint::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $productDeliveryPoints;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->timeWindows = new ArrayCollection();
        $this->productDeliveryPoints = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return $this->name !== '' ? $this->name : 'Point de remise #' . $this->id;
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); $this->touch(); return $this; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self
    {
        $this->code = self::normalizeCode($code);
        $this->touch();

        return $this;
    }

    public function getType(): string { return $this->type; }
    public function setType(string $type): self
    {
        $type = mb_strtoupper(trim($type));
        $this->type = in_array($type, self::getTypes(), true) ? $type : self::TYPE_OTHER;
        $this->touch();

        return $this;
    }

    public function getTypeLabel(): string
    {
        return self::getTypeLabels()[$this->type] ?? $this->type;
    }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; $this->touch(); return $this; }

    public function getLine1(): string { return $this->line1; }
    public function setLine1(string $line1): self { $this->line1 = trim($line1); $this->touch(); return $this; }

    public function getLine2(): ?string { return $this->line2; }
    public function setLine2(?string $line2): self
    {
        $line2 = $line2 !== null ? trim($line2) : null;
        $this->line2 = $line2 !== '' ? $line2 : null;
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

    public function getCommuneName(): string { return $this->communeName; }
    public function setCommuneName(string $communeName): self { $this->communeName = trim($communeName); $this->touch(); return $this; }

    public function getDeliveryCommune(): DeliveryCommune { return $this->deliveryCommune; }
    public function setDeliveryCommune(DeliveryCommune $deliveryCommune): self { $this->deliveryCommune = $deliveryCommune; $this->touch(); return $this; }

    public function getPublicInstructions(): ?string { return $this->publicInstructions; }
    public function setPublicInstructions(?string $publicInstructions): self
    {
        $publicInstructions = $publicInstructions !== null ? trim($publicInstructions) : null;
        $this->publicInstructions = $publicInstructions !== '' ? $publicInstructions : null;
        $this->touch();

        return $this;
    }

    public function getCourierInstructions(): ?string { return $this->courierInstructions; }
    public function setCourierInstructions(?string $courierInstructions): self
    {
        $courierInstructions = $courierInstructions !== null ? trim($courierInstructions) : null;
        $this->courierInstructions = $courierInstructions !== '' ? $courierInstructions : null;
        $this->touch();

        return $this;
    }

    public function getGpsLatitude(): ?string { return $this->gpsLatitude; }
    public function setGpsLatitude(?string $gpsLatitude): self
    {
        $gpsLatitude = $gpsLatitude !== null ? trim((string) $gpsLatitude) : null;
        $this->gpsLatitude = $gpsLatitude !== '' ? $gpsLatitude : null;
        $this->touch();

        return $this;
    }

    public function getGpsLongitude(): ?string { return $this->gpsLongitude; }
    public function setGpsLongitude(?string $gpsLongitude): self
    {
        $gpsLongitude = $gpsLongitude !== null ? trim((string) $gpsLongitude) : null;
        $this->gpsLongitude = $gpsLongitude !== '' ? $gpsLongitude : null;
        $this->touch();

        return $this;
    }

    public function getGpsAccuracyMeters(): ?int { return $this->gpsAccuracyMeters; }
    public function setGpsAccuracyMeters(?int $gpsAccuracyMeters): self { $this->gpsAccuracyMeters = $gpsAccuracyMeters; $this->touch(); return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; $this->touch(); return $this; }

    /** @return Collection<int, DeliveryPointTimeWindow> */
    public function getTimeWindows(): Collection { return $this->timeWindows; }

    public function addTimeWindow(DeliveryPointTimeWindow $timeWindow): self
    {
        if (!$this->timeWindows->contains($timeWindow)) {
            $timeWindow->setDeliveryPoint($this);
            $this->timeWindows->add($timeWindow);
        }

        return $this;
    }

    public function removeTimeWindow(DeliveryPointTimeWindow $timeWindow): self
    {
        $this->timeWindows->removeElement($timeWindow);

        return $this;
    }

    /** @return Collection<int, ProductDeliveryPoint> */
    public function getProductDeliveryPoints(): Collection { return $this->productDeliveryPoints; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    /** @return list<string> */
    public static function getTypes(): array
    {
        return [
            self::TYPE_BARGE,
            self::TYPE_AIRPORT,
            self::TYPE_PICKUP_RELAY,
            self::TYPE_SELLER_POINT,
            self::TYPE_EVENT,
            self::TYPE_OTHER,
        ];
    }

    /** @return array<string, string> */
    public static function getTypeLabels(): array
    {
        return [
            self::TYPE_BARGE => 'Barge',
            self::TYPE_AIRPORT => 'Aéroport',
            self::TYPE_PICKUP_RELAY => 'Relais pickup',
            self::TYPE_SELLER_POINT => 'Point vendeur',
            self::TYPE_EVENT => 'Point événementiel',
            self::TYPE_OTHER => 'Autre point fixe',
        ];
    }

    private static function normalizeCode(string $code): string
    {
        $code = trim($code);
        $code = mb_strtoupper($code);
        $code = str_replace([' ', '-'], '_', $code);
        $code = preg_replace('/[^A-Z0-9_]+/', '', $code) ?? $code;
        $code = preg_replace('/_+/', '_', $code) ?? $code;

        return trim($code, '_');
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
