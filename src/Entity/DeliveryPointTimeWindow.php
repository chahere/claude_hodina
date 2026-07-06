<?php

namespace App\Entity;

use App\Repository\DeliveryPointTimeWindowRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: DeliveryPointTimeWindowRepository::class)]
#[ORM\Table(name: 'delivery_point_time_window')]
#[ORM\Index(name: 'IDX_DELIVERY_POINT_TIME_WINDOW_POINT', columns: ['delivery_point_id'])]
#[ORM\Index(name: 'IDX_DELIVERY_POINT_TIME_WINDOW_DAY_ACTIVE', columns: ['weekday', 'is_active'])]
class DeliveryPointTimeWindow
{
    public const WEEKDAY_MONDAY = 1;
    public const WEEKDAY_TUESDAY = 2;
    public const WEEKDAY_WEDNESDAY = 3;
    public const WEEKDAY_THURSDAY = 4;
    public const WEEKDAY_FRIDAY = 5;
    public const WEEKDAY_SATURDAY = 6;
    public const WEEKDAY_SUNDAY = 7;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: DeliveryPoint::class, inversedBy: 'timeWindows')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DeliveryPoint $deliveryPoint;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $label = null;

    /** Null = tous les jours. */
    #[ORM\Column(nullable: true)]
    private ?int $weekday = null;

    #[ORM\Column(type: 'time_immutable')]
    private \DateTimeImmutable $startTime;

    #[ORM\Column(type: 'time_immutable')]
    private \DateTimeImmutable $endTime;

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
        $this->startTime = new \DateTimeImmutable('08:00');
        $this->endTime = new \DateTimeImmutable('12:00');
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s — %s %s-%s',
            isset($this->deliveryPoint) ? (string) $this->deliveryPoint : 'Point',
            $this->getWeekdayLabel(),
            $this->startTime->format('H:i'),
            $this->endTime->format('H:i')
        );
    }

    public function getId(): ?int { return $this->id; }

    public function getDeliveryPoint(): DeliveryPoint { return $this->deliveryPoint; }
    public function setDeliveryPoint(DeliveryPoint $deliveryPoint): self { $this->deliveryPoint = $deliveryPoint; $this->touch(); return $this; }

    public function getLabel(): ?string { return $this->label; }
    public function setLabel(?string $label): self
    {
        $label = $label !== null ? trim($label) : null;
        $this->label = $label !== '' ? $label : null;
        $this->touch();

        return $this;
    }

    public function getWeekday(): ?int { return $this->weekday; }
    public function setWeekday(?int $weekday): self
    {
        if ($weekday === null || $weekday <= 0) {
            $this->weekday = null;
        } else {
            $this->weekday = array_key_exists($weekday, self::getWeekdayLabels()) ? $weekday : null;
        }

        $this->touch();

        return $this;
    }

    public function getWeekdayLabel(): string
    {
        return $this->weekday !== null ? self::getWeekdayLabels()[$this->weekday] : 'Tous les jours';
    }

    public function getStartTime(): \DateTimeImmutable { return $this->startTime; }
    public function setStartTime(\DateTimeImmutable $startTime): self { $this->startTime = $startTime; $this->touch(); return $this; }

    public function getEndTime(): \DateTimeImmutable { return $this->endTime; }
    public function setEndTime(\DateTimeImmutable $endTime): self { $this->endTime = $endTime; $this->touch(); return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; $this->touch(); return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; $this->touch(); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    /** @return array<int, string> */
    public static function getWeekdayLabels(): array
    {
        return [
            self::WEEKDAY_MONDAY => 'Lundi',
            self::WEEKDAY_TUESDAY => 'Mardi',
            self::WEEKDAY_WEDNESDAY => 'Mercredi',
            self::WEEKDAY_THURSDAY => 'Jeudi',
            self::WEEKDAY_FRIDAY => 'Vendredi',
            self::WEEKDAY_SATURDAY => 'Samedi',
            self::WEEKDAY_SUNDAY => 'Dimanche',
        ];
    }

    /** @return array<string, int> */
    public static function getWeekdayChoices(): array
    {
        return array_merge(['Tous les jours' => 0], array_flip(self::getWeekdayLabels()));
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
