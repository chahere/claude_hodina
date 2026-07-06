<?php

namespace App\Form;

use App\Entity\Customer;
use App\Service\PhoneNumberNormalizer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Regex;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $postalCode = [
            new NotBlank(message: 'Veuillez saisir un code postal.'),
            new Regex(pattern: '/^\\d{5}$/', message: 'Le code postal doit contenir exactement 5 chiffres.'),
        ];

        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre prénom.'),
                    new Length(min: 2, max: 100, minMessage: 'Minimum {{ limit }} caractères.'),
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre nom.'),
                    new Length(min: 2, max: 100, minMessage: 'Minimum {{ limit }} caractères.'),
                ],
            ])
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
                'attr' => [
                    'autocomplete' => 'tel-national',
                    'inputmode' => 'tel',
                    'placeholder' => 'Ex : 0639 12 34 56 ou 0269 60 00 00',
                ],
                'help' => 'Choisis l’indicatif puis saisis le numéro local. Les fixes sont acceptés.',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre téléphone.'),
                    new Length(min: 6, max: 30, minMessage: 'Minimum {{ limit }} caractères.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un email.'),
                    new Email(message: 'Veuillez saisir un email valide.'),
                    new Length(max: 180, maxMessage: 'Max {{ limit }} caractères.'),
                ],
            ])
            ->add('address', TextType::class, [
                'label' => 'Adresse de livraison',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre adresse de livraison.'),
                    new Length(max: 180, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            ->add('postalCode', TextType::class, [
                'label' => 'Code postal de livraison',
                'mapped' => false,
                'constraints' => $postalCode,
            ])
            ->add('commune', TextType::class, [
                'label' => 'Commune de livraison',
                'mapped' => false,
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre commune de livraison.'),
                    new Length(max: 120, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            ->add('zone', ChoiceType::class, [
                'label' => 'Zone de livraison',
                'mapped' => false,
                'choices' => [
                    'Petite-Terre (PT)' => 'PT',
                    'Grande-Terre (GT)' => 'GT',
                ],
                'placeholder' => 'Choisir…',
                'constraints' => [
                    new NotBlank(message: 'Veuillez choisir votre zone de livraison.'),
                ],
            ])
            ->add('useBillingSameAsDelivery', CheckboxType::class, [
                'label' => 'Utiliser cette adresse aussi pour la facturation',
                'mapped' => false,
                'required' => false,
                'data' => true,
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
            ->add('agreeTerms', CheckboxType::class, [
                'label' => 'J’accepte les conditions',
                'mapped' => false,
                'constraints' => [
                    new IsTrue(message: 'Vous devez accepter les conditions.'),
                ],
            ])
            ->add('plainPassword', PasswordType::class, [
                'mapped' => false,
                'label' => 'Mot de passe',
                'attr' => ['autocomplete' => 'new-password'],
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un mot de passe.'),
                    new Length(
                        min: 6,
                        max: 4096,
                        minMessage: 'Votre mot de passe doit faire au moins {{ limit }} caractères.'
                    ),
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Customer::class,
        ]);
    }
}
