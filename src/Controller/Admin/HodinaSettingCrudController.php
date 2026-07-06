<?php

namespace App\Controller\Admin;

use App\Entity\HodinaSetting;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class HodinaSettingCrudController extends AbstractCrudController
{
    protected const GROUP_KEY_FILTER = null;

    public static function getEntityFqcn(): string
    {
        return HodinaSetting::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        $groupLabel = $this->getFilteredGroupLabel();
        $indexTitle = $groupLabel !== null
            ? sprintf('Réglages — %s', $groupLabel)
            : 'Réglages Hodina — tous les paramètres';

        return $crud
            ->setEntityLabelInSingular('réglage Hodina')
            ->setEntityLabelInPlural('réglages Hodina')
            ->setPageTitle(Crud::PAGE_INDEX, $indexTitle)
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail du réglage Hodina')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier le réglage Hodina')
            ->setPageTitle(Crud::PAGE_NEW, 'Ajouter un réglage Hodina')
            ->setDefaultSort(['groupKey' => 'ASC', 'sortOrder' => 'ASC', 'id' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, fn (Action $action): Action => $action->setLabel('Ajouter un réglage'))
            ->update(Crud::PAGE_INDEX, Action::EDIT, fn (Action $action): Action => $action->setLabel('Modifier'))
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action): Action => $action->setLabel('Voir'))
            ->update(Crud::PAGE_DETAIL, Action::EDIT, fn (Action $action): Action => $action->setLabel('Modifier'))
            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn (Action $action): Action => $action->setLabel('Retour aux réglages'))
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_RETURN, fn (Action $action): Action => $action->setLabel('Enregistrer et revenir'))
            ->update(Crud::PAGE_EDIT, Action::SAVE_AND_CONTINUE, fn (Action $action): Action => $action->setLabel('Enregistrer et continuer'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_RETURN, fn (Action $action): Action => $action->setLabel('Créer et revenir'))
            ->update(Crud::PAGE_NEW, Action::SAVE_AND_ADD_ANOTHER, fn (Action $action): Action => $action->setLabel('Créer et ajouter un autre'));
    }

    public function createEntity(string $entityFqcn): HodinaSetting
    {
        $setting = new HodinaSetting();

        if ($this->hasGroupFilter()) {
            $setting->setGroupKey((string) static::GROUP_KEY_FILTER);
        }

        return $setting;
    }

    public function createIndexQueryBuilder(SearchDto $searchDto, EntityDto $entityDto, FieldCollection $fields, FilterCollection $filters): QueryBuilder
    {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);

        if ($this->hasGroupFilter()) {
            $queryBuilder
                ->andWhere('entity.groupKey = :hodinaSettingGroupKey')
                ->setParameter('hodinaSettingGroupKey', static::GROUP_KEY_FILTER);
        }

        return $queryBuilder;
    }

    public function configureFields(string $pageName): iterable
    {
        /** @var HodinaSetting|null $setting */
        $setting = $this->getContext()?->getEntity()?->getInstance();
        $settingKey = $setting instanceof HodinaSetting ? $setting->getSettingKey() : '';
        $isValueEditable = !$setting instanceof HodinaSetting || $setting->isEditable();

        $isDeliveredCommunes = $settingKey === HodinaSetting::KEY_DELIVERED_COMMUNES;
        $isCommerceMode = $settingKey === HodinaSetting::KEY_COMMERCE_MODE;
        $isTimezoneSetting = $this->isTimezoneSetting($settingKey);
        $isBooleanSetting = in_array($settingKey, HodinaSetting::getBooleanSettingKeys(), true);
        $isCourierPayoutFrequency = $settingKey === HodinaSetting::KEY_COURIER_PAYOUT_FREQUENCY;

        yield IdField::new('id')->hideOnForm();

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('groupLabel', 'Groupe');
        } elseif (!$this->hasGroupFilter()) {
            yield ChoiceField::new('groupKey', 'Groupe')
                ->setChoices(HodinaSetting::getGroupChoices())
                ->setHelp('Groupe métier utilisé pour ranger le paramètre dans EasyAdmin.');
        }

        yield IntegerField::new('sortOrder', 'Ordre')
            ->hideOnIndex()
            ->setHelp('Ordre d’affichage dans le groupe. Plus le nombre est petit, plus le paramètre remonte.');

        yield TextField::new('label', 'Paramètre')
            ->setHelp('Nom lisible dans le backoffice. Exemple : Préfixe des numéros de commande.');

        yield TextField::new('settingKey', 'Clé technique')
            ->setHelp('Identifiant stable utilisé par le code. Exemples : order_reference_prefix, commerce_mode. À modifier avec prudence.');

        if ($pageName === Crud::PAGE_INDEX || $pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('displayValue', 'Valeur')
                ->setTemplatePath('admin/field/setting_display_value.html.twig');
        } elseif ($isBooleanSetting) {
            yield BooleanField::new('booleanValue', 'Valeur')
                ->renderAsSwitch(true)
                ->setFormTypeOption('disabled', !$isValueEditable)
                ->setHelp('Interrupteur : activé = 1, désactivé = 0. La valeur technique reste stockée dans hodina_setting.value.');
        } elseif ($isCommerceMode) {
            yield ChoiceField::new('value', 'Valeur')
                ->setChoices([
                    'Ouvert — commandes publiques actives' => 'open',
                    'Préouverture — catalogue visible, commandes publiques bloquées' => 'preopening',
                    'Maintenance — mise à jour production, commandes publiques suspendues' => 'maintenance',
                    'Fermé — suspension manuelle des commandes' => 'closed',
                ])
                ->setFormTypeOption('disabled', !$isValueEditable)
                ->setHelp('Mode commerce global du portail Hodina.');
        } elseif ($isTimezoneSetting) {
            yield ChoiceField::new('value', 'Fuseau horaire par défaut')
                ->setChoices($this->getTimezoneChoices())
                ->setFormTypeOption('disabled', !$isValueEditable)
                ->setHelp('Fuseau utilisé si la commande ne contient pas de fuseau détecté automatiquement. Pour Mayotte : Indian/Mayotte.');
        } elseif ($isDeliveredCommunes) {
            yield CollectionField::new('valueList', 'Communes livrées')
                ->setEntryType(TextType::class)
                ->setEntryIsComplex(false)
                ->allowAdd($isValueEditable)
                ->allowDelete($isValueEditable)
                ->setFormTypeOption('disabled', !$isValueEditable)
                ->setFormTypeOption('entry_options', [
                    'label' => false,
                    'attr' => [
                        'placeholder' => 'Exemple : Dzaoudzi-Labattoir',
                    ],
                ])
                ->setHelp('Ajoute une commune par champ. Utilise le bouton “Ajouter une commune” pour étendre la liste.');
        } elseif ($isCourierPayoutFrequency) {
            yield ChoiceField::new('value', 'Valeur')
                ->setChoices([
                    'Quinzaine — du 1 au 15 puis du 16 à fin de mois' => HodinaSetting::COURIER_PAYOUT_FREQUENCY_SEMI_MONTHLY,
                ])
                ->setFormTypeOption('disabled', !$isValueEditable)
                ->setHelp('Fréquence métier utilisée pour préparer les brouillons de rémunération livreur. Pour le pilote, seule la quinzaine est active.');
        } elseif ($setting instanceof HodinaSetting && $setting->getFieldType() === HodinaSetting::TYPE_EMAIL) {
            yield EmailField::new('value', 'Valeur')
                ->setFormTypeOption('disabled', !$isValueEditable)
                ->setHelp('Adresse e-mail utilisée par Hodina. Exemple : commande@hodina.fr.');
        } elseif ($setting instanceof HodinaSetting && $setting->getFieldType() === HodinaSetting::TYPE_TEXTAREA) {
            yield TextareaField::new('value', 'Valeur')
                ->setHelp('Texte long utilisé par Hodina.')
                ->setFormTypeOption('disabled', !$isValueEditable)
                ->setNumOfRows(4);
        } else {
            yield TextField::new('value', 'Valeur')
                ->setFormTypeOption('disabled', !$isValueEditable)
                ->setHelp('Valeur utilisée par Hodina. Exemple : hodina pour le préfixe des commandes.');
        }

        yield TextareaField::new('help', 'Aide / description')
            ->hideOnIndex()
            ->setNumOfRows(3);

        if ($pageName === Crud::PAGE_NEW) {
            yield ChoiceField::new('fieldType', 'Type de champ')
                ->setChoices([
                    'Texte court' => HodinaSetting::TYPE_TEXT,
                    'Texte long / liste' => HodinaSetting::TYPE_TEXTAREA,
                    'Interrupteur oui/non' => HodinaSetting::TYPE_BOOLEAN,
                    'Liste de choix' => HodinaSetting::TYPE_CHOICE,
                    'Nombre entier' => HodinaSetting::TYPE_INTEGER,
                    'Nombre décimal' => HodinaSetting::TYPE_DECIMAL,
                    'E-mail' => HodinaSetting::TYPE_EMAIL,
                    'URL' => HodinaSetting::TYPE_URL,
                ])
                ->setHelp('Type de saisie attendu pour ce réglage. Les types avancés seront progressivement raccordés aux formulaires spécialisés.');
        } elseif ($pageName === Crud::PAGE_DETAIL) {
            yield TextField::new('fieldType', 'Type de champ');
        }

        yield BooleanField::new('isEditable', 'Modifiable')
            ->hideOnIndex()
            ->renderAsSwitch(true)
            ->setHelp('Désactive la modification de la valeur pour les paramètres système sensibles ou calculés.');

        yield BooleanField::new('isSensitive', 'Sensible')
            ->hideOnIndex()
            ->renderAsSwitch(true)
            ->setHelp('Masque la valeur dans les listes si le réglage contient une donnée sensible.');

        yield DateTimeField::new('updatedAt', 'Dernière mise à jour')
            ->hideOnForm();
    }

    /**
     * Reconnaît les clés de réglage utilisées pour le fuseau horaire par défaut.
     * On accepte plusieurs noms pour rester compatible avec les évolutions J5N-C/J5N-D.
     */
    private function isTimezoneSetting(string $settingKey): bool
    {
        return in_array($settingKey, [
            'local_timezone',
            'app.local_timezone',
            'default_timezone',
            'customer_default_timezone',
        ], true);
    }

    private function hasGroupFilter(): bool
    {
        return static::GROUP_KEY_FILTER !== null;
    }

    private function getFilteredGroupLabel(): ?string
    {
        if (!$this->hasGroupFilter()) {
            return null;
        }

        return HodinaSetting::getGroupLabelForKey((string) static::GROUP_KEY_FILTER);
    }

    /**
     * @return array<string, string>
     */
    private function getTimezoneChoices(): array
    {
        $choices = [];
        $now = new \DateTimeImmutable('now');

        foreach (\DateTimeZone::listIdentifiers() as $timezone) {
            $timezoneObject = new \DateTimeZone($timezone);
            $offset = $now->setTimezone($timezoneObject)->format('P');
            $cityLabel = str_replace(['_', '/'], [' ', ' / '], $timezone);

            $choices[sprintf('(UTC%s) %s', $offset, $cityLabel)] = $timezone;
        }

        uksort($choices, static function (string $left, string $right): int {
            preg_match('/^\(UTC([+-]\d{2}:\d{2})\)/', $left, $leftMatch);
            preg_match('/^\(UTC([+-]\d{2}:\d{2})\)/', $right, $rightMatch);

            $leftOffset = $leftMatch[1] ?? '+00:00';
            $rightOffset = $rightMatch[1] ?? '+00:00';

            return [$leftOffset, $left] <=> [$rightOffset, $right];
        });

        return $choices;
    }
}
