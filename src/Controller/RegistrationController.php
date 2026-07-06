<?php

namespace App\Controller;

use App\Entity\Address;
use App\Entity\Customer;
use App\Entity\DeliveryCommune;
use App\Form\RegistrationFormType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;
use App\Security\AppAuthenticator;
use App\Service\DeliveryCommuneMatcherService;
use App\Service\PhoneNumberNormalizer;

class RegistrationController extends AbstractController
{
    #[Route('/caribou', name: 'app_register')]
    public function register(
        Request $request,
        UserPasswordHasherInterface $userPasswordHasher,
        UserAuthenticatorInterface $userAuthenticator,
        AppAuthenticator $authenticator,
        EntityManagerInterface $entityManager,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        PhoneNumberNormalizer $phoneNumberNormalizer
    ): Response {
        // si déjà connecté, on renvoie (évite des comportements bizarres)
        if ($this->getUser()) {
            if ($this->isGranted('ROLE_ADMIN')) {
                return $this->redirectToRoute('backoffice');
            }
            return $this->redirectToRoute('product_catalogue');
        }

        $user = new Customer();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            $submittedEmail = trim((string) $form->get('email')->getData());

            if ($submittedEmail !== '' && null !== $entityManager->getRepository(Customer::class)->findOneBy(['email' => $submittedEmail])) {
                $form->get('email')->addError(new FormError('Un compte existe déjà avec cette adresse e-mail. Connecte-toi ou utilise “Mot de passe oublié”.'));
            }
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $deliveryCommune = $this->validateDeliveryAddressData(
                $form,
                $deliveryCommuneMatcher,
                (string) $form->get('commune')->getData(),
                (string) $form->get('postalCode')->getData(),
                (string) $form->get('zone')->getData()
            );

            if (!$deliveryCommune) {
                return $this->renderRegistration($form);
            }

            $deliveryZone = $deliveryCommuneMatcher->findDeliveryZoneForCommune($deliveryCommune);
            if (!$deliveryZone) {
                $form->get('zone')->addError(new FormError(sprintf('Zone de livraison %s inactive ou absente.', $this->formatZoneLabel($deliveryCommune->getTerritory()))));

                return $this->renderRegistration($form);
            }

            $useBillingSameAsDelivery = (bool) $form->get('useBillingSameAsDelivery')->getData();
            $billingLine1 = trim((string) ($useBillingSameAsDelivery ? $form->get('address')->getData() : $form->get('billingAddress')->getData()));
            $billingPostalCode = trim((string) ($useBillingSameAsDelivery ? $form->get('postalCode')->getData() : $form->get('billingPostalCode')->getData()));
            $billingCommune = trim((string) ($useBillingSameAsDelivery ? $deliveryCommune->getName() : $form->get('billingCommune')->getData()));
            $billingZoneCode = trim((string) ($useBillingSameAsDelivery ? $deliveryCommune->getTerritory() : $form->get('billingZone')->getData()));

            $billingZone = $useBillingSameAsDelivery
                ? $deliveryZone
                : $this->validateBillingAddressData(
                    $form,
                    $deliveryCommuneMatcher,
                    $billingLine1,
                    $billingPostalCode,
                    $billingCommune,
                    $billingZoneCode
                );

            if (!$billingZone) {
                return $this->renderRegistration($form);
            }

            $normalizedPhone = $phoneNumberNormalizer->normalizeWithDialCode(
                (string) $form->get('phoneCountryCode')->getData(),
                $user->getPhone()
            );
            if ($normalizedPhone !== '') {
                $user->setPhone($normalizedPhone);
            }

            /** @var string $plainPassword */
            $plainPassword = (string) $form->get('plainPassword')->getData();

            $user->setPassword(
                $userPasswordHasher->hashPassword($user, $plainPassword)
            );

            // valeur par défaut, si ton entity gère déjà ça ignore
            if (method_exists($user, 'setRoles')) {
                $user->setRoles(['ROLE_USER']);
            }

            $address = (new Address())
                ->setCustomer($user)
                ->setType(Address::TYPE_DELIVERY)
                ->setLabel('Adresse de livraison principale')
                ->setLine1((string) $form->get('address')->getData())
                ->setPostalCode((string) $form->get('postalCode')->getData())
                ->setCommune($deliveryCommune->getName())
                ->setDeliveryZone($deliveryZone);

            $billingAddress = (new Address())
                ->setCustomer($user)
                ->setType(Address::TYPE_BILLING)
                ->setLabel('Adresse de facturation')
                ->setLine1($billingLine1)
                ->setPostalCode($billingPostalCode)
                ->setCommune($billingCommune)
                ->setDeliveryZone($billingZone);

            $user->addAddress($address);
            $user->addAddress($billingAddress);
            $user->setBillingAddress($billingAddress);

            $entityManager->persist($user);
            $entityManager->persist($address);
            $entityManager->persist($billingAddress);
            $entityManager->flush();

            // login automatique
            return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );
        }

        return $this->renderRegistration($form);
    }


    private function renderRegistration(FormInterface $form): Response
    {
        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    private function validateDeliveryAddressData(
        FormInterface $form,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        string $commune,
        string $postalCode,
        string $zoneCode
    ): ?DeliveryCommune {
        $commune = trim($commune);
        $postalCode = trim($postalCode);
        $zoneCode = trim($zoneCode);

        if ($commune === '') {
            $form->get('commune')->addError(new FormError('La commune de livraison est obligatoire.'));
            return null;
        }

        $deliveryCommune = $deliveryCommuneMatcher->resolveByCommuneName($commune);

        if (!$deliveryCommune) {
            $form->get('commune')->addError(new FormError($deliveryCommuneMatcher->buildValidationMessage($commune, $postalCode)));
            return null;
        }

        $expectedPostalCode = trim((string) $deliveryCommune->getPostalCode());
        if ($expectedPostalCode !== '' && $postalCode !== $expectedPostalCode) {
            $form->get('postalCode')->addError(new FormError(sprintf(
                'Le code postal %s ne correspond pas à la commune %s. Le code postal attendu est %s.',
                $postalCode,
                $deliveryCommune->getName(),
                $expectedPostalCode
            )));

            return null;
        }

        if ($zoneCode !== $deliveryCommune->getTerritory()) {
            $form->get('zone')->addError(new FormError(sprintf(
                'La commune %s appartient à %s, pas à la zone %s.',
                $deliveryCommune->getName(),
                $this->formatZoneLabel($deliveryCommune->getTerritory()),
                $this->formatZoneLabel($zoneCode)
            )));

            return null;
        }

        return $deliveryCommune;
    }

    private function validateBillingAddressData(
        FormInterface $form,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        string $line1,
        string $postalCode,
        string $commune,
        string $zoneCode
    ): mixed {
        $line1 = trim($line1);
        $postalCode = trim($postalCode);
        $commune = trim($commune);
        $zoneCode = trim($zoneCode);

        if ($line1 === '') {
            $form->get('billingAddress')->addError(new FormError('La première ligne de l’adresse de facturation ne doit pas être vide.'));
            return null;
        }

        if (!$deliveryCommuneMatcher->isValidFrenchPostalCode($postalCode)) {
            $form->get('billingPostalCode')->addError(new FormError('Le code postal de facturation doit contenir exactement 5 chiffres.'));
            return null;
        }

        if ($commune === '') {
            $form->get('billingCommune')->addError(new FormError('La commune de facturation est obligatoire.'));
            return null;
        }

        if ($zoneCode === DeliveryCommuneMatcherService::ZONE_AUTRE) {
            $otherZone = $deliveryCommuneMatcher->findOtherDeliveryZone();

            if (!$otherZone) {
                $form->get('billingZone')->addError(new FormError('La zone AUTRE — Autre est absente. Elle est nécessaire pour les adresses de facturation hors zone livrable.'));
                return null;
            }

            return $otherZone;
        }

        if (!in_array($zoneCode, ['PT', 'GT'], true)) {
            $form->get('billingZone')->addError(new FormError('La zone de facturation doit être Petite-Terre, Grande-Terre ou AUTRE — Autre.'));
            return null;
        }

        $billingCommune = $deliveryCommuneMatcher->resolveByCommuneName($commune);

        if (!$billingCommune) {
            $form->get('billingCommune')->addError(new FormError($deliveryCommuneMatcher->buildValidationMessage($commune, $postalCode)));
            return null;
        }

        $expectedPostalCode = trim((string) $billingCommune->getPostalCode());
        if ($expectedPostalCode !== '' && $postalCode !== $expectedPostalCode) {
            $form->get('billingPostalCode')->addError(new FormError(sprintf(
                'Le code postal %s ne correspond pas à la commune %s. Le code postal attendu est %s.',
                $postalCode,
                $billingCommune->getName(),
                $expectedPostalCode
            )));

            return null;
        }

        if ($zoneCode !== $billingCommune->getTerritory()) {
            $form->get('billingZone')->addError(new FormError(sprintf(
                'La commune %s appartient à %s, pas à la zone %s.',
                $billingCommune->getName(),
                $this->formatZoneLabel($billingCommune->getTerritory()),
                $this->formatZoneLabel($zoneCode)
            )));

            return null;
        }

        $billingZone = $deliveryCommuneMatcher->findDeliveryZoneForCommune($billingCommune);

        if (!$billingZone) {
            $form->get('billingZone')->addError(new FormError(sprintf('Zone de facturation %s inactive ou absente.', $this->formatZoneLabel($billingCommune->getTerritory()))));
            return null;
        }

        return $billingZone;
    }

    private function formatZoneLabel(string $zoneCode): string
    {
        return match ($zoneCode) {
            'PT' => 'Petite-Terre (PT)',
            'GT' => 'Grande-Terre (GT)',
            DeliveryCommuneMatcherService::ZONE_AUTRE => 'Autre',
            default => $zoneCode,
        };
    }

}
