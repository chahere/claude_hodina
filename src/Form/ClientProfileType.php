<?php

namespace App\Form;

use App\Service\PhoneNumberNormalizer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ClientProfileType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('firstName', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre prénom.'),
                    new Length(min: 2, max: 100, minMessage: 'Minimum {{ limit }} caractères.'),
                ],
                'attr' => [
                    'autocomplete' => 'given-name',
                ],
            ])
            ->add('lastName', TextType::class, [
                'label' => 'Nom',
                'required' => false,
                'constraints' => [
                    new Length(max: 100, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
                'attr' => [
                    'autocomplete' => 'family-name',
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'Email',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un email.'),
                    new Email(message: 'Veuillez saisir un email valide.'),
                    new Length(max: 180, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
                'attr' => [
                    'autocomplete' => 'email',
                ],
            ])
            ->add('phoneCountryCode', ChoiceType::class, [
                'label' => 'Indicatif',
                'choices' => PhoneNumberNormalizer::dialCodeChoices(),
                'constraints' => [
                    new NotBlank(message: 'Choisis l’indicatif du numéro de téléphone.'),
                ],
                'attr' => [
                    'autocomplete' => 'tel-country-code',
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre téléphone.'),
                    new Length(min: 6, max: 30, minMessage: 'Minimum {{ limit }} caractères.'),
                ],
                'attr' => [
                    'autocomplete' => 'tel-national',
                    'inputmode' => 'tel',
                    'placeholder' => 'Ex : 0639 12 34 56',
                ],
                'help' => 'Choisis l’indicatif puis saisis le numéro local. Les fixes sont acceptés.',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'client_profile',
        ]);
    }
}
