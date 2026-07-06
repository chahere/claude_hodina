<?php

namespace App\Entity;

use App\Repository\CourierPayoutLineRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CourierPayoutLineRepository::class)]
#[ORM\Table(name: 'courier_payout_line')]
#[ORM\UniqueConstraint(name: 'uniq_courier_payout_line_order', columns: ['customer_order_id'])]
class CourierPayoutLine
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CourierPayout::class, inversedBy: 'lines')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?CourierPayout $courierPayout = null;

    #[ORM\ManyToOne(targetEntity: CustomerOrder::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'RESTRICT')]
    private ?CustomerOrder $customerOrder = null;

    #[ORM\Column(length: 80)]
    private string $orderReference = '';

    #[ORM\Column]
    private \DateTimeImmutable $deliveredAt;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $customerCommune = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $courierPayoutAmount = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $deliveryFeeCustomer = '0.00';

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $snapshot = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->deliveredAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        return sprintf('%s — %s €', $this->orderReference ?: 'Commande', number_format((float) $this->courierPayoutAmount, 2, ',', ' '));
    }

    public function getId(): ?int { return $this->id; }

    public function getCourierPayout(): ?CourierPayout { return $this->courierPayout; }
    public function setCourierPayout(?CourierPayout $courierPayout): self { $this->courierPayout = $courierPayout; return $this; }

    public function getCustomerOrder(): ?CustomerOrder { return $this->customerOrder; }
    public function setCustomerOrder(CustomerOrder $customerOrder): self { $this->customerOrder = $customerOrder; return $this; }

    public function getOrderReference(): string { return $this->orderReference; }
    public function setOrderReference(?string $orderReference): self
    {
        $orderReference = trim((string) $orderReference);
        $this->orderReference = $orderReference !== '' ? mb_substr($orderReference, 0, 80) : 'Commande';
        return $this;
    }

    public function getDeliveredAt(): \DateTimeImmutable { return $this->deliveredAt; }
    public function setDeliveredAt(\DateTimeImmutable $deliveredAt): self { $this->deliveredAt = $deliveredAt; return $this; }

    public function getCustomerCommune(): ?string { return $this->customerCommune; }
    public function setCustomerCommune(?string $customerCommune): self
    {
        $customerCommune = $customerCommune !== null ? trim($customerCommune) : null;
        $this->customerCommune = $customerCommune !== '' ? mb_substr((string) $customerCommune, 0, 120) : null;
        return $this;
    }

    public function getCourierPayoutAmount(): string { return $this->courierPayoutAmount; }
    public function setCourierPayoutAmount(string|float|int $courierPayoutAmount): self
    {
        $this->courierPayoutAmount = number_format(max(0.0, round((float) str_replace(',', '.', (string) $courierPayoutAmount), 2)), 2, '.', '');
        return $this;
    }

    public function getDeliveryFeeCustomer(): string { return $this->deliveryFeeCustomer; }
    public function setDeliveryFeeCustomer(string|float|int $deliveryFeeCustomer): self
    {
        $this->deliveryFeeCustomer = number_format(max(0.0, round((float) str_replace(',', '.', (string) $deliveryFeeCustomer), 2)), 2, '.', '');
        return $this;
    }

    /** @return array<string, mixed>|null */
    public function getSnapshot(): ?array { return $this->snapshot; }

    /** @param array<string, mixed>|null $snapshot */
    public function setSnapshot(?array $snapshot): self { $this->snapshot = $snapshot; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
}
