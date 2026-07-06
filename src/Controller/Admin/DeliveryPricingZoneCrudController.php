<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryPricingZone;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class DeliveryPricingZoneCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliveryPricingZone::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Zone tarifaire')
            ->setEntityLabelInPlural('Zones tarifaires')
            ->setDefaultSort(['code' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $a) => $a->setLabel('Ajouter une zone tarifaire'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('name', 'Nom')
            ->setHelp('Exemple : Mamoudzou local, Nord local, Centre local, Sud local, Petite-Terre local. Ces zones ne remplacent pas les territoires PT/GT.');
        yield TextField::new('code', 'Code')
            ->setHelp('Code court unique, par exemple MAMOUDZOU_LOCAL, NORD_LOCAL, CENTRE_LOCAL, SUD_LOCAL. Petite-Terre réutilise PT_LOCAL pour éviter les doublons.');
        yield NumberField::new('customerDeliveryFee', 'Frais livraison client (€)')
            ->setNumDecimals(2)
            ->setHelp('Forfait local payé par le client pour cette zone. J5X-A : PT_LOCAL 12 €, MAMOUDZOU_LOCAL 12 €, CENTRE_LOCAL 17 €, SUD_LOCAL 21 €, NORD_LOCAL 21 €, GT_LOCAL 21 € en fallback technique. Les coûts de liaison LAND/BARGE et multi-vendeurs restent calculés séparément par le service logistique.');
        yield NumberField::new('courierPayout', 'Rémunération livreur (€)')
            ->setNumDecimals(2)
            ->setHelp('Forfait prévu pour le livreur. J5X-A ne modifie pas ce montant : ne pas utiliser un pourcentage du panier pendant le pilote.');
        yield NumberField::new('deliveryMargin', 'Marge livraison Hodina (€)')
            ->setNumDecimals(2)
            ->hideOnForm()
            ->setHelp('Calcul automatique : frais client - rémunération livreur.');
        yield TextField::new('publicLabel', 'Libellé public')
            ->setHelp('Nom affiché au client : Petite-Terre, Mamoudzou, Grande-Terre Nord, Grande-Terre Centre, Grande-Terre Sud. Évite les codes internes.');
        yield TextareaField::new('publicDescription', 'Description publique')
            ->hideOnIndex()
            ->setHelp('Texte court pour expliquer les passages au client, sans promettre une livraison garantie.');
        yield ChoiceField::new('deliveryWeekdays', 'Jours de livraison')
            ->setChoices([
                'Lundi' => 1,
                'Mardi' => 2,
                'Mercredi' => 3,
                'Jeudi' => 4,
                'Vendredi' => 5,
                'Samedi' => 6,
                'Dimanche' => 7,
            ])
            ->allowMultipleChoices()
            ->renderExpanded()
            ->setFormTypeOption('empty_data', [])
            ->setHelp('Jours de passage du secteur. Convention technique : 1=lundi ... 7=dimanche.');
        yield TimeField::new('cutoffTime', 'Heure limite de commande')
            ->setHelp('Exemple : 10:00. Si mercredi est un jour de livraison et J-1 est configuré, le client doit commander avant mardi 10h.');
        yield IntegerField::new('cutoffDaysBefore', 'Jours avant passage')
            ->setHelp('Nombre de jours avant le passage où l’heure limite s’applique. Valeur pilote : 1, donc 10h la veille.');
        yield BooleanField::new('isDeliveryScheduleActive', 'Planning actif')
            ->setHelp('À désactiver pour GT_LOCAL, qui reste un fallback technique et ne doit pas être présenté comme secteur client.');
        yield BooleanField::new('isActive', 'Active');
        yield TextareaField::new('internalNote', 'Note interne')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Dernière mise à jour')->hideOnForm()->hideOnIndex();
    }
}
