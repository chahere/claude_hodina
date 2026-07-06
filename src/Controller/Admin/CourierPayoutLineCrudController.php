<?php

namespace App\Controller\Admin;

use App\Entity\CourierPayoutLine;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class CourierPayoutLineCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CourierPayoutLine::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Ligne rémunération livreur')
            ->setEntityLabelInPlural('Lignes rémunération livreur')
            ->setDefaultSort(['deliveredAt' => 'DESC', 'id' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static fn (Action $action): Action => $action->setLabel('Voir'))
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('courierPayout', 'Paiement');
        yield AssociationField::new('customerOrder', 'Commande');
        yield TextField::new('orderReference', 'Référence');
        yield DateTimeField::new('deliveredAt', 'Livrée le');
        yield TextField::new('customerCommune', 'Commune client');
        yield MoneyField::new('courierPayoutAmount', 'Gain livreur')->setCurrency('EUR')->setStoredAsCents(false);
        yield MoneyField::new('deliveryFeeCustomer', 'Frais livraison client')->setCurrency('EUR')->setStoredAsCents(false);
        yield TextareaField::new('snapshot', 'Snapshot')->onlyOnDetail()->formatValue(static function ($value): string {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '';
        });
        yield DateTimeField::new('createdAt', 'Créée le')->onlyOnDetail();
    }
}
