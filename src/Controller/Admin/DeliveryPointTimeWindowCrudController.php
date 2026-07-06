<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryPointTimeWindow;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;

final class DeliveryPointTimeWindowCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliveryPointTimeWindow::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Plage horaire de point')
            ->setEntityLabelInPlural('Plages horaires points de remise')
            ->setDefaultSort(['deliveryPoint' => 'ASC', 'sortOrder' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, static fn (Action $action): Action => $action->setLabel('Ajouter une plage'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('deliveryPoint', 'Point de remise')->setRequired(true);
        yield TextField::new('label', 'Libellé')->setRequired(false)
            ->setHelp('Optionnel. Exemple : Matin, Après-midi, Arrivées du soir.');
        yield ChoiceField::new('weekday', 'Jour')
            ->setChoices(DeliveryPointTimeWindow::getWeekdayChoices())
            ->formatValue(static fn (?int $value, mixed $entity): string => $entity instanceof DeliveryPointTimeWindow ? $entity->getWeekdayLabel() : 'Tous les jours')
            ->setHelp('Laisser “Tous les jours” si la plage est valable chaque jour.');
        yield TimeField::new('startTime', 'Début');
        yield TimeField::new('endTime', 'Fin');
        yield BooleanField::new('isActive', 'Active')->renderAsSwitch(true);
        yield IntegerField::new('sortOrder', 'Ordre')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm()->hideOnIndex();
    }
}
