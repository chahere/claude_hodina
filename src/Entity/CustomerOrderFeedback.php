<?php

namespace App\Entity;

use App\Repository\CustomerOrderFeedbackRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CustomerOrderFeedbackRepository::class)]
#[ORM\Table(name: 'customer_order_feedback')]
#[ORM\UniqueConstraint(name: 'uniq_customer_order_feedback_target', columns: ['customer_order_id', 'target_key'])]
class CustomerOrderFeedback
{
    public const TARGET_ORDER = 'ORDER';
    public const TARGET_SELLER = 'SELLER';
    public const TARGET_COURIER = 'COURIER';
    public const TARGET_CANCELLATION = 'CANCELLATION';

    public const SOURCE_CLIENT_PORTAL = 'client_portal';

    public const REASON_MISTAKE = 'mistake';
    public const REASON_UNAVAILABLE = 'unavailable';
    public const REASON_DELIVERY_ADDRESS = 'delivery_address';
    public const REASON_PRICE_DELIVERY = 'price_delivery';
    public const REASON_OTHER = 'other';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: CustomerOrder::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CustomerOrder $customerOrder;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Customer $customer = null;

    #[ORM\ManyToOne(targetEntity: Seller::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Seller $seller = null;

    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Customer $courier = null;

    #[ORM\Column(length: 30)]
    private string $targetType = self::TARGET_ORDER;

    #[ORM\Column(length: 80)]
    private string $targetKey = 'order';

    #[ORM\Column(nullable: true)]
    private ?int $rating = null;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $reason = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column(length: 40)]
    private string $source = self::SOURCE_CLIENT_PORTAL;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function __toString(): string
    {
        $orderLabel = 'Commande';
        if (isset($this->customerOrder)) {
            $orderLabel = $this->customerOrder->getOrderReference() ?? ('Commande #' . $this->customerOrder->getId());
        }

        return sprintf('%s — %s', $this->targetType, $orderLabel);
    }

    public function getId(): ?int { return $this->id; }

    public function getCustomerOrder(): CustomerOrder { return $this->customerOrder; }
    public function setCustomerOrder(CustomerOrder $customerOrder): self { $this->customerOrder = $customerOrder; return $this; }

    public function getCustomer(): ?Customer { return $this->customer; }
    public function setCustomer(?Customer $customer): self { $this->customer = $customer; return $this; }

    public function getSeller(): ?Seller { return $this->seller; }
    public function setSeller(?Seller $seller): self { $this->seller = $seller; return $this; }

    public function getCourier(): ?Customer { return $this->courier; }
    public function setCourier(?Customer $courier): self { $this->courier = $courier; return $this; }

    public function getTargetType(): string { return $this->targetType; }
    public function setTargetType(string $targetType): self
    {
        $targetType = mb_strtoupper(trim($targetType));
        $this->targetType = in_array($targetType, self::getTargetTypes(), true) ? $targetType : self::TARGET_ORDER;

        return $this;
    }

    public function getTargetKey(): string { return $this->targetKey; }
    public function setTargetKey(string $targetKey): self
    {
        $targetKey = trim($targetKey);
        $this->targetKey = $targetKey !== '' ? mb_substr($targetKey, 0, 80) : 'order';

        return $this;
    }

    public function getRating(): ?int { return $this->rating; }
    public function setRating(?int $rating): self
    {
        $this->rating = $rating !== null ? max(1, min(5, $rating)) : null;

        return $this;
    }

    public function getReason(): ?string { return $this->reason; }
    public function setReason(?string $reason): self
    {
        $reason = $reason !== null ? trim($reason) : null;
        $this->reason = $reason !== '' ? mb_substr((string) $reason, 0, 80) : null;

        return $this;
    }

    public function getReasonLabel(): string
    {
        return self::getReasonLabels()[$this->reason ?? ''] ?? ($this->reason ?? 'Non renseigné');
    }

    public function getComment(): ?string { return $this->comment; }
    public function setComment(?string $comment): self
    {
        $comment = $comment !== null ? trim($comment) : null;
        $this->comment = $comment !== '' ? $comment : null;

        return $this;
    }

    public function getSource(): string { return $this->source; }
    public function setSource(string $source): self
    {
        $source = trim($source);
        $this->source = $source !== '' ? mb_substr($source, 0, 40) : self::SOURCE_CLIENT_PORTAL;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }
    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self { $this->updatedAt = $updatedAt; return $this; }

    /** @return list<string> */
    public static function getTargetTypes(): array
    {
        return [
            self::TARGET_ORDER,
            self::TARGET_SELLER,
            self::TARGET_COURIER,
            self::TARGET_CANCELLATION,
        ];
    }

    /** @return array<string, string> */
    public static function getReasonLabels(): array
    {
        return [
            self::REASON_MISTAKE => 'Je me suis trompé',
            self::REASON_UNAVAILABLE => 'Je ne suis plus disponible',
            self::REASON_DELIVERY_ADDRESS => 'Adresse ou livraison compliquée',
            self::REASON_PRICE_DELIVERY => 'Prix ou frais de livraison',
            self::REASON_OTHER => 'Autre raison',
        ];
    }
}
