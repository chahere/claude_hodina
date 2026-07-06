<?php

namespace App\Controller\Admin;

use App\Entity\ProductDeliveryPoint;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;

final class ProductDeliveryPointCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return ProductDeliveryPoint::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Point autorisé pour produit')
            ->setEntityLabelInPlural('Produits ↔ points de remise')
            ->setDefaultSort(['sortOrder' => 'ASC', 'createdAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, static fn (Action $action): Action => $action->setLabel('Associer un point'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('product', 'Produit')
            ->setRequired(true)
            ->setHelp('Le produit doit avoir un mode compatible avec les points de remise : “Point imposé uniquement” ou “Livraison standard + point de remise”.');
        yield AssociationField::new('deliveryPoint', 'Point de remise')->setRequired(true);
        yield BooleanField::new('isActive', 'Active')->renderAsSwitch(true);
        yield IntegerField::new('sortOrder', 'Ordre')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm()->hideOnIndex();
    }
}
