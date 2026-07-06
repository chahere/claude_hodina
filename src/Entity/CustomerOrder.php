<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'customer_order')]
class CustomerOrder
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_PENDING_VALIDATION = 'PENDING_VALIDATION';
    public const STATUS_CONFIRMED = 'CONFIRMED';
    public const STATUS_PREPARING = 'PREPARING';
    public const STATUS_READY_FOR_PICKUP = 'READY_FOR_PICKUP';
    public const STATUS_PICKED_UP = 'PICKED_UP';
    public const STATUS_OUT_FOR_DELIVERY = 'OUT_FOR_DELIVERY';
    public const STATUS_DELIVERED = 'DELIVERED';
    public const STATUS_CANCELED = 'CANCELED';

    public const PAY_PENDING = 'PENDING';
    public const PAY_PAID = 'PAID';
    public const PAY_REFUNDED = 'REFUNDED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Customer $customer;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Address $deliveryAddress = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $deliveryAddressLabel = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $deliveryAddressLine1 = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $deliveryAddressLine2 = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $deliveryAddressPostalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $deliveryAddressCommune = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $deliveryAddressLocalityName = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $deliveryAddressZoneCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $deliveryAddressZoneName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deliveryAddressNotes = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deliveryAddressCourierNotes = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $deliveryAddressGpsLatitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $deliveryAddressGpsLongitude = null;

    #[ORM\Column(type: 'decimal', precision: 8, scale: 2, nullable: true)]
    private ?string $deliveryAddressGpsAccuracyMeters = null;

    #[ORM\Column(length: 60, nullable: true)]
    private ?string $billingAddressLabel = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingAddressLine1 = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $billingAddressLine2 = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $billingAddressPostalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $billingAddressCommune = null;

    #[ORM\Column(length: 40, nullable: true)]
    private ?string $billingAddressZoneCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $billingAddressZoneName = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $billingAddressNotes = null;

    #[ORM\OneToMany(mappedBy: 'customerOrder', targetEntity: OrderItem::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $items;

    #[ORM\Column(length: 80, nullable: true, unique: true)]
    private ?string $orderReference = null;

    #[ORM\Column(nullable: true)]
    private ?int $dailyOrderNumber = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $orderReferenceDate = null;

    #[ORM\Column(length: 40)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(length: 40)]
    private string $paymentStatus = self::PAY_PENDING;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $subtotal = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $deliveryFee = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $total = '0.00';

    /**
     * Fuseau horaire detecte cote navigateur au moment de la commande.
     * Exemple : Indian/Mayotte, Europe/Paris, Indian/Reunion.
     */
    #[ORM\Column(length: 80, nullable: true)]
    private ?string $customerTimezone = null;

    #[ORM\ManyToOne(targetEntity: DeliveryPoint::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeliveryPoint $deliveryPoint = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $deliveryPointName = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $deliveryPointCode = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $deliveryPointType = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryPointAddressLine1 = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $deliveryPointAddressLine2 = null;

    #[ORM\Column(length: 10, nullable: true)]
    private ?string $deliveryPointPostalCode = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $deliveryPointCommune = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deliveryPointPublicInstructions = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deliveryPointCourierInstructions = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deliveryPointCustomerInstructions = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $deliveryPointGpsLatitude = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 7, nullable: true)]
    private ?string $deliveryPointGpsLongitude = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryPointGpsAccuracyMeters = null;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $deliveryPointTimeWindowLabel = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveryPointScheduledDate = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveryPointScheduledTime = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryPointTimeWindowWeekday = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveryPointStartTime = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $deliveryPointEndTime = null;


    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $deliveryLogisticsSnapshot = null;

    /**
     * Suivi terrain de la collecte par vendeur pour le portail Djama.
     *
     * Structure attendue :
     * sellerId => [
     *   status => CODE_SENT|COLLECTED,
     *   codeHash => string|null,
     *   codeSentAt => ISO 8601|null,
     *   smsLogId => int|null,
     *   emailLogId => int|null,
     *   collectedAt => ISO 8601|null,
     *   courierId => int|null,
     *   courierLabel => string|null,
     *   note => string|null,
     * ]
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $sellerCollectionSnapshot = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\ManyToOne]
    private ?DeliveryZone $deliveryZone = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $submittedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $confirmedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $preparingAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $readyAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveredAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $canceledAt = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Customer $assignedCourier = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $courierAssignedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $outForDeliveryAt = null;

    /**
     * Code de réception client chiffré pour validation terrain de la livraison.
     * Le code reste déchiffrable uniquement jusqu'à validation de la livraison
     * afin de pouvoir renvoyer le même code si le client ne le retrouve pas.
     */
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deliveryValidationCodeEncrypted = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveryValidationCodeSentAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $deliveryValidationCodeValidatedAt = null;

    #[ORM\Column]
    private int $deliveryValidationCodeSendCount = 0;

    #[ORM\Column]
    private int $deliveryValidationCodeFailedAttempts = 0;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryValidationSmsLogId = null;

    #[ORM\Column(nullable: true)]
    private ?int $deliveryValidationEmailLogId = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->items = new ArrayCollection();
    }

    public function __toString(): string
    {
        return $this->orderReference ?? 'Commande #' . ($this->id ?? 'nouvelle');
    }

    public function getId(): ?int { return $this->id; }

    public function getCustomer(): Customer { return $this->customer; }
    public function setCustomer(Customer $customer): self { $this->customer = $customer; return $this; }

    public function getDeliveryAddress(): ?Address { return $this->deliveryAddress; }
    public function setDeliveryAddress(?Address $deliveryAddress): self { $this->deliveryAddress = $deliveryAddress; return $this; }

    public function snapshotDeliveryAddress(?Address $address): self
    {
        $this->deliveryAddressLabel = $address?->getLabel();
        $this->deliveryAddressLine1 = $address?->getLine1();
        $this->deliveryAddressLine2 = $address?->getLine2();
        $this->deliveryAddressPostalCode = $address?->getPostalCode();
        $this->deliveryAddressCommune = $address?->getCommune();
        $this->deliveryAddressLocalityName = $address?->getLocalityLabel();
        $this->deliveryAddressZoneCode = $address?->getDeliveryZone()?->getCode();
        $this->deliveryAddressZoneName = $address?->getDeliveryZone()?->getName();
        $this->deliveryAddressNotes = $address?->getDeliveryInstructions();
        $this->deliveryAddressCourierNotes = $address?->getCourierNotes();
        $this->deliveryAddressGpsLatitude = $address?->getGpsLatitude();
        $this->deliveryAddressGpsLongitude = $address?->getGpsLongitude();
        $this->deliveryAddressGpsAccuracyMeters = $address?->getGpsAccuracyMeters();

        return $this;
    }

    public function snapshotBillingAddress(?Address $address): self
    {
        $this->billingAddressLabel = $address?->getLabel();
        $this->billingAddressLine1 = $address?->getLine1();
        $this->billingAddressLine2 = $address?->getLine2();
        $this->billingAddressPostalCode = $address?->getPostalCode();
        $this->billingAddressCommune = $address?->getCommune();
        $this->billingAddressZoneCode = $address?->getDeliveryZone()?->getCode();
        $this->billingAddressZoneName = $address?->getDeliveryZone()?->getName();
        $this->billingAddressNotes = $address?->getNotes();

        return $this;
    }

    public function getDeliveryAddressLabel(): ?string { return $this->deliveryAddressLabel; }
    public function getDeliveryAddressLine1(): ?string { return $this->deliveryAddressLine1 ?? $this->deliveryAddress?->getLine1(); }
    public function getDeliveryAddressLine2(): ?string { return $this->deliveryAddressLine2 ?? $this->deliveryAddress?->getLine2(); }
    public function getDeliveryAddressPostalCode(): ?string { return $this->deliveryAddressPostalCode ?? $this->deliveryAddress?->getPostalCode(); }
    public function getDeliveryAddressCommune(): ?string { return $this->deliveryAddressCommune ?? $this->deliveryAddress?->getCommune(); }
    public function getDeliveryAddressLocalityName(): ?string { return $this->deliveryAddressLocalityName ?? $this->deliveryAddress?->getLocalityLabel(); }
    public function getDeliveryAddressZoneCode(): ?string { return $this->deliveryAddressZoneCode ?? $this->deliveryAddress?->getDeliveryZone()?->getCode(); }
    public function getDeliveryAddressZoneName(): ?string { return $this->deliveryAddressZoneName ?? $this->deliveryAddress?->getDeliveryZone()?->getName(); }
    public function getDeliveryAddressNotes(): ?string { return $this->deliveryAddressNotes ?? $this->deliveryAddress?->getDeliveryInstructions(); }
    public function getDeliveryAddressInstructions(): ?string { return $this->getDeliveryAddressNotes(); }
    public function getDeliveryAddressCourierNotes(): ?string { return $this->deliveryAddressCourierNotes ?? $this->deliveryAddress?->getCourierNotes(); }
    public function setDeliveryAddressCourierNotes(?string $deliveryAddressCourierNotes): self
    {
        $deliveryAddressCourierNotes = $deliveryAddressCourierNotes !== null ? trim($deliveryAddressCourierNotes) : null;
        $this->deliveryAddressCourierNotes = $deliveryAddressCourierNotes !== '' ? $deliveryAddressCourierNotes : null;
        return $this;
    }

    public function getDeliveryAddressGpsLatitude(): ?string { return $this->deliveryAddressGpsLatitude ?? $this->deliveryAddress?->getGpsLatitude(); }
    public function getDeliveryAddressGpsLongitude(): ?string { return $this->deliveryAddressGpsLongitude ?? $this->deliveryAddress?->getGpsLongitude(); }
    public function getDeliveryAddressGpsAccuracyMeters(): ?string { return $this->deliveryAddressGpsAccuracyMeters ?? $this->deliveryAddress?->getGpsAccuracyMeters(); }

    public function hasDeliveryGpsCoordinates(): bool
    {
        return $this->getDeliveryAddressGpsLatitude() !== null && $this->getDeliveryAddressGpsLongitude() !== null;
    }

    public function getDeliveryGpsCoordinatesLabel(): ?string
    {
        if (!$this->hasDeliveryGpsCoordinates()) {
            return null;
        }

        $label = sprintf('%s, %s', $this->getDeliveryAddressGpsLatitude(), $this->getDeliveryAddressGpsLongitude());

        if ($this->getDeliveryAddressGpsAccuracyMeters() !== null) {
            $accuracy = rtrim(rtrim((string) $this->getDeliveryAddressGpsAccuracyMeters(), '0'), '.');
            $label .= sprintf(' (precision ~%s m)', $accuracy !== '' ? $accuracy : '0');
        }

        return $label;
    }

    public function getDeliveryGpsMapUrl(): ?string
    {
        if (!$this->hasDeliveryGpsCoordinates()) {
            return null;
        }

        return sprintf('https://www.google.com/maps?q=%s,%s', $this->getDeliveryAddressGpsLatitude(), $this->getDeliveryAddressGpsLongitude());
    }

    public function getDeliveryPoint(): ?DeliveryPoint { return $this->deliveryPoint; }
    public function setDeliveryPoint(?DeliveryPoint $deliveryPoint): self { $this->deliveryPoint = $deliveryPoint; return $this; }

    public function snapshotDeliveryPoint(
        ?DeliveryPoint $point,
        ?DeliveryPointTimeWindow $timeWindow = null,
        ?string $customerInstructions = null,
        ?string $timeWindowLabel = null,
        ?\DateTimeImmutable $scheduledDate = null,
        ?\DateTimeImmutable $scheduledTime = null
    ): self
    {
        $this->deliveryPoint = $point;
        $deliveryCommune = $point?->getDeliveryCommune();

        $this->deliveryPointName = $point?->getName();
        $this->deliveryPointCode = $point?->getCode();
        $this->deliveryPointType = $point?->getType();
        $this->deliveryPointAddressLine1 = $point?->getLine1();
        $this->deliveryPointAddressLine2 = $point?->getLine2();
        $this->deliveryPointPostalCode = $point ? ($point->getPostalCode() ?: $deliveryCommune?->getPostalCode()) : null;
        $this->deliveryPointCommune = $point ? ($point->getCommuneName() ?: $deliveryCommune?->getName()) : null;
        $this->deliveryPointPublicInstructions = $point?->getPublicInstructions();
        $this->deliveryPointCourierInstructions = $point?->getCourierInstructions();
        $this->deliveryPointCustomerInstructions = $this->normalizeNullableOrderText($customerInstructions);
        $this->deliveryPointGpsLatitude = $point?->getGpsLatitude();
        $this->deliveryPointGpsLongitude = $point?->getGpsLongitude();
        $this->deliveryPointGpsAccuracyMeters = $point?->getGpsAccuracyMeters();
        $this->deliveryPointTimeWindowLabel = $timeWindowLabel;
        $this->deliveryPointScheduledDate = $scheduledDate;
        $this->deliveryPointScheduledTime = $scheduledTime;
        $this->deliveryPointTimeWindowWeekday = $timeWindow?->getWeekday();
        $this->deliveryPointStartTime = $timeWindow?->getStartTime();
        $this->deliveryPointEndTime = $timeWindow?->getEndTime();

        return $this;
    }

    public function hasDeliveryPoint(): bool { return $this->deliveryPointName !== null || $this->deliveryPointCode !== null; }
    public function getDeliveryPointName(): ?string { return $this->deliveryPointName; }
    public function getDeliveryPointCode(): ?string { return $this->deliveryPointCode; }
    public function getDeliveryPointType(): ?string { return $this->deliveryPointType; }
    public function getDeliveryPointAddressLine1(): ?string { return $this->deliveryPointAddressLine1; }
    public function getDeliveryPointAddressLine2(): ?string { return $this->deliveryPointAddressLine2; }
    public function getDeliveryPointPostalCode(): ?string { return $this->deliveryPointPostalCode; }
    public function getDeliveryPointCommune(): ?string { return $this->deliveryPointCommune; }
    public function getDeliveryPointPublicInstructions(): ?string { return $this->deliveryPointPublicInstructions; }
    public function getDeliveryPointCourierInstructions(): ?string { return $this->deliveryPointCourierInstructions; }
    public function getDeliveryPointCustomerInstructions(): ?string { return $this->deliveryPointCustomerInstructions; }
    public function getDeliveryPointGpsLatitude(): ?string { return $this->deliveryPointGpsLatitude; }
    public function getDeliveryPointGpsLongitude(): ?string { return $this->deliveryPointGpsLongitude; }
    public function getDeliveryPointGpsAccuracyMeters(): ?int { return $this->deliveryPointGpsAccuracyMeters; }
    public function getDeliveryPointTimeWindowLabel(): ?string { return $this->deliveryPointTimeWindowLabel; }
    public function getDeliveryPointScheduledDate(): ?\DateTimeImmutable { return $this->deliveryPointScheduledDate; }
    public function getDeliveryPointScheduledTime(): ?\DateTimeImmutable { return $this->deliveryPointScheduledTime; }
    public function getDeliveryPointTimeWindowWeekday(): ?int { return $this->deliveryPointTimeWindowWeekday; }
    public function getDeliveryPointStartTime(): ?\DateTimeImmutable { return $this->deliveryPointStartTime; }
    public function getDeliveryPointEndTime(): ?\DateTimeImmutable { return $this->deliveryPointEndTime; }

    public function getDeliveryPointSummary(): ?string
    {
        if (!$this->hasDeliveryPoint()) {
            return null;
        }

        $postalCommune = trim(sprintf('%s %s', (string) $this->deliveryPointPostalCode, (string) $this->deliveryPointCommune));
        $parts = array_filter([
            $this->deliveryPointName,
            $this->deliveryPointAddressLine1,
            $this->deliveryPointAddressLine2,
            $postalCommune,
        ]);

        return $parts !== [] ? implode("\n", $parts) : null;
    }

    public function getDeliveryPointAppointmentSummary(): ?string
    {
        if ($this->deliveryPointScheduledDate === null || $this->deliveryPointScheduledTime === null) {
            return null;
        }

        return sprintf(
            '%s à %s',
            $this->deliveryPointScheduledDate->format('d/m/Y'),
            $this->deliveryPointScheduledTime->format('H:i')
        );
    }

    public function getDeliveryPointTimeWindowSummary(): ?string
    {
        if ($this->deliveryPointTimeWindowLabel !== null) {
            return $this->deliveryPointTimeWindowLabel;
        }

        if ($this->deliveryPointStartTime === null || $this->deliveryPointEndTime === null) {
            return null;
        }

        return sprintf('%s–%s', $this->deliveryPointStartTime->format('H:i'), $this->deliveryPointEndTime->format('H:i'));
    }

    public function getDeliveryPointGpsMapUrl(): ?string
    {
        if ($this->deliveryPointGpsLatitude === null || $this->deliveryPointGpsLongitude === null) {
            return null;
        }

        return sprintf('https://www.google.com/maps?q=%s,%s', $this->deliveryPointGpsLatitude, $this->deliveryPointGpsLongitude);
    }

    public function getBillingAddressLabel(): ?string { return $this->billingAddressLabel; }
    public function getBillingAddressLine1(): ?string { return $this->billingAddressLine1; }
    public function getBillingAddressLine2(): ?string { return $this->billingAddressLine2; }
    public function getBillingAddressPostalCode(): ?string { return $this->billingAddressPostalCode; }
    public function getBillingAddressCommune(): ?string { return $this->billingAddressCommune; }
    public function getBillingAddressZoneCode(): ?string { return $this->billingAddressZoneCode; }
    public function getBillingAddressZoneName(): ?string { return $this->billingAddressZoneName; }
    public function getBillingAddressNotes(): ?string { return $this->billingAddressNotes; }

    public function getDeliveryAddressSummary(): string
    {
        $line1 = trim((string) $this->getDeliveryAddressLine1());
        $line2 = trim((string) $this->getDeliveryAddressLine2());
        $postalCommune = trim(sprintf('%s %s', (string) $this->getDeliveryAddressPostalCode(), (string) $this->getDeliveryAddressCommune()));

        $locality = trim((string) $this->getDeliveryAddressLocalityName());
        $parts = array_filter([$line1, $line2, $locality, $postalCommune]);

        return $parts !== [] ? implode("
", $parts) : 'Adresse non renseignée.';
    }

    public function getBillingAddressSummary(): string
    {
        $line1 = trim((string) $this->getBillingAddressLine1());
        $line2 = trim((string) $this->getBillingAddressLine2());
        $postalCommune = trim(sprintf('%s %s', (string) $this->getBillingAddressPostalCode(), (string) $this->getBillingAddressCommune()));

        $parts = array_filter([$line1, $line2, $postalCommune]);

        return $parts !== [] ? implode("
", $parts) : 'Adresse de facturation non renseignée.';
    }

    /**
     * @return Collection<int, OrderItem>
     */
    public function getItems(): Collection
    {
        return $this->items;
    }

    public function addItem(OrderItem $item): self
    {
        if (!$this->items->contains($item)) {
            $this->items->add($item);
            $item->setCustomerOrder($this);
        }

        return $this;
    }

    public function removeItem(OrderItem $item): self
    {
        $this->items->removeElement($item);

        return $this;
    }

    public function getItemsSummary(): string
    {
        if ($this->items->isEmpty()) {
            return 'Aucune ligne de commande.';
        }

        $lines = [];
        foreach ($this->items as $item) {
            $product = $item->getProduct()?->getName() ?? 'Produit';
            $seller = (string) $item->getSeller();
            $lines[] = sprintf(
                '%s — %d x %s € = %s € — %s',
                $product,
                $item->getQuantity(),
                $item->getUnitPrice(),
                $item->getLineTotal(),
                $seller
            );
        }

        return implode("\n", $lines);
    }

    public function getOrderReference(): ?string { return $this->orderReference; }
    public function setOrderReference(?string $orderReference): self { $this->orderReference = $orderReference; return $this; }

    public function getDailyOrderNumber(): ?int { return $this->dailyOrderNumber; }
    public function setDailyOrderNumber(?int $dailyOrderNumber): self { $this->dailyOrderNumber = $dailyOrderNumber; return $this; }

    public function getOrderReferenceDate(): ?\DateTimeImmutable { return $this->orderReferenceDate; }
    public function setOrderReferenceDate(?\DateTimeImmutable $orderReferenceDate): self { $this->orderReferenceDate = $orderReferenceDate; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self { $this->status = $status; return $this; }

    public function getPaymentStatus(): string { return $this->paymentStatus; }
    public function setPaymentStatus(string $paymentStatus): self { $this->paymentStatus = $paymentStatus; return $this; }

    public function getSubtotal(): string { return $this->subtotal; }
    public function setSubtotal(string $subtotal): self { $this->subtotal = $subtotal; return $this; }

    public function getDeliveryFee(): string { return $this->deliveryFee; }
    public function setDeliveryFee(string $deliveryFee): self { $this->deliveryFee = $deliveryFee; return $this; }

    public function getTotal(): string { return $this->total; }
    public function setTotal(string $total): self { $this->total = $total; return $this; }

    public function getCustomerTimezone(): ?string { return $this->customerTimezone; }
    public function setCustomerTimezone(?string $customerTimezone): self
    {
        $customerTimezone = $customerTimezone !== null ? trim($customerTimezone) : null;
        $this->customerTimezone = $customerTimezone !== '' ? mb_substr((string) $customerTimezone, 0, 80) : null;

        return $this;
    }

    public function getDisplayTimezone(): string
    {
        return $this->customerTimezone ?: 'Indian/Mayotte';
    }

    /** @return array<string, mixed>|null */
    public function getDeliveryLogisticsSnapshot(): ?array { return $this->deliveryLogisticsSnapshot; }

    /** @param array<string, mixed>|null $deliveryLogisticsSnapshot */
    public function setDeliveryLogisticsSnapshot(?array $deliveryLogisticsSnapshot): self
    {
        $this->deliveryLogisticsSnapshot = $deliveryLogisticsSnapshot;

        return $this;
    }


    /** @return array<string, mixed>|null */
    public function getSellerCollectionSnapshot(): ?array { return $this->sellerCollectionSnapshot; }

    /** @param array<string, mixed>|null $sellerCollectionSnapshot */
    public function setSellerCollectionSnapshot(?array $sellerCollectionSnapshot): self
    {
        $this->sellerCollectionSnapshot = $sellerCollectionSnapshot;

        return $this;
    }

    /** @return array<string, array<string, mixed>> */
    public function getSellerCollectionSnapshotEntries(): array
    {
        $snapshot = $this->sellerCollectionSnapshot ?? [];

        return is_array($snapshot) ? $snapshot : [];
    }

    /** @return array<string, mixed>|null */
    public function getSellerCollectionEntry(Seller $seller): ?array
    {
        $sellerId = $seller->getId();
        if ($sellerId === null) {
            return null;
        }

        $snapshot = $this->getSellerCollectionSnapshotEntries();
        $entry = $snapshot[(string) $sellerId] ?? null;

        return is_array($entry) ? $entry : null;
    }

    public function isSellerCollected(Seller $seller): bool
    {
        $entry = $this->getSellerCollectionEntry($seller);

        return ($entry['status'] ?? null) === 'COLLECTED';
    }

    public function hasSellerCollectionCodeBeenSent(Seller $seller): bool
    {
        $entry = $this->getSellerCollectionEntry($seller);

        return is_array($entry)
            && ($entry['status'] ?? null) === 'CODE_SENT'
            && trim((string) ($entry['codeHash'] ?? '')) !== '';
    }

    public function prepareSellerCollectionCode(Seller $seller, string $codeHash, ?Customer $courier = null): self
    {
        $sellerId = $seller->getId();
        if ($sellerId === null) {
            return $this;
        }

        $snapshot = $this->getSellerCollectionSnapshotEntries();
        $entry = $snapshot[(string) $sellerId] ?? [];
        $entry = is_array($entry) ? $entry : [];

        $entry['status'] = 'CODE_SENT';
        $entry['sellerLabel'] = $seller->getCourierDisplayName();
        $entry['codeHash'] = $codeHash;
        $entry['codeSentAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $entry['codeRequestedByCourierId'] = $courier?->getId();
        $entry['codeRequestedByCourierLabel'] = $this->buildCourierLabel($courier);
        $entry['failedAttempts'] = 0;

        $snapshot[(string) $sellerId] = $entry;
        $this->sellerCollectionSnapshot = $snapshot;

        return $this;
    }

    public function updateSellerCollectionCodeLogs(Seller $seller, ?int $smsLogId, ?int $emailLogId): self
    {
        $sellerId = $seller->getId();
        if ($sellerId === null) {
            return $this;
        }

        $snapshot = $this->getSellerCollectionSnapshotEntries();
        $entry = $snapshot[(string) $sellerId] ?? [];
        $entry = is_array($entry) ? $entry : [];

        $entry['smsLogId'] = $smsLogId;
        $entry['emailLogId'] = $emailLogId;

        $snapshot[(string) $sellerId] = $entry;
        $this->sellerCollectionSnapshot = $snapshot;

        return $this;
    }

    public function incrementSellerCollectionFailedAttempt(Seller $seller): self
    {
        $sellerId = $seller->getId();
        if ($sellerId === null) {
            return $this;
        }

        $snapshot = $this->getSellerCollectionSnapshotEntries();
        $entry = $snapshot[(string) $sellerId] ?? [];
        $entry = is_array($entry) ? $entry : [];
        $entry['failedAttempts'] = ((int) ($entry['failedAttempts'] ?? 0)) + 1;
        $entry['lastFailedAttemptAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);

        $snapshot[(string) $sellerId] = $entry;
        $this->sellerCollectionSnapshot = $snapshot;

        return $this;
    }

    public function markSellerCollected(Seller $seller, ?Customer $courier = null, ?string $note = null, string $validationMode = 'UNKNOWN'): self
    {
        $sellerId = $seller->getId();
        if ($sellerId === null) {
            return $this;
        }

        $snapshot = $this->getSellerCollectionSnapshotEntries();
        $entry = $snapshot[(string) $sellerId] ?? [];
        $entry = is_array($entry) ? $entry : [];

        $note = $note !== null ? trim($note) : null;
        $entry['status'] = 'COLLECTED';
        $entry['sellerLabel'] = $seller->getCourierDisplayName();
        $entry['collectedAt'] = (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM);
        $entry['courierId'] = $courier?->getId();
        $entry['courierLabel'] = $this->buildCourierLabel($courier);
        $entry['validationMode'] = $validationMode;
        $entry['note'] = $note !== '' ? $note : null;
        unset($entry['codeHash']);

        $snapshot[(string) $sellerId] = $entry;
        $this->sellerCollectionSnapshot = $snapshot;

        return $this;
    }

    /** @return array<int, Seller> */
    public function getDistinctSellers(): array
    {
        $sellersById = [];

        foreach ($this->items as $item) {
            if (!$item instanceof OrderItem) {
                continue;
            }

            $seller = $item->getSeller();
            if (!$seller instanceof Seller || $seller->getId() === null) {
                continue;
            }

            $sellersById[$seller->getId()] = $seller;
        }

        return array_values($sellersById);
    }

    /** @return array{collected: int, total: int} */
    public function getSellerCollectionProgress(): array
    {
        $total = 0;
        $collected = 0;

        foreach ($this->getDistinctSellers() as $seller) {
            ++$total;
            if ($this->isSellerCollected($seller)) {
                ++$collected;
            }
        }

        return [
            'collected' => $collected,
            'total' => $total,
        ];
    }

    public function areAllSellerCollectionsDone(): bool
    {
        $progress = $this->getSellerCollectionProgress();

        return $progress['total'] === 0 || $progress['collected'] >= $progress['total'];
    }

    public function containsSeller(Seller $seller): bool
    {
        $sellerId = $seller->getId();
        if ($sellerId === null) {
            return false;
        }

        foreach ($this->getDistinctSellers() as $orderSeller) {
            if ($orderSeller->getId() === $sellerId) {
                return true;
            }
        }

        return false;
    }

    private function buildCourierLabel(?Customer $courier): ?string
    {
        if (!$courier instanceof Customer) {
            return null;
        }

        $label = trim(sprintf('%s %s', (string) $courier->getFirstName(), (string) $courier->getLastName()));

        return $label !== '' ? $label : ('Livreur #' . $courier->getId());
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getDeliveryZone(): ?DeliveryZone { return $this->deliveryZone; }
    public function setDeliveryZone(?DeliveryZone $deliveryZone): static { $this->deliveryZone = $deliveryZone; return $this; }

    public function getSubmittedAt(): ?\DateTimeImmutable { return $this->submittedAt; }
    public function setSubmittedAt(?\DateTimeImmutable $submittedAt): static { $this->submittedAt = $submittedAt; return $this; }

    public function getConfirmedAt(): ?\DateTimeImmutable { return $this->confirmedAt; }
    public function setConfirmedAt(?\DateTimeImmutable $confirmedAt): self { $this->confirmedAt = $confirmedAt; return $this; }

    public function getPreparingAt(): ?\DateTimeImmutable { return $this->preparingAt; }
    public function setPreparingAt(?\DateTimeImmutable $preparingAt): self { $this->preparingAt = $preparingAt; return $this; }

    public function getReadyAt(): ?\DateTimeImmutable { return $this->readyAt; }
    public function setReadyAt(?\DateTimeImmutable $readyAt): self { $this->readyAt = $readyAt; return $this; }

    public function getDeliveredAt(): ?\DateTimeImmutable { return $this->deliveredAt; }
    public function setDeliveredAt(?\DateTimeImmutable $deliveredAt): self { $this->deliveredAt = $deliveredAt; return $this; }

    public function getCanceledAt(): ?\DateTimeImmutable { return $this->canceledAt; }
    public function setCanceledAt(?\DateTimeImmutable $canceledAt): self { $this->canceledAt = $canceledAt; return $this; }

    public function getAssignedCourier(): ?Customer { return $this->assignedCourier; }
    public function setAssignedCourier(?Customer $assignedCourier): self { $this->assignedCourier = $assignedCourier; return $this; }

    public function getCourierAssignedAt(): ?\DateTimeImmutable { return $this->courierAssignedAt; }
    public function setCourierAssignedAt(?\DateTimeImmutable $courierAssignedAt): self { $this->courierAssignedAt = $courierAssignedAt; return $this; }

    public function getOutForDeliveryAt(): ?\DateTimeImmutable { return $this->outForDeliveryAt; }
    public function setOutForDeliveryAt(?\DateTimeImmutable $outForDeliveryAt): self { $this->outForDeliveryAt = $outForDeliveryAt; return $this; }

    public function getDeliveryValidationCodeEncrypted(): ?string { return $this->deliveryValidationCodeEncrypted; }
    public function setDeliveryValidationCodeEncrypted(?string $deliveryValidationCodeEncrypted): self
    {
        $deliveryValidationCodeEncrypted = $deliveryValidationCodeEncrypted !== null ? trim($deliveryValidationCodeEncrypted) : null;
        $this->deliveryValidationCodeEncrypted = $deliveryValidationCodeEncrypted !== '' ? $deliveryValidationCodeEncrypted : null;

        return $this;
    }

    public function getDeliveryValidationCodeSentAt(): ?\DateTimeImmutable { return $this->deliveryValidationCodeSentAt; }
    public function setDeliveryValidationCodeSentAt(?\DateTimeImmutable $deliveryValidationCodeSentAt): self
    {
        $this->deliveryValidationCodeSentAt = $deliveryValidationCodeSentAt;

        return $this;
    }

    public function getDeliveryValidationCodeValidatedAt(): ?\DateTimeImmutable { return $this->deliveryValidationCodeValidatedAt; }
    public function setDeliveryValidationCodeValidatedAt(?\DateTimeImmutable $deliveryValidationCodeValidatedAt): self
    {
        $this->deliveryValidationCodeValidatedAt = $deliveryValidationCodeValidatedAt;

        return $this;
    }

    public function getDeliveryValidationCodeSendCount(): int { return $this->deliveryValidationCodeSendCount; }
    public function setDeliveryValidationCodeSendCount(int $deliveryValidationCodeSendCount): self
    {
        $this->deliveryValidationCodeSendCount = max(0, $deliveryValidationCodeSendCount);

        return $this;
    }

    public function getDeliveryValidationCodeFailedAttempts(): int { return $this->deliveryValidationCodeFailedAttempts; }
    public function setDeliveryValidationCodeFailedAttempts(int $deliveryValidationCodeFailedAttempts): self
    {
        $this->deliveryValidationCodeFailedAttempts = max(0, $deliveryValidationCodeFailedAttempts);

        return $this;
    }

    public function getDeliveryValidationSmsLogId(): ?int { return $this->deliveryValidationSmsLogId; }
    public function setDeliveryValidationSmsLogId(?int $deliveryValidationSmsLogId): self
    {
        $this->deliveryValidationSmsLogId = $deliveryValidationSmsLogId;

        return $this;
    }

    public function getDeliveryValidationEmailLogId(): ?int { return $this->deliveryValidationEmailLogId; }
    public function setDeliveryValidationEmailLogId(?int $deliveryValidationEmailLogId): self
    {
        $this->deliveryValidationEmailLogId = $deliveryValidationEmailLogId;

        return $this;
    }

    private function normalizeNullableOrderText(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    public function hasPendingDeliveryValidationCode(): bool
    {
        return $this->deliveryValidationCodeEncrypted !== null
            && $this->deliveryValidationCodeValidatedAt === null;
    }

    public function prepareDeliveryValidationCode(string $encryptedCode): self
    {
        $this->deliveryValidationCodeEncrypted = trim($encryptedCode);
        $this->deliveryValidationCodeValidatedAt = null;
        $this->deliveryValidationCodeFailedAttempts = 0;

        return $this;
    }

    public function registerDeliveryValidationCodeDispatch(?int $smsLogId, ?int $emailLogId): self
    {
        $this->deliveryValidationCodeSentAt = new \DateTimeImmutable();
        $this->deliveryValidationCodeSendCount++;
        $this->deliveryValidationSmsLogId = $smsLogId;
        $this->deliveryValidationEmailLogId = $emailLogId;

        return $this;
    }

    public function incrementDeliveryValidationCodeFailedAttempt(): self
    {
        $this->deliveryValidationCodeFailedAttempts++;

        return $this;
    }

    public function markDeliveryValidationCodeValidated(): self
    {
        $this->deliveryValidationCodeValidatedAt = new \DateTimeImmutable();
        $this->deliveryValidationCodeEncrypted = null;

        return $this;
    }
}
