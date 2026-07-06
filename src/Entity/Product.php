<?php

namespace App\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;


#[ORM\Entity]
#[ORM\Table(name: 'product')]
#[ORM\HasLifecycleCallbacks]
class Product
{
    public const DELIVERY_MODE_STANDARD = 'STANDARD';
    public const DELIVERY_MODE_POINT_REQUIRED = 'DELIVERY_POINT_REQUIRED';
    public const DELIVERY_MODE_POINT_OPTIONAL = 'DELIVERY_POINT_OPTIONAL';

    public const DELIVERY_PROMISE_MODE_SECTOR_SCHEDULE = 'SECTOR_SCHEDULE';
    public const DELIVERY_PROMISE_MODE_APPOINTMENT = 'APPOINTMENT';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

	#[ORM\OneToMany(
        mappedBy: 'product',
        targetEntity: ProductImage::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['position' => 'ASC', 'id' => 'ASC'])]
    private Collection $images;

	#[ORM\Column(length: 255, unique: true)]
	private string $slug;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Seller $seller;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Category $category;

    #[ORM\Column(length: 160)]
    private string $name;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $description = null;

    /**
     * Prix historique conservé pour compatibilité.
     * À partir de J5E, le prix client affiché doit être calculé par ProductPricingService.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $price = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $producerPrice = null;

    /** Taux de marge produit en pourcentage. Exemple : 20.00 = 20 %. */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $marginRate = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $unit = null;

    #[ORM\Column(nullable: true)]
    private ?int $stockQty = null;

    #[ORM\Column]
    private bool $isUnlimitedStock = false;

    #[ORM\Column]
    private bool $isPreorder = false;

    #[ORM\Column(nullable: true)]
    private ?int $manufacturingDays = null;

    #[ORM\Column]
    private int $deliveryDays = 1;

    #[ORM\Column(length: 40)]
    private string $deliveryMode = self::DELIVERY_MODE_STANDARD;

    #[ORM\Column(nullable: true)]
    private ?int $minimumOrderLeadTimeHours = null;

    #[ORM\Column(length: 40)]
    private string $deliveryPromiseMode = self::DELIVERY_PROMISE_MODE_SECTOR_SCHEDULE;

    #[ORM\Column(length: 160, nullable: true)]
    private ?string $deliveryPromiseTitle = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $deliveryPromiseDescription = null;

    /** @var list<int>|null */
    #[ORM\Column(nullable: true)]
    private ?array $appointmentDeliveryWeekdays = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $appointmentTimeWindowStart = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $appointmentTimeWindowEnd = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $appointmentCutoffTime = null;

    #[ORM\Column]
    private int $appointmentCutoffDaysBefore = 1;

    #[ORM\OneToMany(mappedBy: 'product', targetEntity: ProductDeliveryPoint::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $productDeliveryPoints;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private bool $isFeatured = false;

    #[ORM\Column]
    private int $displayPriority = 0;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

	public function __construct()
	{
		$this->createdAt = new \DateTimeImmutable();
		$this->images = new ArrayCollection();
        $this->productDeliveryPoints = new ArrayCollection();
	}

		#[ORM\PrePersist]
	#[ORM\PreUpdate]
	public function updateSlug(): void
	{
		if (!empty($this->name)) {
			$this->slug = self::slugify($this->name);
		}
	}

	public function getSlug(): string
	{
		return $this->slug;
	}

	public function setSlug(string $slug): self
	{
		$this->slug = $slug;
		return $this;
	}


	private static function slugify(string $text): string
	{
		$text = trim($text);

		// minuscule
		$text = mb_strtolower($text, 'UTF-8');

		// enlève accents (ex: "légumes" -> "legumes")
		$converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
		if ($converted !== false) {
			$text = $converted;
		}

		// remplace tout ce qui n'est pas a-z0-9 par des tirets
		$text = preg_replace('~[^a-z0-9]+~', '-', $text);
		$text = trim((string) $text, '-');

		return $text !== '' ? $text : 'n-a';
	}

	public function getImages(): Collection
	{
		return $this->images;
	}

	public function addImage(ProductImage $image): self
	{
		if (!$this->images->contains($image)) {
			$image->setProduct($this);

			// position auto: dernière position + 1
			$image->setPosition($this->images->count());

			$this->images->add($image);
		}

		return $this;
	}

	public function removeImage(ProductImage $image): self
	{
		if ($this->images->removeElement($image)) {
			// orphanRemoval => supprimera en DB
		}
		return $this;
	}

    public function __toString(): string
    {
        return $this->name ?? 'Produit #' . $this->id;
    }

    public function getId(): ?int { return $this->id; }

    public function getSeller(): Seller { return $this->seller; }
    public function setSeller(Seller $seller): self { $this->seller = $seller; return $this; }

    public function getCategory(): Category { return $this->category; }
    public function setCategory(Category $category): self { $this->category = $category; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; $this->touch(); return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; $this->touch(); return $this; }

    public function getPrice(): string { return $this->price; }
    public function setPrice(string $price): self { $this->price = $price; $this->touch(); return $this; }

    public function getProducerPrice(): ?string { return $this->producerPrice; }
    public function setProducerPrice(?string $producerPrice): self { $this->producerPrice = $producerPrice !== null ? number_format((float) $producerPrice, 2, '.', '') : null; $this->touch(); return $this; }

    public function getMarginRate(): ?string { return $this->marginRate; }
    public function setMarginRate(?string $marginRate): self { $this->marginRate = $marginRate !== null && trim((string) $marginRate) !== '' ? number_format((float) $marginRate, 2, '.', '') : null; $this->touch(); return $this; }

    public function getUnit(): ?string { return $this->unit; }
    public function setUnit(?string $unit): self { $this->unit = $unit; $this->touch(); return $this; }

    /** @return array<string, string> */
    public static function getUnitLabels(): array
    {
        return [
            'UNIT' => 'À l’unité',
            'KG' => 'Au kilo',
            'G' => 'Au gramme',
            'L' => 'Au litre',
        ];
    }

    public function getUnitLabel(): string
    {
        $unit = $this->unit !== null ? trim($this->unit) : '';

        return self::getUnitLabels()[$unit] ?? 'À l’unité';
    }

    public function getStockQty(): ?int { return $this->stockQty; }
    public function setStockQty(?int $stockQty): self { $this->stockQty = $stockQty; $this->touch(); return $this; }

    public function isUnlimitedStock(): bool { return $this->isUnlimitedStock; }
    public function setIsUnlimitedStock(bool $isUnlimitedStock): self { $this->isUnlimitedStock = $isUnlimitedStock; $this->touch(); return $this; }

    public function isPreorder(): bool { return $this->isPreorder; }
    public function setIsPreorder(bool $isPreorder): self { $this->isPreorder = $isPreorder; $this->touch(); return $this; }

    public function getManufacturingDays(): ?int { return $this->manufacturingDays; }
    public function setManufacturingDays(?int $manufacturingDays): self { $this->manufacturingDays = $manufacturingDays; $this->touch(); return $this; }

    public function getDeliveryDays(): int { return $this->deliveryDays; }
    public function setDeliveryDays(int $deliveryDays): self { $this->deliveryDays = $deliveryDays; $this->touch(); return $this; }

    public function getMinimumOrderLeadTimeHours(): ?int { return $this->minimumOrderLeadTimeHours; }
    public function setMinimumOrderLeadTimeHours(?int $minimumOrderLeadTimeHours): self
    {
        $this->minimumOrderLeadTimeHours = $minimumOrderLeadTimeHours !== null && $minimumOrderLeadTimeHours > 0 ? $minimumOrderLeadTimeHours : null;
        $this->touch();

        return $this;
    }

    public function getDeliveryPromiseMode(): string { return $this->deliveryPromiseMode; }

    public function setDeliveryPromiseMode(string $deliveryPromiseMode): self
    {
        $deliveryPromiseMode = mb_strtoupper(trim($deliveryPromiseMode));
        $this->deliveryPromiseMode = in_array($deliveryPromiseMode, self::getDeliveryPromiseModes(), true)
            ? $deliveryPromiseMode
            : self::DELIVERY_PROMISE_MODE_SECTOR_SCHEDULE;
        $this->touch();

        return $this;
    }

    public function isAppointmentDeliveryPromise(): bool
    {
        return $this->deliveryPromiseMode === self::DELIVERY_PROMISE_MODE_APPOINTMENT;
    }

    public function getDeliveryPromiseModeLabel(): string
    {
        return self::getDeliveryPromiseModeLabels()[$this->deliveryPromiseMode] ?? $this->deliveryPromiseMode;
    }

    public function getDeliveryPromiseTitle(): ?string { return $this->deliveryPromiseTitle; }
    public function setDeliveryPromiseTitle(?string $deliveryPromiseTitle): self
    {
        $deliveryPromiseTitle = $deliveryPromiseTitle !== null ? trim($deliveryPromiseTitle) : null;
        $this->deliveryPromiseTitle = $deliveryPromiseTitle !== '' ? $deliveryPromiseTitle : null;
        $this->touch();

        return $this;
    }

    public function getDeliveryPromiseDescription(): ?string { return $this->deliveryPromiseDescription; }
    public function setDeliveryPromiseDescription(?string $deliveryPromiseDescription): self
    {
        $deliveryPromiseDescription = $deliveryPromiseDescription !== null ? trim($deliveryPromiseDescription) : null;
        $this->deliveryPromiseDescription = $deliveryPromiseDescription !== '' ? $deliveryPromiseDescription : null;
        $this->touch();

        return $this;
    }

    /** @return list<int> */
    public function getAppointmentDeliveryWeekdays(): array
    {
        $weekdays = is_array($this->appointmentDeliveryWeekdays) ? $this->appointmentDeliveryWeekdays : [];
        $normalized = [];

        foreach ($weekdays as $weekday) {
            $weekday = (int) $weekday;
            if ($weekday >= 1 && $weekday <= 7) {
                $normalized[] = $weekday;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);

        return $normalized;
    }

    /** @param list<int>|array<int|string, mixed>|null $appointmentDeliveryWeekdays */
    public function setAppointmentDeliveryWeekdays(?array $appointmentDeliveryWeekdays): self
    {
        $normalized = [];

        foreach ($appointmentDeliveryWeekdays ?? [] as $weekday) {
            $weekday = (int) $weekday;
            if ($weekday >= 1 && $weekday <= 7) {
                $normalized[] = $weekday;
            }
        }

        $normalized = array_values(array_unique($normalized));
        sort($normalized);
        $this->appointmentDeliveryWeekdays = $normalized !== [] ? $normalized : null;
        $this->touch();

        return $this;
    }

    public function getAppointmentTimeWindowStart(): ?\DateTimeImmutable { return $this->appointmentTimeWindowStart; }
    public function setAppointmentTimeWindowStart(?\DateTimeImmutable $appointmentTimeWindowStart): self { $this->appointmentTimeWindowStart = $appointmentTimeWindowStart; $this->touch(); return $this; }

    public function getAppointmentTimeWindowEnd(): ?\DateTimeImmutable { return $this->appointmentTimeWindowEnd; }
    public function setAppointmentTimeWindowEnd(?\DateTimeImmutable $appointmentTimeWindowEnd): self { $this->appointmentTimeWindowEnd = $appointmentTimeWindowEnd; $this->touch(); return $this; }

    public function getAppointmentCutoffTime(): ?\DateTimeImmutable { return $this->appointmentCutoffTime; }
    public function setAppointmentCutoffTime(?\DateTimeImmutable $appointmentCutoffTime): self { $this->appointmentCutoffTime = $appointmentCutoffTime; $this->touch(); return $this; }

    public function getAppointmentCutoffDaysBefore(): int { return $this->appointmentCutoffDaysBefore; }
    public function setAppointmentCutoffDaysBefore(int $appointmentCutoffDaysBefore): self
    {
        $this->appointmentCutoffDaysBefore = max(0, $appointmentCutoffDaysBefore);
        $this->touch();

        return $this;
    }

    public function getDeliveryMode(): string { return $this->deliveryMode; }
    public function setDeliveryMode(string $deliveryMode): self
    {
        $deliveryMode = mb_strtoupper(trim($deliveryMode));
        $this->deliveryMode = in_array($deliveryMode, self::getDeliveryModes(), true) ? $deliveryMode : self::DELIVERY_MODE_STANDARD;
        $this->touch();

        return $this;
    }

    public function requiresDeliveryPoint(): bool
    {
        return $this->deliveryMode === self::DELIVERY_MODE_POINT_REQUIRED;
    }

    public function allowsDeliveryPoint(): bool
    {
        return in_array($this->deliveryMode, [self::DELIVERY_MODE_POINT_REQUIRED, self::DELIVERY_MODE_POINT_OPTIONAL], true);
    }

    public function allowsStandardDelivery(): bool
    {
        return in_array($this->deliveryMode, [self::DELIVERY_MODE_STANDARD, self::DELIVERY_MODE_POINT_OPTIONAL], true);
    }

    public function getDeliveryModeLabel(): string
    {
        return self::getDeliveryModeLabels()[$this->deliveryMode] ?? $this->deliveryMode;
    }

    /** @return Collection<int, ProductDeliveryPoint> */
    public function getProductDeliveryPoints(): Collection { return $this->productDeliveryPoints; }

    public function addProductDeliveryPoint(ProductDeliveryPoint $productDeliveryPoint): self
    {
        if (!$this->productDeliveryPoints->contains($productDeliveryPoint)) {
            $productDeliveryPoint->setProduct($this);
            $this->productDeliveryPoints->add($productDeliveryPoint);
        }

        return $this;
    }

    public function removeProductDeliveryPoint(ProductDeliveryPoint $productDeliveryPoint): self
    {
        $this->productDeliveryPoints->removeElement($productDeliveryPoint);

        return $this;
    }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; $this->touch(); return $this; }

    public function isFeatured(): bool { return $this->isFeatured; }
    public function setIsFeatured(bool $isFeatured): self { $this->isFeatured = $isFeatured; $this->touch(); return $this; }

    public function getDisplayPriority(): int { return $this->displayPriority; }
    public function setDisplayPriority(int $displayPriority): self { $this->displayPriority = max(0, $displayPriority); $this->touch(); return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    /** @return list<string> */
    public static function getDeliveryModes(): array
    {
        return [
            self::DELIVERY_MODE_STANDARD,
            self::DELIVERY_MODE_POINT_REQUIRED,
            self::DELIVERY_MODE_POINT_OPTIONAL,
        ];
    }

    /** @return array<string, string> */
    public static function getDeliveryModeLabels(): array
    {
        return [
            self::DELIVERY_MODE_STANDARD => 'Livraison standard uniquement',
            self::DELIVERY_MODE_POINT_REQUIRED => 'Point de remise imposé uniquement',
            self::DELIVERY_MODE_POINT_OPTIONAL => 'Livraison standard + point de remise',
        ];
    }

    /** @return list<string> */
    public static function getDeliveryPromiseModes(): array
    {
        return [
            self::DELIVERY_PROMISE_MODE_SECTOR_SCHEDULE,
            self::DELIVERY_PROMISE_MODE_APPOINTMENT,
        ];
    }

    /** @return array<string, string> */
    public static function getDeliveryPromiseModeLabels(): array
    {
        return [
            self::DELIVERY_PROMISE_MODE_SECTOR_SCHEDULE => 'Suit les passages du secteur client',
            self::DELIVERY_PROMISE_MODE_APPOINTMENT => 'Sur créneau / rendez-vous',
        ];
    }

    /** @return array<int, string> */
    public static function getWeekdayLabels(): array
    {
        return [
            1 => 'Lundi',
            2 => 'Mardi',
            3 => 'Mercredi',
            4 => 'Jeudi',
            5 => 'Vendredi',
            6 => 'Samedi',
            7 => 'Dimanche',
        ];
    }

    private function touch(): void { $this->updatedAt = new \DateTimeImmutable(); }

}
