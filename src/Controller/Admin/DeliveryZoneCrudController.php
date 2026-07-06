<?php
namespace App\Controller\Admin;

use App\Entity\DeliveryZone;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
class DeliveryZoneCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliveryZone::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Zone de livraison')
            ->setEntityLabelInPlural('Zones de livraison');
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $a) => $a->setLabel('Ajouter une zone'));
    }
    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        // Champs métier (ajuste si tes noms diffèrent)
        yield TextField::new('name', 'Nom');
        yield TextField::new('Code', 'Code');
        yield BooleanField::new('isActive', 'Actif');        
        // ? createdAt visible (liste/détail) mais NON éditable
        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
    }
}
