<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'delivery_pricing_zone')]
class DeliveryPricingZone
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 40, unique: true)]
    private string $code;

    /** Montant payé par le client pour la livraison. */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $customerDeliveryFee = '0.00';

    /** Montant prévu pour rémunérer le livreur. */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $courierPayout = '0.00';

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $internalNote = null;

    /** Libellé public affiché au client, sans jargon interne. */
    #[ORM\Column(length: 120, nullable: true)]
    private ?string $publicLabel = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $publicDescription = null;

    /**
     * Jours de passage standard du secteur. Convention : 1=lundi ... 7=dimanche.
     *
     * @var list<int>|null
     */
    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $deliveryWeekdays = null;

    #[ORM\Column(type: 'time_immutable', nullable: true)]
    private ?\DateTimeImmutable $cutoffTime = null;

    #[ORM\Column(options: ['default' => 1])]
    private int $cutoffDaysBefore = 1;

    #[ORM\Column(options: ['default' => true])]
    private bool $isDeliveryScheduleActive = true;

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
        return sprintf('%s — %s', $this->code, $this->name);
    }

    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; $this->touch(); return $this; }

    public function getCode(): string { return $this->code; }
    public function setCode(string $code): self { $this->code = mb_strtoupper(trim($code)); $this->touch(); return $this; }

    public function getCustomerDeliveryFee(): string { return $this->customerDeliveryFee; }
    public function setCustomerDeliveryFee(string|float|int $customerDeliveryFee): self { $this->customerDeliveryFee = number_format((float) $customerDeliveryFee, 2, '.', ''); $this->touch(); return $this; }

    public function getCourierPayout(): string { return $this->courierPayout; }
    public function setCourierPayout(string|float|int $courierPayout): self { $this->courierPayout = number_format((float) $courierPayout, 2, '.', ''); $this->touch(); return $this; }

    public function getDeliveryMargin(): string
    {
        return number_format((float) $this->customerDeliveryFee - (float) $this->courierPayout, 2, '.', '');
    }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; $this->touch(); return $this; }

    public function getInternalNote(): ?string { return $this->internalNote; }
    public function setInternalNote(?string $internalNote): self { $this->internalNote = $internalNote; $this->touch(); return $this; }

    public function getPublicLabel(): ?string { return $this->publicLabel; }
    public function setPublicLabel(?string $publicLabel): self
    {
        $publicLabel = trim((string) $publicLabel);
        $this->publicLabel = $publicLabel !== '' ? mb_substr($publicLabel, 0, 120) : null;
        $this->touch();

        return $this;
    }

    public function getPublicDescription(): ?string { return $this->publicDescription; }
    public function setPublicDescription(?string $publicDescription): self
    {
        $publicDescription = trim((string) $publicDescription);
        $this->publicDescription = $publicDescription !== '' ? $publicDescription : null;
        $this->touch();

        return $this;
    }

    /** @return list<int> */
    public function getDeliveryWeekdays(): array
    {
        return is_array($this->deliveryWeekdays) ? array_values($this->deliveryWeekdays) : [];
    }

    /** @param list<int|string>|null $deliveryWeekdays */
    public function setDeliveryWeekdays(?array $deliveryWeekdays): self
    {
        if ($deliveryWeekdays === null) {
            $this->deliveryWeekdays = null;
            $this->touch();

            return $this;
        }

        $normalized = [];
        foreach ($deliveryWeekdays as $weekday) {
            $weekday = (int) $weekday;
            if ($weekday >= 1 && $weekday <= 7) {
                $normalized[$weekday] = $weekday;
            }
        }

        ksort($normalized);
        $this->deliveryWeekdays = array_values($normalized);
        $this->touch();

        return $this;
    }

    public function getCutoffTime(): ?\DateTimeImmutable { return $this->cutoffTime; }
    public function setCutoffTime(?\DateTimeInterface $cutoffTime): self
    {
        $this->cutoffTime = $cutoffTime instanceof \DateTimeImmutable
            ? $cutoffTime
            : ($cutoffTime instanceof \DateTimeInterface ? \DateTimeImmutable::createFromInterface($cutoffTime) : null);
        $this->touch();

        return $this;
    }

    public function getCutoffDaysBefore(): int { return $this->cutoffDaysBefore; }
    public function setCutoffDaysBefore(?int $cutoffDaysBefore): self
    {
        $this->cutoffDaysBefore = max(0, (int) ($cutoffDaysBefore ?? 1));
        $this->touch();

        return $this;
    }

    public function isDeliveryScheduleActive(): bool { return $this->isDeliveryScheduleActive; }
    public function setIsDeliveryScheduleActive(bool $isDeliveryScheduleActive): self
    {
        $this->isDeliveryScheduleActive = $isDeliveryScheduleActive;
        $this->touch();

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
