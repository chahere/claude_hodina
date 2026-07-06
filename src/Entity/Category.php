<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'category')]
class Category
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 120)]
    private string $name;

    #[ORM\Column(length: 255)]
    private string $slug;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column]
    private int $displayOrder = 0;

    #[ORM\Column]
    private bool $isFeatured = false;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $publicDescription = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
    public function __toString(): string
    {
        // Affichage dans les listes déroulantes (EasyAdmin)
        return (string) ($this->getName() ?: 'Category');
    }


    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = $name; return $this; }

    public function getSlug(): string { return $this->slug; }
    public function setSlug(string $slug): self { $this->slug = $slug; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getDisplayOrder(): int { return $this->displayOrder; }
    public function setDisplayOrder(int $displayOrder): self { $this->displayOrder = max(0, $displayOrder); return $this; }

    public function isFeatured(): bool { return $this->isFeatured; }
    public function setIsFeatured(bool $isFeatured): self { $this->isFeatured = $isFeatured; return $this; }

    public function getPublicDescription(): ?string { return $this->publicDescription; }
    public function setPublicDescription(?string $publicDescription): self
    {
        $publicDescription = $publicDescription !== null ? trim($publicDescription) : null;
        $this->publicDescription = $publicDescription !== '' ? $publicDescription : null;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function updateSlug(): void
    {
        if (!empty($this->name)) {
            $this->slug = self::slugify($this->name);
        }
    }

    private static function slugify(string $text): string
    {
        $text = trim($text);

        // 1) minuscules
        $text = mb_strtolower($text, 'UTF-8');

        // 2) remplace les apostrophes/quotes par espace (évite les "l-egumes")
        $text = str_replace(["'", "’", "`", "´", "\""], ' ', $text);

        // 3) translit accents (é -> e)
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        if ($converted !== false) {
            $text = $converted;
        }

        // 4) garde uniquement a-z0-9 et espaces/tirets
        $text = preg_replace('~[^a-z0-9\s-]+~', '', $text);

        // 5) espaces multiples -> un espace
        $text = preg_replace('~[\s-]+~', ' ', $text);

        // 6) espace -> tiret
        $text = trim((string) $text);
        $text = str_replace(' ', '-', $text);

        return $text !== '' ? $text : 'n-a';
    }

}
