<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryCommune;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DeliveryCommuneCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliveryCommune::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Commune livrée')
            ->setEntityLabelInPlural('Communes livrées')
            ->setDefaultSort(['name' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $a) => $a->setLabel('Ajouter une commune'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Commune / point logistique')
            ->setHelp('Nom utilisé par Hodina pour la livraison, par exemple Dzaoudzi, Labattoir, Pamandzi, Mamoudzou.');
        yield TextField::new('slug', 'Slug')
            ->setRequired(false)
            ->setHelp('Identifiant technique lisible pour les imports. Exemple : dzaoudzi, labattoir, mamoudzou.');
        yield TextField::new('postalCode', 'Code postal')
            ->setRequired(false)
            ->setHelp('Code postal principal, par exemple 97615 pour Dzaoudzi / Labattoir / Pamandzi.');
        yield TextField::new('inseeCode', 'Code INSEE')
            ->setRequired(false)
            ->setHelp('Code officiel si la ligne correspond à une commune administrative.');
        yield TextField::new('parentInseeCode', 'Code INSEE parent')
            ->setRequired(false)
            ->setHelp('À utiliser pour un point logistique rattaché à une commune administrative, par exemple Labattoir rattaché à Dzaoudzi.');
        yield BooleanField::new('isLogisticsPoint', 'Point logistique')
            ->setHelp('Activé si Hodina peut utiliser cette ligne comme point de départ ou d’arrivée logistique.');
        yield ChoiceField::new('territory', 'Territoire')
            ->setChoices([
                'Petite-Terre (PT)' => DeliveryCommune::TERRITORY_PT,
                'Grande-Terre (GT)' => DeliveryCommune::TERRITORY_GT,
            ])
            ->setHelp('Territoire technique PT/GT conservé pour les règles barge et les compatibilités historiques. Ne pas l’utiliser comme secteur tarifaire fin.');
        yield AssociationField::new('localPricingZone', 'Zone tarifaire locale')
            ->setRequired(true)
            ->setHelp('Forfait de base utilisé pour la commune client. J5W-A permet des zones fines : Mamoudzou, Nord, Centre, Sud, Petite-Terre.');
        yield AssociationField::new('bargePricingZone', 'Zone tarifaire avec barge')
            ->setRequired(true)
            ->setHelp('Champ historique conservé pour compatibilité admin. Pendant le pilote, la barge est portée par les liaisons logistiques BARGE et le forfait de base reste localPricingZone.');
        yield AssociationField::new('neighboringCommunes', 'Anciennes communes voisines')
            ->setRequired(false)
            ->setHelp('Relation J5F conservée pour compatibilité. Pour J5G-B, utiliser plutôt le menu Logistique > Liaisons logistiques afin de distinguer LAND et BARGE.');
        yield BooleanField::new('isActive', 'Active');
        yield TextareaField::new('internalNote', 'Note interne')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Dernière mise à jour')->hideOnForm()->hideOnIndex();
    }
}
