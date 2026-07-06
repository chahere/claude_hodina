<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Address;
use App\Entity\Customer;
use App\Entity\DeliveryCommune;
use App\Entity\DeliveryZone;
use App\Entity\Seller;

/**
 * Centralise la règle J5M-C2-bis :
 * - l'adresse de retrait vendeur guide le livreur ;
 * - la commune logistique vendeur est déduite côté serveur depuis cette adresse ;
 * - DeliveryLogisticsService continue ensuite d'utiliser Seller::deliveryCommune.
 */
final class SellerPickupLogisticsSynchronizer
{
    public function __construct(
        private readonly DeliveryCommuneMatcherService $deliveryCommuneMatcher,
    ) {
    }

    /**
     * Synchronise un vendeur avant sauvegarde ou via commande de rattrapage.
     *
     * @return array{
     *     changed: bool,
     *     errors: list<string>,
     *     warnings: list<string>,
     *     infos: list<string>
     * }
     */
    public function synchronize(Seller $seller, bool $useCustomerDefaultAddress = true): array
    {
        $changed = false;
        $errors = [];
        $warnings = [];
        $infos = [];

        $customerAccount = $seller->getCustomerAccount();
        $pickupAddress = $seller->getPickupAddress();

        if ($pickupAddress instanceof Address) {
            $pickupCustomer = $pickupAddress->getCustomer();

            if ($customerAccount instanceof Customer && $pickupCustomer->getId() !== $customerAccount->getId()) {
                $errors[] = sprintf(
                    'Adresse de retrait incohérente : elle appartient à "%s" et non au compte client vendeur "%s".',
                    $this->getCustomerLabel($pickupCustomer),
                    $this->getCustomerLabel($customerAccount),
                );
            }

            if (!$customerAccount instanceof Customer) {
                $seller->setCustomerAccount($pickupCustomer);
                $customerAccount = $pickupCustomer;
                $changed = true;
                $infos[] = 'Compte client vendeur déduit depuis le propriétaire de l’adresse de retrait.';
            }
        }

        if (!$pickupAddress instanceof Address && $useCustomerDefaultAddress && $customerAccount instanceof Customer) {
            $defaultAddress = $customerAccount->getDeliveryAddress();

            if ($defaultAddress instanceof Address) {
                $seller->setPickupAddress($defaultAddress);
                $pickupAddress = $defaultAddress;
                $changed = true;
                $infos[] = 'Adresse de retrait initialisée avec l’adresse de livraison par défaut du compte client vendeur.';
            }
        }

        if ($pickupAddress instanceof Address) {
            $resolvedCommune = $this->resolveDeliveryCommuneFromAddress($pickupAddress);

            if (!$resolvedCommune instanceof DeliveryCommune) {
                $errors[] = sprintf(
                    'Impossible de déduire la commune logistique depuis l’adresse de retrait "%s %s". Vérifie Logistique > Communes livrées.',
                    trim($pickupAddress->getPostalCode()),
                    trim($pickupAddress->getCommune()),
                );
            } else {
                if (!$seller->getDeliveryCommune() instanceof DeliveryCommune || $seller->getDeliveryCommune()->getId() !== $resolvedCommune->getId()) {
                    $seller->setDeliveryCommune($resolvedCommune);
                    $changed = true;
                    $infos[] = sprintf('Commune logistique déduite : %s.', $resolvedCommune->getName());
                }

                $zone = $this->deliveryCommuneMatcher->findDeliveryZoneForCommune($resolvedCommune)
                    ?? $this->deliveryCommuneMatcher->findOtherDeliveryZone();

                if (!$zone instanceof DeliveryZone) {
                    $errors[] = sprintf('Impossible de déduire la zone de livraison depuis la commune logistique "%s".', $resolvedCommune->getName());
                } elseif (!$this->sameDeliveryZone($this->getSellerDeliveryZoneOrNull($seller), $zone)) {
                    $seller->setDeliveryZone($zone);
                    $changed = true;
                    $infos[] = sprintf('Zone de livraison déduite : %s.', $zone->getCode());
                }

                if (trim((string) $seller->getCommune()) === '') {
                    $seller->setCommune($resolvedCommune->getName());
                    $changed = true;
                    $infos[] = 'Commune texte historique renseignée par compatibilité.';
                }
            }
        } else {
            if (!$seller->getDeliveryCommune() instanceof DeliveryCommune) {
                $warnings[] = 'Aucune adresse de retrait disponible : la commune logistique ne peut pas être déduite automatiquement.';
            }

            if (!$this->getSellerDeliveryZoneOrNull($seller) instanceof DeliveryZone) {
                $otherZone = $this->deliveryCommuneMatcher->findOtherDeliveryZone();

                if ($otherZone instanceof DeliveryZone) {
                    $seller->setDeliveryZone($otherZone);
                    $changed = true;
                    $warnings[] = 'Zone de livraison initialisée sur AUTRE faute d’adresse de retrait exploitable.';
                } else {
                    $errors[] = 'Aucune zone de livraison exploitable : renseigne une adresse de retrait ou configure la zone AUTRE.';
                }
            }
        }

        return [
            'changed' => $changed,
            'errors' => $errors,
            'warnings' => $warnings,
            'infos' => $infos,
        ];
    }



