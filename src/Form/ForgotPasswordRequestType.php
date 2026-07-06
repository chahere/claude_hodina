<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

final class ForgotPasswordRequestType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('email', EmailType::class, [
            'label' => 'Adresse email du compte',
            'mapped' => false,
            'attr' => [
                'autocomplete' => 'email',
                'placeholder' => 'exemple@email.com',
            ],
            'constraints' => [
                new NotBlank(message: 'Veuillez saisir l’adresse email du compte.'),
                new Email(message: 'Veuillez saisir une adresse email valide.'),
                new Length(max: 180, maxMessage: 'Maximum {{ limit }} caractères.'),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'csrf_protection' => true,
            'csrf_field_name' => '_token',
            'csrf_token_id' => 'forgot_password_request',
        ]);
    }
}
