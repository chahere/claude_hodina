<?php

namespace App\Controller\Admin;

use App\Entity\CustomerOrderFeedback;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class CustomerOrderFeedbackCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CustomerOrderFeedback::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Retour client')
            ->setEntityLabelInPlural('Retours clients')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static fn (Action $action): Action => $action->setLabel('Voir'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID');
        yield DateTimeField::new('createdAt', 'Créé le');
        yield AssociationField::new('customerOrder', 'Commande');
        yield AssociationField::new('customer', 'Client');
        yield ChoiceField::new('targetType', 'Type')->setChoices([
            'Commande' => CustomerOrderFeedback::TARGET_ORDER,
            'Vendeur' => CustomerOrderFeedback::TARGET_SELLER,
            'Livreur' => CustomerOrderFeedback::TARGET_COURIER,
            'Annulation' => CustomerOrderFeedback::TARGET_CANCELLATION,
        ]);
        yield TextField::new('targetKey', 'Cible');
        yield AssociationField::new('seller', 'Vendeur')->hideOnIndex();
        yield AssociationField::new('courier', 'Livreur')->hideOnIndex();
        yield IntegerField::new('rating', 'Note')->hideOnIndex();
        yield ChoiceField::new('reason', 'Motif')
            ->setChoices(array_flip(CustomerOrderFeedback::getReasonLabels()))
            ->formatValue(static fn (?string $value, mixed $feedback): string => $feedback instanceof CustomerOrderFeedback ? $feedback->getReasonLabel() : (CustomerOrderFeedback::getReasonLabels()[$value ?? ''] ?? 'Non renseigné'));
        yield TextareaField::new('comment', 'Commentaire');
        yield TextField::new('source', 'Source')->hideOnIndex();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->onlyOnDetail();
    }
}
