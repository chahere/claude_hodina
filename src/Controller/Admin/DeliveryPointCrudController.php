<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryPoint;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class DeliveryPointCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliveryPoint::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Point de remise')
            ->setEntityLabelInPlural('Points de remise')
            ->setDefaultSort(['sortOrder' => 'ASC', 'name' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, static fn (Action $action): Action => $action->setLabel('Ajouter un point'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom')
            ->setHelp('Exemple : Accueil barge Petite-Terre, Accueil passager aéroport Pamandzi.');
        yield TextField::new('code', 'Code')
            ->setHelp('Code stable en majuscules, sans accent. Exemple : BARGE_PETITE_TERRE.');
        yield ChoiceField::new('type', 'Type')
            ->setChoices(array_flip(DeliveryPoint::getTypeLabels()))
            ->renderAsBadges();
        yield BooleanField::new('isActive', 'Actif')
            ->renderAsSwitch(true);
        yield IntegerField::new('sortOrder', 'Ordre')
            ->hideOnIndex()
            ->setHelp('Plus le nombre est petit, plus le point remonte dans les listes.');

        yield TextField::new('line1', 'Adresse ligne 1')
            ->setHelp('Adresse précise du point. Ce n’est pas une adresse client.');
        yield TextField::new('line2', 'Adresse ligne 2')->setRequired(false)->hideOnIndex();
        yield TextField::new('postalCode', 'Code postal')->setRequired(false);
        yield TextField::new('communeName', 'Commune affichée')
            ->setHelp('Nom affiché au client/livreur. Exemple : Dzaoudzi, Pamandzi.');
        yield AssociationField::new('deliveryCommune', 'Commune logistique')
            ->setRequired(true)
            ->setHelp('Source logistique Hodina utilisée pour rattacher le point à PT/GT et aux calculs futurs.');

        yield NumberField::new('gpsLatitude', 'Latitude GPS')->setNumDecimals(7)->setRequired(false)->hideOnIndex();
        yield NumberField::new('gpsLongitude', 'Longitude GPS')->setNumDecimals(7)->setRequired(false)->hideOnIndex();
        yield IntegerField::new('gpsAccuracyMeters', 'Précision GPS (m)')->setRequired(false)->hideOnIndex();

        yield TextareaField::new('publicInstructions', 'Consigne client')->hideOnIndex()
            ->setHelp('Texte visible par le client : où attendre, point de repère, précaution.');
        yield TextareaField::new('courierInstructions', 'Consigne livreur')->hideOnIndex()
            ->setHelp('Texte interne pour Djama / livreur : accès, stationnement, contact, repère terrain.');

        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->hideOnForm()->hideOnIndex();
    }
}