    /**
     * Crée ou met à jour l'adresse de retrait depuis les champs du formulaire vendeur.
     * Ces champs sont portés temporairement par Seller pour éviter un sélecteur d'adresse en création.
     */
    public function createOrUpdatePickupAddressFromSellerForm(Seller $seller): ?Address
    {
        $customerAccount = $seller->getCustomerAccount();

        if (!$customerAccount instanceof Customer || !$seller->hasPickupAddressFormData()) {
            return $seller->getPickupAddress();
        }

        $line1 = trim((string) $seller->getPickupAddressLine1());
        $selectedCommune = $seller->getPickupDeliveryCommune();

        if (!$selectedCommune instanceof DeliveryCommune) {
            $selectedCommune = $seller->getDeliveryCommune();
        }

        if ($line1 === '' || !$selectedCommune instanceof DeliveryCommune) {
            return $seller->getPickupAddress();
        }

        $postalCode = trim((string) ($selectedCommune->getPostalCode() ?: ''));
        $commune = $selectedCommune->getName();

        $address = $seller->getPickupAddress() ?? new Address();
        $address
            ->setCustomer($customerAccount)
            ->setLabel('Point de retrait vendeur')
            ->setType(Address::TYPE_DELIVERY)
            ->setLine1($line1)
            ->setLine2($seller->getPickupAddressLine2())
            ->setPostalCode($postalCode)
            ->setCommune($commune)
            ->setNotes($seller->getPickupAddressNotes())
            ->setCourierNotes($seller->getPickupAddressCourierNotes())
            ->setGpsLatitude($seller->getPickupAddressGpsLatitude())
            ->setGpsLongitude($seller->getPickupAddressGpsLongitude())
            ->setGpsAccuracyMeters($seller->getPickupAddressGpsAccuracyMeters());

        $zone = $this->deliveryCommuneMatcher->findDeliveryZoneForCommune($selectedCommune)
            ?? $this->deliveryCommuneMatcher->findOtherDeliveryZone();

        if ($zone instanceof DeliveryZone) {
            $address->setDeliveryZone($zone);
            $seller->setDeliveryZone($zone);
        }

        $seller
            ->setDeliveryCommune($selectedCommune)
            ->setCommune($commune);

        $seller->setPickupAddress($address);

        if (!$customerAccount->getAddresses()->contains($address)) {
            $customerAccount->addAddress($address);
        }

        if (!$customerAccount->getDeliveryAddress() instanceof Address) {
            $customerAccount->setDeliveryAddress($address);
        }

        return $address;
    }

    /**
     * Crée une adresse de retrait minimale depuis la commune logistique existante.
     * À utiliser uniquement en rattrapage contrôlé, lorsque le vendeur possède déjà un compte client.
     */
    public function createPickupAddressFromExistingLogisticsCommune(Seller $seller): ?Address
    {
        $customerAccount = $seller->getCustomerAccount();
        $deliveryCommune = $seller->getDeliveryCommune();

        if (!$customerAccount instanceof Customer || !$deliveryCommune instanceof DeliveryCommune) {
            return null;
        }

        $zone = $this->deliveryCommuneMatcher->findDeliveryZoneForCommune($deliveryCommune)
            ?? $this->deliveryCommuneMatcher->findOtherDeliveryZone();

        if (!$zone instanceof DeliveryZone) {
            return null;
        }

        $address = new Address();
        $address
            ->setCustomer($customerAccount)
            ->setLabel('Point de retrait vendeur')
            ->setType(Address::TYPE_DELIVERY)
            ->setLine1('Point de retrait à préciser')
            ->setPostalCode((string) ($deliveryCommune->getPostalCode() ?: ''))
            ->setCommune($deliveryCommune->getName())
            ->setDeliveryZone($zone)
            ->setDeliveryInstructions('Adresse initialisée depuis la commune logistique vendeur. À préciser avant exploitation terrain.');

        $seller->setPickupAddress($address);

        return $address;
    }

    private function resolveDeliveryCommuneFromAddress(Address $address): ?DeliveryCommune
    {
        return $this->deliveryCommuneMatcher->resolve($address->getCommune(), $address->getPostalCode())
            ?? $this->deliveryCommuneMatcher->resolveByCommuneName($address->getCommune());
    }

    private function sameDeliveryZone(?DeliveryZone $currentZone, DeliveryZone $expectedZone): bool
    {
        if (!$currentZone instanceof DeliveryZone) {
            return false;
        }

        if ($currentZone->getId() !== null && $expectedZone->getId() !== null) {
            return $currentZone->getId() === $expectedZone->getId();
        }

        return $currentZone->getCode() === $expectedZone->getCode();
    }

    private function getSellerDeliveryZoneOrNull(Seller $seller): ?DeliveryZone
    {
        try {
            return $seller->getDeliveryZone();
        } catch (\Throwable) {
            return null;
        }
    }

    private function getCustomerLabel(Customer $customer): string
    {
        $name = trim(sprintf('%s %s', $customer->getFirstName(), (string) $customer->getLastName()));

        if ($name !== '') {
            return $name;
        }

        return $customer->getEmail() ?: sprintf('Client #%s', $customer->getId() ?? '?');
    }
}
