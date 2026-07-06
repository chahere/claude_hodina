<?php

declare(strict_types=1);

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Formulaire de contact public. Volontairement non lié à une entité :
 * le contrôleur construit lui-même le SupportTicket à partir des données reçues.
 */
class ContactFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Nom',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre nom.'),
                    new Length(min: 2, max: 150, minMessage: 'Minimum {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            ->add('email', EmailType::class, [
                'label' => 'E-mail',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre e-mail.'),
                    new Email(message: 'Veuillez saisir un e-mail valide.'),
                    new Length(max: 180, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone (optionnel)',
                'required' => false,
                'constraints' => [
                    new Length(max: 30, maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            ->add('subject', TextType::class, [
                'label' => 'Sujet',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir un sujet.'),
                    new Length(min: 3, max: 200, minMessage: 'Minimum {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            ->add('message', TextareaType::class, [
                'label' => 'Message',
                'constraints' => [
                    new NotBlank(message: 'Veuillez saisir votre message.'),
                    new Length(min: 10, max: 5000, minMessage: 'Minimum {{ limit }} caractères.', maxMessage: 'Maximum {{ limit }} caractères.'),
                ],
            ])
            // Piège à robots : champ invisible pour un visiteur humain, jamais rempli.
            // S'il arrive rempli, la soumission est ignorée silencieusement (cf. ContactController).
            ->add('website', TextType::class, [
                'label' => false,
                'required' => false,
            ]);
    }
}
