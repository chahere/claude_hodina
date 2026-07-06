<?php

namespace App\Entity;

use App\Repository\CourierPayoutRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourierPayoutRepository::class)]
#[ORM\Table(name: 'courier_payout')]
#[ORM\UniqueConstraint(name: 'uniq_courier_payout_period', columns: ['courier_id', 'period_start', 'period_end'])]
class CourierPayout
{
    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_VALIDATED = 'VALIDATED';
    public const STATUS_PAID = 'PAID';
    public const STATUS_CANCELED = 'CANCELED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Customer $courier = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $periodStart = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $periodEnd = null;

    #[ORM\Column(type: 'date_immutable')]
    private ?\DateTimeImmutable $paymentDueDate = null;

    #[ORM\Column(length: 40)]
    private string $status = self::STATUS_DRAFT;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $totalAmount = '0.00';

    #[ORM\Column]
    private int $ordersCount = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $validatedAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $paidAt = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $paymentMethod = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $paymentReference = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $adminNote = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, CourierPayoutLine> */
    #[ORM\OneToMany(mappedBy: 'courierPayout', targetEntity: CourierPayoutLine::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $lines;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->lines = new ArrayCollection();
    }

    public function __toString(): string
    {
        return sprintf(
            '%s — %s — %s €',
            $this->getCourierLabel(),
            $this->getPeriodLabel(),
            $this->formatMoney($this->totalAmount)
        );
    }

    public function getId(): ?int { return $this->id; }

    public function getCourier(): ?Customer { return $this->courier; }
    public function setCourier(?Customer $courier): self { $this->courier = $courier; return $this; }

    public function getPeriodStart(): ?\DateTimeImmutable { return $this->periodStart; }
    public function setPeriodStart(\DateTimeImmutable $periodStart): self { $this->periodStart = $periodStart; return $this; }

    public function getPeriodEnd(): ?\DateTimeImmutable { return $this->periodEnd; }
    public function setPeriodEnd(\DateTimeImmutable $periodEnd): self { $this->periodEnd = $periodEnd; return $this; }

    public function getPaymentDueDate(): ?\DateTimeImmutable { return $this->paymentDueDate; }
    public function setPaymentDueDate(\DateTimeImmutable $paymentDueDate): self { $this->paymentDueDate = $paymentDueDate; return $this; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $status): self
    {
        if (!in_array($status, self::getStatuses(), true)) {
            throw new \InvalidArgumentException(sprintf('Statut paiement livreur invalide : %s', $status));
        }

        $this->status = $status;
        $this->touch();

        return $this;
    }

    public function getStatusLabel(): string
    {
        return self::getStatusChoices()[$this->status] ?? $this->status;
    }

    /** @return list<string> */
    public static function getStatuses(): array
    {
        return [self::STATUS_DRAFT, self::STATUS_VALIDATED, self::STATUS_PAID, self::STATUS_CANCELED];
    }

    /** @return array<string, string> */
    public static function getStatusChoices(): array
    {
        return [
            self::STATUS_DRAFT => 'À contrôler',
            self::STATUS_VALIDATED => 'Validé, à payer',
            self::STATUS_PAID => 'Payé',
            self::STATUS_CANCELED => 'Annulé',
        ];
    }

    public function getTotalAmount(): string { return $this->totalAmount; }
    public function setTotalAmount(string|float|int $totalAmount): self
    {
        $this->totalAmount = number_format(max(0.0, round((float) str_replace(',', '.', (string) $totalAmount), 2)), 2, '.', '');
        $this->touch();

        return $this;
    }

    public function getOrdersCount(): int { return $this->ordersCount; }
    public function setOrdersCount(int $ordersCount): self { $this->ordersCount = max(0, $ordersCount); return $this; }

    public function getValidatedAt(): ?\DateTimeImmutable { return $this->validatedAt; }
    public function setValidatedAt(?\DateTimeImmutable $validatedAt): self { $this->validatedAt = $validatedAt; return $this; }

    public function getPaidAt(): ?\DateTimeImmutable { return $this->paidAt; }
    public function setPaidAt(?\DateTimeImmutable $paidAt): self { $this->paidAt = $paidAt; return $this; }

    public function getPaymentMethod(): ?string { return $this->paymentMethod; }
    public function setPaymentMethod(?string $paymentMethod): self
    {
        $paymentMethod = $paymentMethod !== null ? trim($paymentMethod) : null;
        $this->paymentMethod = $paymentMethod !== '' ? mb_substr((string) $paymentMethod, 0, 120) : null;
        return $this;
    }

    public function getPaymentReference(): ?string { return $this->paymentReference; }
    public function setPaymentReference(?string $paymentReference): self
    {
        $paymentReference = $paymentReference !== null ? trim($paymentReference) : null;
        $this->paymentReference = $paymentReference !== '' ? mb_substr((string) $paymentReference, 0, 180) : null;
        return $this;
    }

    public function getAdminNote(): ?string { return $this->adminNote; }
    public function setAdminNote(?string $adminNote): self
    {
        $adminNote = $adminNote !== null ? trim($adminNote) : null;
        $this->adminNote = $adminNote !== '' ? $adminNote : null;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): \DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    /** @return Collection<int, CourierPayoutLine> */
    public function getLines(): Collection { return $this->lines; }

    public function addLine(CourierPayoutLine $line): self
    {
        if (!$this->lines->contains($line)) {
            $this->lines->add($line);
            $line->setCourierPayout($this);
            $this->recalculateTotals();
        }

        return $this;
    }

    public function removeLine(CourierPayoutLine $line): self
    {
        if ($this->lines->removeElement($line) && $line->getCourierPayout() === $this) {
            $line->setCourierPayout(null);
            $this->recalculateTotals();
        }

        return $this;
    }

    public function recalculateTotals(): self
    {
        $total = 0.0;
        foreach ($this->lines as $line) {
            $total += (float) $line->getCourierPayoutAmount();
        }

        $this->totalAmount = number_format(round($total, 2), 2, '.', '');
        $this->ordersCount = $this->lines->count();
        $this->touch();

        return $this;
    }

    public function validate(): self
    {
        if ($this->status === self::STATUS_PAID) {
            throw new \DomainException('Un paiement déjà payé ne peut plus être revalidé.');
        }

        if ($this->status === self::STATUS_CANCELED) {
            throw new \DomainException('Un paiement annulé ne peut pas être validé.');
        }

        $this->status = self::STATUS_VALIDATED;
        $this->validatedAt = new \DateTimeImmutable();
        $this->touch();

        return $this;
    }

    public function markPaid(?string $paymentReference = null, ?string $paymentMethod = null): self
    {
        if ($this->status === self::STATUS_CANCELED) {
            throw new \DomainException('Un paiement annulé ne peut pas être marqué payé.');
        }

        $this->status = self::STATUS_PAID;
        $this->paidAt = new \DateTimeImmutable();
        $this->setPaymentReference($paymentReference ?? $this->paymentReference);
        $this->setPaymentMethod($paymentMethod ?? $this->paymentMethod);
        $this->touch();

        return $this;
    }

    public function cancel(): self
    {
        if ($this->status === self::STATUS_PAID) {
            throw new \DomainException('Un paiement déjà payé ne peut pas être annulé.');
        }

        $this->status = self::STATUS_CANCELED;
        $this->touch();

        return $this;
    }

    public function getCourierLabel(): string
    {
        if (!$this->courier instanceof Customer) {
            return 'Livreur non renseigné';
        }

        $name = trim(sprintf('%s %s', $this->courier->getFirstName(), (string) $this->courier->getLastName()));

        return $name !== '' ? $name : 'Livreur #' . $this->courier->getId();
    }

    public function getPeriodLabel(): string
    {
        if (!$this->periodStart instanceof \DateTimeImmutable || !$this->periodEnd instanceof \DateTimeImmutable) {
            return 'Période non définie';
        }

        return sprintf('%s → %s', $this->periodStart->format('d/m/Y'), $this->periodEnd->format('d/m/Y'));
    }

    public function touch(): self
    {
        $this->updatedAt = new \DateTimeImmutable();

        return $this;
    }

    private function formatMoney(string|float|int|null $amount): string
    {
        return number_format((float) $amount, 2, ',', ' ');
    }
}
