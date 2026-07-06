<?php

namespace App\Form;

use App\Entity\DeliveryCommune;
use App\Service\DeliveryPointCartService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use App\Service\PhoneNumberNormalizer;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class CheckoutType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $firstNameRequired = [new NotBlank(message: 'Indique le prénom de la personne qui recevra la commande.')];
        $lastNameRequired = [new NotBlank(message: 'Indique le nom de la personne qui recevra la commande.')];
        $phoneRequired = [new NotBlank(message: 'Indique le téléphone de la personne qui recevra la commande.')];
        $emailRequired = [new NotBlank(message: 'Indique l’e-mail de la personne qui recevra la commande.')];
        $deliveryCommunes = $this->normalizeDeliveryCommunes($options['delivery_communes'] ?? []);

        $builder
            ->add('firstName', TextType::class, ['label' => 'Prénom', 'constraints' => $firstNameRequired])
            ->add('lastName', TextType::class, ['label' => 'Nom', 'constraints' => $lastNameRequired])
            ->add('phoneCountryCode', ChoiceType::class, [
                'label' => 'Indicatif',
                'mapped' => false,
                'required' => true,
                'choices' => PhoneNumberNormalizer::dialCodeChoices(),
                'data' => PhoneNumberNormalizer::DEFAULT_DIAL_CODE,
                'constraints' => [
                    new NotBlank(message: 'Choisis l’indicatif du numéro de téléphone.'),
                ],
                'attr' => [
                    'autocomplete' => 'tel-country-code',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'constraints' => $phoneRequired,
                'attr' => [
                    'autocomplete' => 'tel-national',
                    'inputmode' => 'tel',
                    'placeholder' => 'Ex : 0639 12 34 56 ou 0269 60 00 00',
                ],
                'help' => 'Choisis l’indicatif puis saisis le numéro local. Les fixes sont acceptés.',
            ])
            ->add('email', EmailType::class, ['label' => 'Email', 'constraints' => $emailRequired])
            ->add('existingAddressId', HiddenType::class, ['mapped' => false, 'required' => false])
            ->add('confirmExistingAccount', HiddenType::class, [
                'mapped' => false,
                'required' => false,
                'data' => '0',
            ])
            ->add('confirmedExistingAccountEmail', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('makeDeliveryDefault', CheckboxType::class, [
                'label' => 'Utiliser cette adresse par défaut',
                'mapped' => false,
                'required' => false,
            ])
            ->add('deliveryMethod', ChoiceType::class, [
                'label' => 'Mode de remise',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new NotBlank(message: 'Choisis un mode de livraison pour valider ta commande.'),
                ],
                'choices' => [
                    'Livraison à une adresse' => DeliveryPointCartService::METHOD_STANDARD,
                    'Point de remise Hodina' => DeliveryPointCartService::METHOD_DELIVERY_POINT,
                ],
                'data' => DeliveryPointCartService::METHOD_STANDARD,
            ])
            ->add('deliveryPointId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('deliveryPointTimeWindowId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('deliveryPointRequestedDate', DateType::class, [
                'label' => 'Date de rendez-vous',
                'mapped' => false,
                'required' => false,
                'widget' => 'single_text',
                'html5' => true,
                'attr' => [
                    'autocomplete' => 'off',
                ],
            ])
            ->add('deliveryPointRequestedTime', HiddenType::class, [
                'label' => 'Créneau de remise',
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'data-delivery-point-time-input' => '1',
                ],
            ])
            ->add('deliveryPointCustomerInstructions', TextareaType::class, [
                'label' => 'Précision pour le point de remise',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length(
                        max: 1000,
                        maxMessage: 'La précision pour le point de remise ne doit pas dépasser {{ limit }} caractères.'
                    ),
                ],
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Exemple : j’arrive par le vol de 18h40, je serai devant l’accueil passager…',
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse de livraison',
                'required' => false,
            ])
            ->add('addressLocalityId', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('localityText', TextType::class, [
                'label' => 'Localité',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length(
                        max: 120,
                        maxMessage: 'La localité ne doit pas dépasser {{ limit }} caractères.'
                    ),
                ],
                'attr' => [
                    'autocomplete' => 'address-level3',
                    'placeholder' => 'Ex : Kawéni, Kavani, Mtsapéré…',
                    'data-address-locality-text' => '1',
                ],
            ])
            ->add('postalCode', ChoiceType::class, [
                'label' => 'Code postal',
                'placeholder' => 'Choisir un code postal…',
                'choices' => $this->buildDeliveryPostalCodeChoices($deliveryCommunes),
                'required' => false,
                'invalid_message' => 'Ce code postal n’est pas reconnu par Hodina. Choisis un code postal proposé dans la liste.',
                'attr' => [
                    'data-delivery-postal-select' => '1',
                ],
            ])
            ->add('commune', ChoiceType::class, [
                'label' => 'Commune livrée',
                'placeholder' => 'Choisir une commune livrée…',
                'choices' => $this->buildDeliveryCommuneChoices($deliveryCommunes),
                'choice_attr' => $this->buildDeliveryCommuneChoiceAttributes($deliveryCommunes),
                'required' => false,
                'invalid_message' => 'Cette commune n’est plus livrée par Hodina. Choisis une commune proposée dans la liste.',
                'attr' => [
                    'data-delivery-commune-select' => '1',
                ],
            ])
            ->add('zone', HiddenType::class, [
                'required' => false,
            ])
            ->add('deliveryInstructions', TextareaType::class, [
                'label' => 'Instructions de livraison',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new Length(
                        max: 1000,
                        maxMessage: 'Les instructions de livraison ne doivent pas dépasser {{ limit }} caractères.'
                    ),
                ],
                'attr' => [
                    'rows' => 3,
                    'placeholder' => 'Exemple : près du centre commercial Baobab, portail bleu, appeler en arrivant…',
                ],
            ])
            ->add('gpsLatitude', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('gpsLongitude', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('gpsAccuracyMeters', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('customerTimezone', HiddenType::class, [
                'mapped' => false,
                'required' => false,
            ])
            ->add('useBillingSameAsDelivery', CheckboxType::class, [
                'label' => 'Utiliser cette adresse aussi pour la facturation',
                'mapped' => false,
                'required' => false,
                'data' => true,
            ])
            ->add('billingExistingAddressId', HiddenType::class, ['mapped' => false, 'required' => false])
            ->add('makeBillingDefault', CheckboxType::class, [
                'label' => 'Utiliser cette adresse par défaut',
                'mapped' => false,
                'required' => false,
            ])
            ->add('billingAddress', TextType::class, [
                'label' => 'Adresse de facturation',
                'mapped' => false,
                'required' => false,
            ])
            ->add('billingPostalCode', TextType::class, [
                'label' => 'Code postal de facturation',
                'mapped' => false,
                'required' => false,
            ])
            ->add('billingCommune', TextType::class, [
                'label' => 'Commune de facturation',
                'mapped' => false,
                'required' => false,
            ])
            ->add('billingZone', ChoiceType::class, [
                'label' => 'Zone de facturation',
                'mapped' => false,
                'required' => false,
                'choices' => [
                    'Autre' => 'AUTRE',
                    'Petite-Terre (PT)' => 'PT',
                    'Grande-Terre (GT)' => 'GT',
                ],
                'data' => 'AUTRE',
            ])
            ->add('acceptTerms', CheckboxType::class, [
                'label' => 'J’accepte les CGV et les CGU de Hodina',
                'mapped' => false,
                'required' => true,
                'constraints' => [
                    new IsTrue(
                        message: 'Vous devez accepter les CGU et CGV pour valider votre commande.'
                    ),
                ],
            ]);

        $builder->addEventListener(FormEvents::POST_SUBMIT, static function (FormEvent $event): void {
            $form = $event->getForm();

            if (!$form->has('deliveryMethod')) {
                return;
            }

            $deliveryMethod = (string) $form->get('deliveryMethod')->getData();
            if ($deliveryMethod !== DeliveryPointCartService::METHOD_STANDARD) {
                return;
            }

            $existingAddressId = $form->has('existingAddressId')
                ? trim((string) $form->get('existingAddressId')->getData())
                : '';

            if ($existingAddressId !== '') {
                return;
            }

            $address = $form->has('address') ? trim((string) $form->get('address')->getData()) : '';
            if ($address === '') {
                $form->get('address')->addError(new FormError('Indique l’adresse de livraison.'));
            }

            $postalCode = $form->has('postalCode') ? trim((string) $form->get('postalCode')->getData()) : '';
            if ($postalCode === '') {
                $form->get('postalCode')->addError(new FormError('Choisis un code postal connu par Hodina.'));
            }

            $commune = $form->has('commune') ? trim((string) $form->get('commune')->getData()) : '';
            if ($commune === '') {
                $form->get('commune')->addError(new FormError('Choisis une commune livrée par Hodina.'));
            }
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'delivery_communes' => [],
        ]);

        $resolver->setAllowedTypes('delivery_communes', ['array']);
    }

    /**
     * @param array<mixed> $deliveryCommunes
     * @return list<DeliveryCommune>
     */
    private function normalizeDeliveryCommunes(array $deliveryCommunes): array
    {
        return array_values(array_filter(
            $deliveryCommunes,
            static fn (mixed $commune): bool => $commune instanceof DeliveryCommune
        ));
    }

    /**
     * @param list<DeliveryCommune> $deliveryCommunes
     * @return array<string, string>
     */
    private function buildDeliveryPostalCodeChoices(array $deliveryCommunes): array
    {
        $postalCodes = [];

        foreach ($deliveryCommunes as $commune) {
            $postalCode = trim((string) $commune->getPostalCode());
            if ($postalCode === '') {
                continue;
            }

            $postalCodes[$postalCode] = $postalCode;
        }

        ksort($postalCodes, SORT_STRING);

        return $postalCodes;
    }

    /**
     * @param list<DeliveryCommune> $deliveryCommunes
     * @return array<string, string>
     */
    private function buildDeliveryCommuneChoices(array $deliveryCommunes): array
    {
        $choices = [];

        foreach ($deliveryCommunes as $commune) {
            $label = sprintf(
                '%s — %s',
                $commune->getName(),
                $this->formatTerritoryLabel($commune->getTerritory())
            );

            $choices[$label] = $commune->getName();
        }

        return $choices;
    }

    /**
     * @param list<DeliveryCommune> $deliveryCommunes
     */
    private function buildDeliveryCommuneChoiceAttributes(array $deliveryCommunes): callable
    {
        $communesByName = [];

        foreach ($deliveryCommunes as $commune) {
            $communesByName[$commune->getName()] = $commune;
        }

        return static function (mixed $choice, string $key, mixed $value) use ($communesByName): array {
            $commune = $communesByName[(string) $value] ?? null;

            if (!$commune instanceof DeliveryCommune) {
                return [];
            }

            return [
                'data-postal-code' => (string) $commune->getPostalCode(),
                'data-zone' => $commune->getTerritory(),
            ];
        };
    }

    private function formatTerritoryLabel(string $territory): string
    {
        return match ($territory) {
            DeliveryCommune::TERRITORY_PT => 'Petite-Terre',
            DeliveryCommune::TERRITORY_GT => 'Grande-Terre',
            default => $territory,
        };
    }
}
