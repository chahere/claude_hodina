<?php

namespace App\Controller\Admin;

use App\Entity\CustomerSignup;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

final class CustomerSignupCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CustomerSignup::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->onlyOnIndex();

        yield DateTimeField::new('createdAt', 'Créé le')->onlyOnIndex();

        yield TextField::new('firstName', 'Prénom');
        yield TextField::new('lastName', 'Nom');
        yield EmailField::new('email', 'Email');
        yield TextField::new('phone', 'Téléphone');

        yield TextareaField::new('address', 'Adresse')->hideOnIndex();

        yield ChoiceField::new('zone', 'Zone')
            ->setChoices(['Petit-Terre' => 'PT', 'Grande-Terre' => 'GT']);

        yield TextareaField::new('cartSnapshot', 'Panier (snapshot)')
            ->hideOnIndex();
    }
}
