<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'order_item')]
class OrderItem
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'items')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private CustomerOrder $customerOrder;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Product $product;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private Seller $seller;

    #[ORM\Column]
    private int $quantity = 1;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $unitPrice = '0.00';

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $producerUnitPrice = null;

    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $appliedMarginRate = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $hodinaMarginAmount = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2)]
    private string $lineTotal = '0.00';

    public function __toString(): string
    {
        return sprintf(
            '%s — %d x %s € = %s €',
            $this->product?->getName() ?? 'Produit',
            $this->quantity,
            $this->unitPrice,
            $this->lineTotal
        );
    }

    public function getId(): ?int { return $this->id; }

    public function getCustomerOrder(): CustomerOrder { return $this->customerOrder; }
    public function setCustomerOrder(CustomerOrder $customerOrder): self { $this->customerOrder = $customerOrder; return $this; }

    public function getProduct(): Product { return $this->product; }
    public function setProduct(Product $product): self { $this->product = $product; return $this; }

    public function getSeller(): Seller { return $this->seller; }
    public function setSeller(Seller $seller): self { $this->seller = $seller; return $this; }

    public function getQuantity(): int { return $this->quantity; }
    public function setQuantity(int $quantity): self { $this->quantity = max(1, $quantity); $this->recalc(); return $this; }

    public function getUnitPrice(): string { return $this->unitPrice; }
    public function setUnitPrice(string $unitPrice): self { $this->unitPrice = number_format((float) $unitPrice, 2, '.', ''); $this->recalc(); return $this; }

    public function getProducerUnitPrice(): ?string { return $this->producerUnitPrice; }
    public function setProducerUnitPrice(?string $producerUnitPrice): self { $this->producerUnitPrice = $producerUnitPrice !== null ? number_format((float) $producerUnitPrice, 2, '.', '') : null; return $this; }

    public function getAppliedMarginRate(): ?string { return $this->appliedMarginRate; }
    public function setAppliedMarginRate(?string $appliedMarginRate): self { $this->appliedMarginRate = $appliedMarginRate !== null ? number_format((float) $appliedMarginRate, 2, '.', '') : null; return $this; }

    public function getHodinaMarginAmount(): ?string { return $this->hodinaMarginAmount; }
    public function setHodinaMarginAmount(?string $hodinaMarginAmount): self { $this->hodinaMarginAmount = $hodinaMarginAmount !== null ? number_format((float) $hodinaMarginAmount, 2, '.', '') : null; return $this; }

    public function getLineTotal(): string { return $this->lineTotal; }

    private function recalc(): void
    {
        $this->lineTotal = number_format(((float) $this->unitPrice) * $this->quantity, 2, '.', '');
    }
}
