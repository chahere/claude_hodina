<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
#[ORM\Table(name: 'customer')]
#[ORM\UniqueConstraint(name: 'UNIQ_CUSTOMER_EMAIL', columns: ['email'])]
class Customer implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 100)]
    private string $firstName;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $lastName = null;

    #[ORM\Column(length: 30)]
    private string $phone;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private array $roles = [];

    #[ORM\Column(length: 255)]
    private ?string $password = null;

    /**
     * Mot de passe temporaire saisi uniquement dans le backoffice.
     * Non persisté en base : il sert à générer le hash stocké dans password.
     */
    private ?string $plainPassword = null;

    #[ORM\Column]
    private bool $isVerified = false;

    #[ORM\Column(length: 128, nullable: true)]
    private ?string $resetPasswordToken = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $resetPasswordTokenExpiresAt = null;

    /**
     * Plafond maximum versé à ce livreur par commande.
     *
     * Null ou 0 = utiliser le plafond global Hodina.
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $courierPayoutCap = null;


    #[Assert\Valid]
    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Address $billingAddress = null;

    #[Assert\Valid]
    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Address $deliveryAddress = null;

    #[Assert\Valid]
    #[ORM\OneToMany(mappedBy: 'customer', targetEntity: Address::class, cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $addresses;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->addresses = new ArrayCollection();
    }
	public function getUserIdentifier(): string
	{
		return (string) $this->email;
	}
	public function getUsername(): string
	{
		return (string) $this->email;
	}
    public function getId(): ?int { return $this->id; }

    public function getFirstName(): string { return $this->firstName; }
    public function setFirstName(string $firstName): self { $this->firstName = $firstName; return $this; }

    public function getLastName(): ?string { return $this->lastName; }
    public function setLastName(?string $lastName): self { $this->lastName = $lastName; return $this; }

    public function getPhone(): string { return $this->phone; }
    public function setPhone(string $phone): self { $this->phone = $phone; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }

    public function getBillingAddress(): ?Address
    {
        return $this->billingAddress;
    }

    public function setBillingAddress(?Address $billingAddress): self
    {
        $this->billingAddress = $billingAddress;
        return $this;
    }

    public function getDeliveryAddress(): ?Address
    {
        return $this->deliveryAddress;
    }

    public function setDeliveryAddress(?Address $deliveryAddress): self
    {
        $this->deliveryAddress = $deliveryAddress;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }

	public function getRoles(): array
	{
		$roles = $this->roles ?? [];
		$roles[] = 'ROLE_USER';

		return array_unique($roles);
	}

    public function setRoles(array $roles): self
	{
		$this->roles = $roles;
		return $this;
	}

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;

        return $this;
    }


    public function getPlainPassword(): ?string
    {
        return $this->plainPassword;
    }

    public function setPlainPassword(?string $plainPassword): self
    {
        $this->plainPassword = $plainPassword;
        return $this;
    }

    public function getResetPasswordToken(): ?string
    {
        return $this->resetPasswordToken;
    }

    public function setResetPasswordToken(?string $resetPasswordToken): self
    {
        $this->resetPasswordToken = $resetPasswordToken;
        return $this;
    }

    public function getResetPasswordTokenExpiresAt(): ?\DateTimeImmutable
    {
        return $this->resetPasswordTokenExpiresAt;
    }

    public function setResetPasswordTokenExpiresAt(?\DateTimeImmutable $resetPasswordTokenExpiresAt): self
    {
        $this->resetPasswordTokenExpiresAt = $resetPasswordTokenExpiresAt;
        return $this;
    }

    public function getCourierPayoutCap(): ?string
    {
        return $this->courierPayoutCap;
    }

    public function setCourierPayoutCap(null|string|float|int $courierPayoutCap): self
    {
        if ($courierPayoutCap === null || trim((string) $courierPayoutCap) === '') {
            $this->courierPayoutCap = null;
            return $this;
        }

        $amount = max(0.0, round((float) str_replace(',', '.', (string) $courierPayoutCap), 2));
        $this->courierPayoutCap = number_format($amount, 2, '.', '');

        return $this;
    }

    public function getCourierPayoutCapAsFloat(): ?float
    {
        if ($this->courierPayoutCap === null || trim($this->courierPayoutCap) === '') {
            return null;
        }

        $amount = (float) $this->courierPayoutCap;

        return $amount > 0.0 ? $amount : null;
    }

    public function __toString(): string
    {
        $name = trim(($this->getFirstName() ?? '') . ' ' . ($this->getLastName() ?? ''));

        if ($name !== '') {
            return $name . ' — ' . $this->getEmail();
        }

        return $this->getEmail() ?? 'Client #' . $this->getId();
    }
	public function eraseCredentials(): void
	{
		// Si tu stockes temporairement un plainPassword, tu le vides ici
	}

    public function isVerified(): bool
    {
        return $this->isVerified;
    }

    public function setIsVerified(bool $isVerified): static
    {
        $this->isVerified = $isVerified;

        return $this;
    }
    /**
     * @return Collection<int, Address>
     */
    public function getAddresses(): Collection
    {
        return $this->addresses;
    }

    public function addAddress(Address $address): self
    {
        if (!$this->addresses->contains($address)) {
            $this->addresses[] = $address;
            $address->setCustomer($this);
        }

        return $this;
    }

    public function removeAddress(Address $address): self
    {
        if ($this->addresses->removeElement($address)) {
            if ($this->billingAddress === $address) {
                $this->billingAddress = null;
            }

            if ($this->deliveryAddress === $address) {
                $this->deliveryAddress = null;
            }
        }

        return $this;
    }
}
