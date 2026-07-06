<?php

namespace App\Entity;

use App\Repository\ProductDeliveryPointRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProductDeliveryPointRepository::class)]
#[ORM\Table(name: 'product_delivery_point')]
#[ORM\UniqueConstraint(name: 'UNIQ_PRODUCT_DELIVERY_POINT', columns: ['product_id', 'delivery_point_id'])]
#[ORM\Index(name: 'IDX_PRODUCT_DELIVERY_POINT_PRODUCT', columns: ['product_id'])]
#[ORM\Index(name: 'IDX_PRODUCT_DELIVERY_POINT_POINT', columns: ['delivery_point_id'])]
class ProductDeliveryPoint
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Product::class, inversedBy: 'productDeliveryPoints')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Product $product;

    #[ORM\ManyToOne(targetEntity: DeliveryPoint::class, inversedBy: 'productDeliveryPoints')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private DeliveryPoint $deliveryPoint;

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
        return sprintf('%s → %s', isset($this->product) ? (string) $this->product : 'Produit', isset($this->deliveryPoint) ? (string) $this->deliveryPoint : 'Point de remise');
    }

    public function getId(): ?int { return $this->id; }

    public function getProduct(): Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; $this->touch(); return $this; }

    public function getDeliveryPoint(): DeliveryPoint { return $this->deliveryPoint; }
    public function setDeliveryPoint(DeliveryPoint $deliveryPoint): self { $this->deliveryPoint = $deliveryPoint; $this->touch(); return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; $this->touch(); return $this; }

    public function getSortOrder(): int { return $this->sortOrder; }
    public function setSortOrder(int $sortOrder): self { $this->sortOrder = $sortOrder; $this->touch(); return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    public function getUpdatedAt(): ?\DateTimeImmutable { return $this->updatedAt; }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
