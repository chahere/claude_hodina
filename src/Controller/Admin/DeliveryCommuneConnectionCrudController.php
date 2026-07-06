<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryCommuneConnection;
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
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class DeliveryCommuneConnectionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return DeliveryCommuneConnection::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Liaison logistique')
            ->setEntityLabelInPlural('Liaisons logistiques')
            ->setDefaultSort(['id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $a) => $a->setLabel('Ajouter une liaison'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield AssociationField::new('fromCommune', 'Départ')
            ->setRequired(true)
            ->setHelp('Commune ou point logistique de départ du lien.');

        yield AssociationField::new('toCommune', 'Arrivée')
            ->setRequired(true)
            ->setHelp('Commune ou point logistique d’arrivée du lien.');

        yield ChoiceField::new('linkType', 'Type de lien')
            ->setChoices([
                'Route terrestre (LAND)' => DeliveryCommuneConnection::LINK_TYPE_LAND,
                'Traversée barge (BARGE)' => DeliveryCommuneConnection::LINK_TYPE_BARGE,
            ])
            ->setHelp('LAND = trajet terrestre. BARGE = traversée maritime PT ↔ GT.');

        yield BooleanField::new('isBidirectional', 'Bidirectionnelle')
            ->setHelp('Si activé, le service pourra utiliser aussi le chemin inverse sans créer une deuxième ligne.');

        yield IntegerField::new('hopCount', 'Poids / étapes')
            ->setHelp('Valeur pilote : 1. Permettra plus tard de pondérer certains liens.');

        yield MoneyField::new('customerExtraFee', 'Supplément client spécifique')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(false)
            ->setHelp('Pour LAND : laisser vide pour utiliser le coût global de traversée de commune. Pour BARGE : renseigner ici le coût fixe de traversée maritime ajouté au forfait local. Mettre 0 pour forcer aucun supplément sur cette liaison.');

        yield MoneyField::new('courierExtraPayout', 'Supplément livreur spécifique')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setRequired(false)
            ->setHelp('Pour LAND : laisser vide pour utiliser le supplément livreur global de traversée de commune. Pour BARGE : renseigner si besoin le supplément fixe versé au livreur. Mettre 0 pour forcer aucun supplément sur cette liaison.');

        yield BooleanField::new('isActive', 'Active');
        yield TextareaField::new('internalNote', 'Note interne')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Dernière mise à jour')->hideOnForm()->hideOnIndex();
    }
}
