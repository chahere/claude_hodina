<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;

class AddressCrudController extends AbstractCrudController
{
    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Adresse')
            ->setEntityLabelInPlural('Adresses')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public static function getEntityFqcn(): string
    {
        return Address::class;
    }

    public function configureFields(string $pageName): iterable
    {
        yield FormField::addPanel('Adresse structurée')
            ->setHelp('Distinguer clairement les adresses de livraison et les adresses de facturation.');

        yield ChoiceField::new('type', 'Type d’adresse')
            ->setChoices([
                'Adresse de livraison' => Address::TYPE_DELIVERY,
                'Adresse de facturation' => Address::TYPE_BILLING,
            ])
            ->setRequired(true)
            ->setHelp('Livraison : commune livrable PT/GT obligatoire. Facturation : zone AUTRE et code postal français à 5 chiffres.');

        yield TextField::new('label', 'Libellé')
            ->setHelp('Exemple : Maison, Travail, Facturation entreprise.');

        yield TextField::new('line1', 'Adresse')
            ->setRequired(true)
            ->setColumns(12)
            ->setFormTypeOption('empty_data', '')
            ->setHelp('Obligatoire. Première ligne de l’adresse : rue, quartier, repère terrain.');

        yield TextField::new('postalCode', 'Code postal')
            ->setRequired(true)
            ->setColumns(4)
            ->setFormTypeOption('empty_data', '')
            ->setHelp('Obligatoire. Format attendu : 5 chiffres. Exemple : 97615 ou 35000.');

        yield TextField::new('commune', 'Commune')
            ->setRequired(true)
            ->setColumns(8)
            ->setFormTypeOption('empty_data', '')
            ->setHelp('Obligatoire. Livraison : commune livrable Hodina. Facturation : commune libre.');

        yield AssociationField::new('addressLocality', 'Localité connue')
            ->setRequired(false)
            ->setColumns(6)
            ->setHelp('Village / quartier / lieu-dit connu par Hodina. Ne remplace pas la commune et ne calcule pas les frais.');

        yield TextField::new('localityText', 'Localité libre')
            ->setRequired(false)
            ->setColumns(6)
            ->setFormTypeOption('empty_data', '')
            ->setHelp('Texte conservé si la localité n’est pas encore référencée. Exemple : quartier, lieu-dit, repère local.');

        yield TextField::new('line2', 'Complément')
            ->hideOnIndex();

        yield AssociationField::new('deliveryZone', 'Zone')
            ->setHelp('Livraison : PT ou GT. Facturation : AUTRE — Autre.');

        yield FormField::addPanel('Position GPS')
            ->setHelp('Facultatif. Utile a Mayotte quand l’adresse terrain ne suffit pas.');

        yield TextField::new('gpsLatitude', 'Latitude')
            ->hideOnIndex()
            ->setHelp('Format decimal, exemple : -12.7801234.');

        yield TextField::new('gpsLongitude', 'Longitude')
            ->hideOnIndex()
            ->setHelp('Format decimal, exemple : 45.2271234.');

        yield TextField::new('gpsAccuracyMeters', 'Precision GPS (m)')
            ->hideOnIndex()
            ->setHelp('Optionnel. Rempli automatiquement depuis le navigateur si disponible.');

        yield TextField::new('gpsMapUrl', 'Lien carte')
            ->onlyOnDetail()
            ->formatValue(static fn (?string $value): string => $value ? sprintf('<a href="%s" target="_blank" rel="noopener">Ouvrir dans Google Maps</a>', htmlspecialchars($value, ENT_QUOTES)) : '-');

        yield TextareaField::new('notes', 'Instructions client')
            ->hideOnIndex()
            ->setHelp('Visible côté admin/livreur. Exemple : près du centre commercial Baobab, portail bleu, appeler en arrivant.');

        yield TextareaField::new('courierNotes', 'Commentaire livreur / terrain')
            ->hideOnIndex()
            ->setHelp('Note interne pour les prochaines livraisons. Non affichée au client dans le pilote.');
    }
}
