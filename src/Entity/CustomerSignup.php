<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
class CustomerSignup
{
    #[ORM\Id, ORM\GeneratedValue, ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 80)]
    private string $firstName = '';

    #[ORM\Column(length: 80)]
    private string $lastName = '';

    #[ORM\Column(length: 180)]
    private string $email = '';

    #[ORM\Column(length: 30)]
    private string $phone = '';

    #[ORM\Column(length: 255)]
    private string $address = '';

    #[ORM\Column(length: 8)]
    private string $zone = ''; // PT / GT

    #[ORM\Column(type: 'json')]
    private array $cartSnapshot = [];

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(length: 30)]
    private string $status = 'new'; // new / processed

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int { return $this->id; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $v): self { $this->firstName = $v; return $this; }

    public function getLastName(): string { return $this->lastName; }
    public function setLastName(string $v): self { $this->lastName = $v; return $this; }

    public function getEmail(): string { return $this->email; }
    public function setEmail(string $v): self { $this->email = $v; return $this; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $v): self { $this->phone = $v; return $this; }

    public function getAddress(): string { return $this->address; }
    public function setAddress(string $v): self { $this->address = $v; return $this; }

    public function getZone(): string { return $this->zone; }
    public function setZone(string $v): self { $this->zone = $v; return $this; }

    public function getCartSnapshot(): array { return $this->cartSnapshot; }
    public function setCartSnapshot(array $v): self { $this->cartSnapshot = $v; return $this; }

    public function getCreatedAt(): \DateTimeImmutable { return $this->createdAt; }

    public function getStatus(): string { return $this->status; }
    public function setStatus(string $v): self { $this->status = $v; return $this; }
}
