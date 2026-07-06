<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'seller')]
class Seller
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 150)]
    private string $name = '';

    /**
     * Nom commercial optionnel affiche quand le vendeur a une structure.
     * Exemple : Marche Petite-Terre, Boutique Combo, Ferme Abdallah.
     */
    #[ORM\Column(length: 150, nullable: true)]
    private ?string $businessName = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $contactName = null;

    #[ORM\Column(length: 30, nullable: true)]
    private ?string $phone = null;

    #[ORM\Column(length: 180, nullable: true)]
    private ?string $email = null;

    /**
     * Code de validation fixe que le vendeur communique au livreur au moment de la collecte.
     * Si ce champ est vide, Hodina génère un code ponctuel par commande et l'envoie au vendeur.
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $collectionValidationCode = null;

    /**
     * Compte utilisateur/client rattache au vendeur quand le vendeur utilise aussi le portail Hodina.
     * Permet de reutiliser ses adresses existantes sans dupliquer adresse/GPS dans seller.
     */
    #[ORM\ManyToOne(targetEntity: Customer::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Customer $customerAccount = null;

    /**
     * Adresse de collecte/retrait du vendeur.
     * Reutilise l'entite Address existante qui porte deja adresse, instructions et GPS.
     *
     * Important : cette adresse sert uniquement au terrain livreur.
     * Le calcul des trajets et des coûts reste base sur deliveryCommune.
     */
    #[ORM\ManyToOne(targetEntity: Address::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?Address $pickupAddress = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $commune = null;

    /** Commune logistique administrable utilisee pour J5F/J5G. */
    #[ORM\ManyToOne(targetEntity: DeliveryCommune::class)]
    #[ORM\JoinColumn(nullable: true, onDelete: 'SET NULL')]
    private ?DeliveryCommune $deliveryCommune = null;

    /** Taux de marge vendeur en pourcentage. Exemple : 15.00 = 15 %. */
    #[ORM\Column(type: 'decimal', precision: 5, scale: 2, nullable: true)]
    private ?string $marginRate = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private DeliveryZone $deliveryZone;

    #[ORM\Column]
    private bool $isActive = true;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastProductUpdateAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $lastStockUpdateAt = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;


    /** Champs de formulaire non persistés pour créer/éditer l'identité client du vendeur. */
    private ?string $sellerFirstName = null;
    private ?string $sellerLastName = null;

    /** Champs de formulaire non persistés pour créer/éditer directement le point de retrait vendeur. */
    private ?string $pickupAddressLine1 = null;
    private ?string $pickupAddressLine2 = null;
    private ?DeliveryCommune $pickupDeliveryCommune = null;
    private ?string $pickupAddressPostalCode = null;
    private ?string $pickupAddressCommune = null;
    private ?string $pickupAddressNotes = null;
    private ?string $pickupAddressCourierNotes = null;
    private ?string $pickupAddressGpsLatitude = null;
    private ?string $pickupAddressGpsLongitude = null;
    private ?string $pickupAddressGpsAccuracyMeters = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }
    public function __toString(): string
    {
        $name = $this->getCourierDisplayName();

        try {
            $zone = $this->getDeliveryZone();
        } catch (\Throwable) {
            $zone = null;
        }

        return $zone ? $name.' ('.$zone.')' : $name;
    }



    public function getId(): ?int { return $this->id; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): self { $this->name = trim($name); return $this; }

    public function getBusinessName(): ?string { return $this->businessName; }
    public function setBusinessName(?string $businessName): self { $this->businessName = $this->normalizeNullableString($businessName); return $this; }

    /** Affichage terrain : structure si renseignée, sinon prénom + nom du client vendeur. */
    public function getCourierDisplayName(): string
    {
        $businessName = trim((string) $this->businessName);
        if ($businessName !== '') {
            return $businessName;
        }

        $customerName = trim(sprintf(
            '%s %s',
            (string) ($this->customerAccount?->getFirstName() ?? $this->sellerFirstName ?? ''),
            (string) ($this->customerAccount?->getLastName() ?? $this->sellerLastName ?? '')
        ));

        return $customerName !== '' ? $customerName : (trim($this->name) !== '' ? trim($this->name) : 'Vendeur');
    }

    /** Affichage catalogue : structure si renseignée, sinon nom de famille du vendeur. */
    public function getPublicDisplayName(): string
    {
        $businessName = trim((string) $this->businessName);
        if ($businessName !== '') {
            return $businessName;
        }

        $lastName = trim((string) ($this->customerAccount?->getLastName() ?? $this->sellerLastName ?? ''));

        return $lastName !== '' ? $lastName : (trim($this->name) !== '' ? trim($this->name) : 'Vendeur');
    }

    public function getSellerFirstName(): ?string
    {
        return $this->sellerFirstName ?? $this->customerAccount?->getFirstName();
    }

    public function setSellerFirstName(?string $sellerFirstName): self
    {
        $this->sellerFirstName = $this->normalizeNullableString($sellerFirstName);
        return $this;
    }

    public function getSellerLastName(): ?string
    {
        return $this->sellerLastName ?? $this->customerAccount?->getLastName();
    }

    public function setSellerLastName(?string $sellerLastName): self
    {
        $this->sellerLastName = $this->normalizeNullableString($sellerLastName);
        return $this;
    }

    public function getContactName(): ?string { return $this->contactName; }
    public function setContactName(?string $contactName): self { $this->contactName = $contactName; return $this; }

    public function getPhone(): ?string { return $this->phone; }
    public function setPhone(?string $phone): self { $this->phone = $phone; return $this; }

    public function getEmail(): ?string { return $this->email; }
    public function setEmail(?string $email): self { $this->email = $email; return $this; }

    public function getCollectionValidationCode(): ?string { return $this->collectionValidationCode; }
    public function setCollectionValidationCode(?string $collectionValidationCode): self
    {
        $code = $collectionValidationCode !== null ? trim((string) $collectionValidationCode) : '';
        $code = preg_replace('/\s+/', '', $code) ?? $code;
        $code = mb_strtoupper($code);

        $this->collectionValidationCode = $code !== '' ? mb_substr($code, 0, 20) : null;

        return $this;
    }

    public function hasCollectionValidationCode(): bool
    {
        return trim((string) $this->collectionValidationCode) !== '';
    }

    public function getCustomerAccount(): ?Customer { return $this->customerAccount; }
    public function setCustomerAccount(?Customer $customerAccount): self { $this->customerAccount = $customerAccount; return $this; }

    public function getPickupAddress(): ?Address { return $this->pickupAddress; }
    public function setPickupAddress(?Address $pickupAddress): self { $this->pickupAddress = $pickupAddress; return $this; }

    /**
     * Adresse terrain effective pour aider le livreur a collecter chez le vendeur.
     * Ne pas utiliser pour calculer les frais de livraison ou le trajet logistique.
     */
    public function getEffectivePickupAddress(): ?Address
    {
        if ($this->pickupAddress instanceof Address) {
            return $this->pickupAddress;
        }

        return $this->customerAccount?->getDeliveryAddress();
    }



    public function getPickupAddressLine1(): ?string
    {
        return $this->pickupAddress?->getLine1() ?: $this->pickupAddressLine1;
    }

    public function setPickupAddressLine1(?string $pickupAddressLine1): self
    {
        $this->pickupAddressLine1 = $this->normalizeNullableString($pickupAddressLine1);

        if ($this->pickupAddress instanceof Address && $this->pickupAddressLine1 !== null) {
            $this->pickupAddress->setLine1($this->pickupAddressLine1);
        }

        return $this;
    }

    public function getPickupAddressLine2(): ?string
    {
        return $this->pickupAddress?->getLine2() ?: $this->pickupAddressLine2;
    }

    public function setPickupAddressLine2(?string $pickupAddressLine2): self
    {
        $this->pickupAddressLine2 = $this->normalizeNullableString($pickupAddressLine2);

        if ($this->pickupAddress instanceof Address) {
            $this->pickupAddress->setLine2($this->pickupAddressLine2);
        }

        return $this;
    }

    /**
     * Commune de retrait sélectionnée dans le formulaire vendeur.
     * Non persistée : elle sert à remplir Address.commune/postalCode puis Seller.deliveryCommune.
     */
    public function getPickupDeliveryCommune(): ?DeliveryCommune
    {
        if ($this->pickupDeliveryCommune instanceof DeliveryCommune) {
            return $this->pickupDeliveryCommune;
        }

        if ($this->deliveryCommune instanceof DeliveryCommune) {
            return $this->deliveryCommune;
        }

        return null;
    }

    public function setPickupDeliveryCommune(?DeliveryCommune $pickupDeliveryCommune): self
    {
        $this->pickupDeliveryCommune = $pickupDeliveryCommune;

        if ($pickupDeliveryCommune instanceof DeliveryCommune) {
            $this->pickupAddressPostalCode = $pickupDeliveryCommune->getPostalCode();
            $this->pickupAddressCommune = $pickupDeliveryCommune->getName();

            if ($this->pickupAddress instanceof Address) {
                $this->pickupAddress
                    ->setPostalCode((string) ($pickupDeliveryCommune->getPostalCode() ?: ''))
                    ->setCommune($pickupDeliveryCommune->getName());
            }
        }

        return $this;
    }

    public function getPickupAddressPostalCode(): ?string
    {
        return $this->pickupAddress?->getPostalCode() ?: $this->pickupAddressPostalCode;
    }

    public function setPickupAddressPostalCode(?string $pickupAddressPostalCode): self
    {
        $this->pickupAddressPostalCode = $this->normalizeNullableString($pickupAddressPostalCode);

        if ($this->pickupAddress instanceof Address && $this->pickupAddressPostalCode !== null) {
            $this->pickupAddress->setPostalCode($this->pickupAddressPostalCode);
        }

        return $this;
    }

    public function getPickupAddressCommune(): ?string
    {
        return $this->pickupAddress?->getCommune() ?: $this->pickupAddressCommune;
    }

    public function setPickupAddressCommune(?string $pickupAddressCommune): self
    {
        $this->pickupAddressCommune = $this->normalizeNullableString($pickupAddressCommune);

        if ($this->pickupAddress instanceof Address && $this->pickupAddressCommune !== null) {
            $this->pickupAddress->setCommune($this->pickupAddressCommune);
        }

        return $this;
    }

    public function getPickupAddressNotes(): ?string
    {
        return $this->pickupAddress?->getNotes() ?: $this->pickupAddressNotes;
    }

    public function setPickupAddressNotes(?string $pickupAddressNotes): self
    {
        $this->pickupAddressNotes = $this->normalizeNullableString($pickupAddressNotes);

        if ($this->pickupAddress instanceof Address) {
            $this->pickupAddress->setNotes($this->pickupAddressNotes);
        }

        return $this;
    }

    public function getPickupAddressCourierNotes(): ?string
    {
        return $this->pickupAddress?->getCourierNotes() ?: $this->pickupAddressCourierNotes;
    }

    public function setPickupAddressCourierNotes(?string $pickupAddressCourierNotes): self
    {
        $this->pickupAddressCourierNotes = $this->normalizeNullableString($pickupAddressCourierNotes);

        if ($this->pickupAddress instanceof Address) {
            $this->pickupAddress->setCourierNotes($this->pickupAddressCourierNotes);
        }

        return $this;
    }

    public function getPickupAddressGpsLatitude(): ?string
    {
        return $this->pickupAddress?->getGpsLatitude() ?: $this->pickupAddressGpsLatitude;
    }

    public function setPickupAddressGpsLatitude(?string $pickupAddressGpsLatitude): self
    {
        $this->pickupAddressGpsLatitude = $this->normalizeNullableString($pickupAddressGpsLatitude);

        if ($this->pickupAddress instanceof Address) {
            $this->pickupAddress->setGpsLatitude($this->pickupAddressGpsLatitude);
        }

        return $this;
    }

    public function getPickupAddressGpsLongitude(): ?string
    {
        return $this->pickupAddress?->getGpsLongitude() ?: $this->pickupAddressGpsLongitude;
    }

    public function setPickupAddressGpsLongitude(?string $pickupAddressGpsLongitude): self
    {
        $this->pickupAddressGpsLongitude = $this->normalizeNullableString($pickupAddressGpsLongitude);

        if ($this->pickupAddress instanceof Address) {
            $this->pickupAddress->setGpsLongitude($this->pickupAddressGpsLongitude);
        }

        return $this;
    }

    public function getPickupAddressGpsAccuracyMeters(): ?string
    {
        return $this->pickupAddress?->getGpsAccuracyMeters() ?: $this->pickupAddressGpsAccuracyMeters;
    }

    public function setPickupAddressGpsAccuracyMeters(?string $pickupAddressGpsAccuracyMeters): self
    {
        $this->pickupAddressGpsAccuracyMeters = $this->normalizeNullableString($pickupAddressGpsAccuracyMeters);

        if ($this->pickupAddress instanceof Address) {
            $this->pickupAddress->setGpsAccuracyMeters($this->pickupAddressGpsAccuracyMeters);
        }

        return $this;
    }

    public function hasPickupAddressFormData(): bool
    {
        return trim((string) $this->getPickupAddressLine1()) !== ''
            || $this->getPickupDeliveryCommune() instanceof DeliveryCommune
            || trim((string) $this->getPickupAddressPostalCode()) !== ''
            || trim((string) $this->getPickupAddressCommune()) !== '';
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $value = $value !== null ? trim($value) : '';

        return $value !== '' ? $value : null;
    }

    public function getCommune(): ?string { return $this->commune; }
    public function setCommune(?string $commune): self { $this->commune = $commune; return $this; }

    public function getDeliveryCommune(): ?DeliveryCommune { return $this->deliveryCommune; }
    public function setDeliveryCommune(?DeliveryCommune $deliveryCommune): self { $this->deliveryCommune = $deliveryCommune; return $this; }

    public function getMarginRate(): ?string { return $this->marginRate; }
    public function setMarginRate(?string $marginRate): self { $this->marginRate = $marginRate !== null && trim((string) $marginRate) !== '' ? number_format((float) $marginRate, 2, '.', '') : null; return $this; }

    public function getDeliveryZone(): DeliveryZone { return $this->deliveryZone; }
    public function setDeliveryZone(DeliveryZone $deliveryZone): self { $this->deliveryZone = $deliveryZone; return $this; }

    public function isActive(): bool { return $this->isActive; }
    public function setIsActive(bool $isActive): self { $this->isActive = $isActive; return $this; }

    public function getLastProductUpdateAt(): ?\DateTimeImmutable { return $this->lastProductUpdateAt; }
    public function setLastProductUpdateAt(?\DateTimeImmutable $dt): self { $this->lastProductUpdateAt = $dt; return $this; }

    public function getLastStockUpdateAt(): ?\DateTimeImmutable { return $this->lastStockUpdateAt; }
    public function setLastStockUpdateAt(?\DateTimeImmutable $dt): self { $this->lastStockUpdateAt = $dt; return $this; }

    public function getCreatedAt(): ?\DateTimeImmutable { return $this->createdAt; }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self { $this->createdAt = $createdAt; return $this; }
 
}
