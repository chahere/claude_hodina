<?php

namespace App\Service;

use App\Entity\Address;
use App\Entity\Customer;
use Doctrine\ORM\EntityManagerInterface;

final class CustomerAnonymizerService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * @return array{addresses:int}
     */
    public function preview(Customer $customer): array
    {
        return [
            'addresses' => count($customer->getAddresses()),
        ];
    }

    /**
     * Anonymisation RGPD : remplace les données personnelles du client par des
     * valeurs génériques et bloque sa connexion, sans toucher à son historique
     * (commandes, tickets support, conversations IA, paiements livreur), qui reste
     * rattaché à cette fiche client — seule son identité devient anonyme.
     *
     * Non réversible : les données d'origine ne sont pas conservées ailleurs.
     *
     * @return array{addresses:int}
     */
    public function anonymize(Customer $customer): array
    {
        if ($customer->isAnonymized()) {
            throw new \LogicException(sprintf('Le client #%d est déjà anonymisé.', $customer->getId()));
        }

        $summary = [
            'addresses' => count($customer->getAddresses()),
        ];

        $this->entityManager->beginTransaction();

        try {
            $id = $customer->getId();

            $customer->setFirstName('Client');
            $customer->setLastName('anonymisé');
            $customer->setPhone('0000000000');
            $customer->setEmail(sprintf('client-%d@anonymise.hodina.local', $id));
            $customer->setPassword(bin2hex(random_bytes(32)));
            $customer->setPlainPassword(null);
            $customer->setResetPasswordToken(null);
            $customer->setResetPasswordTokenExpiresAt(null);
            $customer->setCourierPayoutCap(null);

            if ($customer->getBillingAddress() instanceof Address) {
                $customer->setBillingAddress(null);
            }
            if ($customer->getDeliveryAddress() instanceof Address) {
                $customer->setDeliveryAddress(null);
            }

            foreach ($customer->getAddresses()->toArray() as $address) {
                if ($address instanceof Address) {
                    $this->entityManager->remove($address);
                }
            }

            $customer->setIsActive(false);
            $customer->setAnonymizedAt(new \DateTimeImmutable());

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw $exception;
        }

        return $summary;
    }
}
