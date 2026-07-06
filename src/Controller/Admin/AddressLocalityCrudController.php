<?php

namespace App\Controller\Admin;

use App\Entity\AddressLocality;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AddressLocalityCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return AddressLocality::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Localité')
            ->setEntityLabelInPlural('Localités')
            ->setDefaultSort(['sortOrder' => 'ASC', 'name' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $a) => $a->setLabel('Ajouter une localité'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Localité')
            ->setHelp('Village / quartier / lieu-dit. Exemple : Kawéni, Kavani, Tsoundzou I.');
        yield TextField::new('normalizedName', 'Nom normalisé')
            ->hideOnForm()
            ->setHelp('Renseigné automatiquement depuis le nom.');
        yield AssociationField::new('deliveryCommune', 'Commune livrée associée')
            ->setRequired(false)
            ->setHelp('Aide au préremplissage. La commune de livraison reste stockée dans Address.commune.');
        yield TextField::new('postalCode', 'Code postal')
            ->setRequired(false)
            ->setHelp('Indicatif et cohérent avec la commune associée. Ne calcule jamais les frais.');
        yield TextField::new('countryCode', 'Pays')
            ->setRequired(false)
            ->setHelp('Code pays indicatif. Pour Mayotte : YT.');
        yield BooleanField::new('isActive', 'Active')
            ->setHelp('Une localité inactive reste lisible sur les anciennes adresses mais n’est plus proposée aux nouveaux clients.');
        yield IntegerField::new('sortOrder', 'Ordre')
            ->setHelp('Ordre d’affichage dans les suggestions.');
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Dernière mise à jour')->hideOnForm()->hideOnIndex();
    }
}
