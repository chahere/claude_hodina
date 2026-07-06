<?php

namespace App\Controller\Admin;

use App\Entity\DeliveryCommune;
use App\Entity\DeliveryPoint;
use App\Entity\DeliveryPointTimeWindow;
use App\Entity\Product;
use App\Entity\ProductDeliveryPoint;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TimeField;
use Symfony\Component\HttpFoundation\RequestStack;

class ProductCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly EntityManagerInterface $adminEntityManager,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Product::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Produit')
            ->setEntityLabelInPlural('Produits')
            ->setDefaultSort(['isFeatured' => 'DESC', 'displayPriority' => 'ASC', 'createdAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        // -------------------------
        // Champs coeur
        // -------------------------
        yield TextField::new('name', 'Nom');

        // Vendeur / Catégorie requis pour éviter produits "orphelins"
        yield AssociationField::new('seller', 'Vendeur')->setRequired(true);
        yield AssociationField::new('category', 'Catégorie')->setRequired(true);

        // -------------------------
        // Images (ProductImage.path = filename)
        // -------------------------
        yield CollectionField::new('images', 'Photos')
            ->useEntryCrudForm(ProductImageCrudController::class)
            ->setTemplatePath('admin/field/product_images_collection.html.twig')
            ->setHelp('Ajoute 1 ou plusieurs images. Les fichiers sont servis depuis /public/uploads/products/.')
            ->setFormTypeOptions([
                'by_reference' => false,
                'attr' => [
                    'data-controller' => 'product-images',
                    'data-product-images-base-path-value' => '/uploads/products/',
                ],
                'row_attr' => [
                    'data-controller' => 'product-images',
                    'data-product-images-base-path-value' => '/uploads/products/',
                ],
            ])
            ->onlyOnForms();

        // -------------------------
        // Prix - SECURISÉ : en euros (decimal), pas en centimes
        // -------------------------
        yield MoneyField::new('producerPrice', 'Prix producteur (€)')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setHelp('Montant demandé par le vendeur. Si vide, Hodina réutilise temporairement l’ancien champ Prix.');

        yield NumberField::new('marginRate', 'Marge produit Hodina (%)')
            ->setNumDecimals(2)
            ->setHelp('Optionnel. Priorité : marge produit > marge vendeur > marge globale.');

        // -------------------------
        // Création produit — champs opérationnels visibles juste après la marge
        // -------------------------
        yield BooleanField::new('isUnlimitedStock', 'Stock illimité')
            ->renderAsSwitch(true)
            ->setHelp('Si activé, le champ Stock est ignoré.')
            ->setFormTypeOption('attr', [
                'data-controller' => 'stock',
                'data-action' => 'change->stock#toggle',
            ])
            ->hideOnIndex();

        yield IntegerField::new('stockQty', 'Stock')
            ->setHelp('Quantité disponible (laisser vide si stock illimité).')
            ->setFormTypeOption('attr', [
                'data-stock-target' => 'stock',
                'min' => 0,
            ])
            ->hideOnIndex();

        // -------------------------
        // Unité de vente (affichage badge en liste)
        // -------------------------
        yield ChoiceField::new('unit', 'Unité de vente')
            ->setChoices(array_flip(Product::getUnitLabels()))
            ->renderAsBadges()
            ->renderExpanded(false)
            ->allowMultipleChoices(false);

        // -------------------------
        // Description
        // -------------------------
        yield TextareaField::new('description', 'Description')
            ->hideOnIndex()
            ->setHelp('Décris le produit : origine, qualité, quantité, etc.');

        // -------------------------
        // Précommande / délais (pilote)
        // -------------------------
        yield BooleanField::new('isPreorder', 'Précommande')
            ->renderAsSwitch(true)
            ->hideOnIndex()
            ->setHelp('Active si le produit nécessite une fabrication avant livraison.');

        yield IntegerField::new('manufacturingDays', 'Jours fabrication')
            ->hideOnIndex()
            ->setHelp('Nombre de jours estimés pour fabriquer (si précommande).')
            ->setFormTypeOption('attr', ['min' => 0]);

        // -------------------------
        // Mode de livraison produit (J5S-A)
        // Placé avant les jours de livraison : c'est un choix métier structurant
        // pour le parcours panier, pas un réglage avancé.
        // -------------------------
        yield ChoiceField::new('deliveryMode', 'Mode de remise au client')
            ->setChoices(array_flip(Product::getDeliveryModeLabels()))
            ->renderAsBadges()
            ->setHelp('Détermine où le client peut récupérer ce produit : adresse classique, point Hodina imposé, ou choix entre les deux. Ce champ pilote le parcours panier ; il est distinct du message de livraison affiché sur la fiche produit.');

        yield IntegerField::new('deliveryDays', 'Jours livraison')
            ->hideOnIndex()
            ->setHelp('Nombre de jours de livraison après validation.')
            ->setFormTypeOption('attr', ['min' => 0]);

        yield IntegerField::new('minimumOrderLeadTimeHours', 'Délai minimum avant remise/livraison (h)')
            ->hideOnIndex()
            ->setHelp('Exemple : 48 signifie que le client doit commander au moins 48h avant la date/heure de remise souhaitée. Laisser vide ou 0 pour aucune contrainte.')
            ->setFormTypeOption('attr', ['min' => 0]);

        // -------------------------
        // Prix historique / statut / ordre catalogue
        // Placés après les champs opérationnels pour simplifier la création produit.
        // -------------------------
        yield MoneyField::new('price', 'Ancien prix / compatibilité (€)')
            ->setCurrency('EUR')
            ->setStoredAsCents(false)
            ->setNumDecimals(2)
            ->setHelp('Champ historique conservé pour compatibilité J5E.')
            ->hideOnIndex();

        yield BooleanField::new('isActive', 'Actif')
            ->renderAsSwitch(true)
            ->setHelp('Si inactif, le produit ne s’affiche pas dans le catalogue.');

        yield FormField::addFieldset('Catalogue — ordre éditorial Hodina')
            ->setHelp('Pilote l’ordre d’affichage par défaut du catalogue, sans modifier les prix ni la livraison.')
            ->collapsible();

        yield BooleanField::new('isFeatured', 'Mettre en tête de sa catégorie')
            ->renderAsSwitch(true)
            ->setHelp('Si coché, ce produit passe devant les produits non cochés de la même catégorie dans l’ordre Hodina.');

        yield IntegerField::new('displayPriority', 'Ordre d’affichage produit')
            ->setHelp('Plus le chiffre est faible, plus le produit remonte dans sa catégorie. Exemple : 0 avant 10.')
            ->setFormTypeOption('attr', ['min' => 0]);

        // -------------------------
        // Promesse livraison produit (J5X-C)
        // -------------------------
        yield FormField::addFieldset('Fiche produit — message de livraison client')
            ->setHelp('J5X-C : configure uniquement le message visible sur la fiche produit. Ce bloc ne calcule pas les frais, ne crée pas de point de remise et ne garantit pas un créneau.')
            ->collapsible();

        yield ChoiceField::new('deliveryPromiseMode', 'Type de message affiché')
            ->setChoices(array_flip(Product::getDeliveryPromiseModeLabels()))
            ->renderAsBadges()
            ->setHelp('Secteur client : le produit suit les passages configurés dans les zones tarifaires. Sur créneau : information indicative pour broche, collier de fleurs, accueil aéroport, cérémonie ou événement.');

        yield TextField::new('deliveryPromiseTitle', 'Titre affiché côté client')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Optionnel. Exemple : Livraison sur créneau, Accueil aéroport, Livraison selon ta commune.');

        yield TextareaField::new('deliveryPromiseDescription', 'Texte affiché côté client')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Texte court visible sur la fiche produit. Ne promets pas une livraison garantie : Hodina confirme le passage ou le créneau selon la disponibilité terrain.');

        yield ChoiceField::new('appointmentDeliveryWeekdays', 'Jours indicatifs sur créneau')
            ->setChoices(array_flip(Product::getWeekdayLabels()))
            ->allowMultipleChoices(true)
            ->renderExpanded(true)
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('À renseigner seulement si le message affiché est “Sur créneau / rendez-vous”. Pour afficher “tous les jours”, coche tous les jours.');

        yield TimeField::new('appointmentTimeWindowStart', 'Début de plage indicative')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Exemple : 08:00. Information affichée au client pour un produit sur créneau ; ce n’est pas une réservation automatique.');

        yield TimeField::new('appointmentTimeWindowEnd', 'Fin de plage indicative')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Exemple : 18:00. Information affichée au client pour cadrer la plage souhaitée ; Hodina confirme ensuite le créneau final.');

        yield TimeField::new('appointmentCutoffTime', 'Heure limite indicative')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Exemple : 10:00. Si vide, l’affichage client utilise 10h par défaut. Cette aide n’ajoute pas de nouvelle validation checkout.');

        yield IntegerField::new('appointmentCutoffDaysBefore', 'Jours avant plage indicative')
            ->hideOnIndex()
            ->setHelp('Exemple : 1 = affichage “commande avant 10h la veille”. Cette règle ne remplace pas le délai minimum J5V-A ni la validation point de remise.')
            ->setFormTypeOption('attr', ['min' => 0]);

        // -------------------------
        // Points de remise rapides (J5S-A-bis)
        // -------------------------
        yield FormField::addFieldset('Avancé — points de remise : associer des points existants')
            ->setHelp('Logistique point de remise. À utiliser pour rattacher ce produit à un point Hodina déjà créé. Distinct du message de livraison J5X-C affiché sur la fiche produit.')
            ->collapsible()
            ->renderCollapsed();

        yield ChoiceField::new('quickExistingDeliveryPointIds', 'Points existants à associer')
            ->setChoices($this->getDeliveryPointChoices())
            ->setRequired(false)
            ->allowMultipleChoices(true)
            ->setFormTypeOption('mapped', false)
            ->setHelp('Sélectionne un ou plusieurs points : barge, aéroport, relais pickup, point vendeur, etc.')
            ->onlyOnForms();

        yield FormField::addFieldset('Avancé — points de remise : créer un nouveau point')
            ->setHelp('Création rapide d’un point Hodina depuis ce produit. À utiliser seulement pour créer un vrai lieu de remise : aéroport, barge, relais, point vendeur. Pour gérer un point existant, utilise le menu Points de remise.')
            ->collapsible()
            ->renderCollapsed();

        yield TextField::new('quickDeliveryPointName', 'Nom du nouveau point de remise')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->setHelp('Exemple : Accueil barge Petite-Terre, Relais pickup Tsoundzou, Accueil passager aéroport Pamandzi.')
            ->onlyOnForms();

        yield ChoiceField::new('quickDeliveryPointDeliveryCommuneId', 'Commune logistique du point')
            ->setChoices($this->getDeliveryCommuneChoices())
            ->setRequired(false)
            ->allowMultipleChoices(false)
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('placeholder', '— Choisir une commune logistique —')
            ->setHelp('Obligatoire pour créer un nouveau point. Hodina déduit automatiquement le nom de commune affiché et le code postal depuis cette commune.')
            ->onlyOnForms();

        yield TextField::new('quickDeliveryPointLine1', 'Adresse terrain')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->setHelp('Adresse courte terrain. Exemple : Accueil passager de l’aéroport de Pamandzi.')
            ->onlyOnForms();

        yield TextField::new('quickDeliveryPointCode', 'Code stable')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->setHelp('Optionnel. Si vide, Hodina génère un code depuis le nom. Exemple : BARGE_PETITE_TERRE.')
            ->onlyOnForms();

        yield ChoiceField::new('quickDeliveryPointType', 'Type')
            ->setChoices(array_flip(DeliveryPoint::getTypeLabels()))
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('placeholder', 'Relais pickup / barge / aéroport...')
            ->setHelp('Type logistique du point de remise.')
            ->onlyOnForms();

        yield TextField::new('quickDeliveryPointLine2', 'Complément')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->hideOnIndex()
            ->onlyOnForms();

        yield TextareaField::new('quickDeliveryPointPublicInstructions', 'Consigne client')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->setHelp('Texte visible côté client dans les prochains lots.')
            ->hideOnIndex()
            ->onlyOnForms();

        yield TextareaField::new('quickDeliveryPointCourierInstructions', 'Consigne livreur')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->setHelp('Texte terrain utile pour Djama dans les prochains lots.')
            ->hideOnIndex()
            ->onlyOnForms();

        yield TextField::new('quickDeliveryPointGpsLatitude', 'Latitude GPS')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->hideOnIndex()
            ->onlyOnForms();

        yield TextField::new('quickDeliveryPointGpsLongitude', 'Longitude GPS')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->hideOnIndex()
            ->onlyOnForms();

        yield FormField::addFieldset('Avancé — points de remise : plages horaires du nouveau point')
            ->setHelp('Uniquement utilisé si tu crées un nouveau point avec “Nom du nouveau point” + “Commune logistique du point”. Pour modifier les plages d’un point existant, utilise le menu “Plages points de remise”.')
            ->collapsible()
            ->renderCollapsed();

        yield TextareaField::new('quickDeliveryPointTimeWindows', 'Plages horaires du nouveau point')
            ->setRequired(false)
            ->setFormTypeOption('mapped', false)
            ->setFormTypeOption('row_attr', [
                'data-controller' => 'delivery-point-windows',
                'data-delivery-point-windows-ready-value' => '1',
            ])
            ->setFormTypeOption('attr', [
                'data-delivery-point-windows-source' => '1',
                'rows' => 2,
                'placeholder' => 'Matin;jours ouvrés;08:00;12:00',
            ])
            ->setHelp('Interface guidée J5Y-A-bis. Le champ technique est remplacé dans le formulaire par des lignes Libellé / Jours concernés / Début / Fin. Si le JavaScript ne charge pas, tu peux encore saisir une plage par ligne au format : Label;jour;début;fin. Pour modifier un point existant, utilise le menu “Plages points de remise”.')
            ->hideOnIndex()
            ->onlyOnForms();

        // -------------------------
        // createdAt : visible mais non éditable
        // -------------------------
        yield DateTimeField::new('createdAt', 'Créé le')->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            $this->sanitizeProduct($entityInstance);
            $this->processQuickDeliveryPointFields($entityManager, $entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Product) {
            $this->sanitizeProduct($entityInstance);
            $this->processQuickDeliveryPointFields($entityManager, $entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * J5S-A-bis — formulaire produit pratique :
     * l'admin peut associer un point existant ou créer un point + ses plages
     * directement depuis l'édition du produit. Aucun impact panier/checkout ici.
     */
    private function processQuickDeliveryPointFields(EntityManagerInterface $entityManager, Product $product): void
    {
        foreach ($this->getSubmittedIntList('quickExistingDeliveryPointIds') as $existingPointId) {
            if ($existingPointId <= 0) {
                continue;
            }

            $existingPoint = $entityManager->getRepository(DeliveryPoint::class)->find($existingPointId);
            if ($existingPoint instanceof DeliveryPoint) {
                $this->linkProductToDeliveryPoint($entityManager, $product, $existingPoint);
                $this->ensureDeliveryPointModeWhenNeeded($product);
            }
        }

        $pointName = $this->getSubmittedString('quickDeliveryPointName');
        if ($pointName === null) {
            return;
        }

        $deliveryCommuneId = $this->getSubmittedInt('quickDeliveryPointDeliveryCommuneId');
        if ($deliveryCommuneId === null || $deliveryCommuneId <= 0) {
            return;
        }

        $deliveryCommune = $entityManager->getRepository(DeliveryCommune::class)->find($deliveryCommuneId);
        if (!$deliveryCommune instanceof DeliveryCommune) {
            return;
        }

        $pointCode = $this->getSubmittedString('quickDeliveryPointCode') ?? self::normalizeQuickCode($pointName);
        $pointCode = self::normalizeQuickCode($pointCode);

        /** @var DeliveryPoint|null $point */
        $point = $entityManager->getRepository(DeliveryPoint::class)->findOneBy(['code' => $pointCode]);

        if (!$point instanceof DeliveryPoint) {
            $point = new DeliveryPoint();
            $point
                ->setName($pointName)
                ->setCode($pointCode)
                ->setType($this->getSubmittedString('quickDeliveryPointType') ?? DeliveryPoint::TYPE_PICKUP_RELAY)
                ->setIsActive(true)
                ->setLine1($this->getSubmittedString('quickDeliveryPointLine1') ?? $pointName)
                ->setLine2($this->getSubmittedString('quickDeliveryPointLine2'))
                ->setPostalCode($deliveryCommune->getPostalCode())
                ->setCommuneName($deliveryCommune->getName())
                ->setDeliveryCommune($deliveryCommune)
                ->setPublicInstructions($this->getSubmittedString('quickDeliveryPointPublicInstructions'))
                ->setCourierInstructions($this->getSubmittedString('quickDeliveryPointCourierInstructions'))
                ->setGpsLatitude($this->getSubmittedString('quickDeliveryPointGpsLatitude'))
                ->setGpsLongitude($this->getSubmittedString('quickDeliveryPointGpsLongitude'));

            $entityManager->persist($point);
        }

        $this->applyQuickTimeWindows($entityManager, $point, $this->getSubmittedString('quickDeliveryPointTimeWindows'));
        $this->linkProductToDeliveryPoint($entityManager, $product, $point);
        $this->ensureDeliveryPointModeWhenNeeded($product);
    }

    private function linkProductToDeliveryPoint(EntityManagerInterface $entityManager, Product $product, DeliveryPoint $point): void
    {
        foreach ($product->getProductDeliveryPoints() as $existingLink) {
            if ($existingLink->getDeliveryPoint() === $point || $existingLink->getDeliveryPoint()->getId() === $point->getId()) {
                $existingLink->setIsActive(true);
                return;
            }
        }

        $storedLink = null;
        if ($product->getId() !== null && $point->getId() !== null) {
            $storedLink = $entityManager->getRepository(ProductDeliveryPoint::class)->findOneBy([
                'product' => $product,
                'deliveryPoint' => $point,
            ]);
        }

        if ($storedLink instanceof ProductDeliveryPoint) {
            $storedLink->setIsActive(true);
            return;
        }

        $link = new ProductDeliveryPoint();
        $link->setDeliveryPoint($point);
        $link->setIsActive(true);
        $link->setSortOrder(($product->getProductDeliveryPoints()->count() + 1) * 10);

        $product->addProductDeliveryPoint($link);
        $entityManager->persist($link);
    }

    private function applyQuickTimeWindows(EntityManagerInterface $entityManager, DeliveryPoint $point, ?string $rawWindows): void
    {
        $rawWindows = $rawWindows !== null && trim($rawWindows) !== ''
            ? $rawWindows
            : "Matin;jours ouvrés;08:00;12:00
Après-midi;jours ouvrés;14:00;18:00";

        foreach (preg_split('/\R+/', trim($rawWindows)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            foreach ($this->parseQuickTimeWindowLine($line) as [$label, $weekday, $startTime, $endTime]) {
                if ($this->deliveryPointAlreadyHasWindow($point, $label, $weekday, $startTime, $endTime)) {
                    continue;
                }

                $timeWindow = new DeliveryPointTimeWindow();
                $timeWindow
                    ->setDeliveryPoint($point)
                    ->setLabel($label)
                    ->setWeekday($weekday)
                    ->setStartTime($startTime)
                    ->setEndTime($endTime)
                    ->setIsActive(true)
                    ->setSortOrder(($point->getTimeWindows()->count() + 1) * 10);

                $point->addTimeWindow($timeWindow);
                $entityManager->persist($timeWindow);
            }
        }
    }

    /**
     * Convertit une ligne issue de l'interface guidée J5Y-A en une ou plusieurs plages réelles.
     *
     * Formats acceptés :
     * - Label;jours ouvrés;08:00;12:00       => lundi à vendredi
     * - Label;jours ouvrables;08:00;12:00    => lundi à samedi
     * - Label;tous les jours;08:00;12:00     => une plage générique tous les jours
     * - Label;3;08:00;12:00                 => mercredi
     * - Label;mercredi;08:00;12:00          => mercredi
     * - Label;08:00;12:00                   => tous les jours, compatibilité historique
     *
     * @return list<array{0: ?string, 1: ?int, 2: \DateTimeImmutable, 3: \DateTimeImmutable}>
     */
    private function parseQuickTimeWindowLine(string $line): array
    {
        $parts = array_values(array_filter(array_map('trim', preg_split('/[;|,]/', $line) ?: []), static fn (string $value): bool => $value !== ''));

        if (count($parts) >= 4) {
            $label = $parts[0];
            $weekdays = $this->expandWeekdayPreset($parts[1]);
            $start = $parts[2];
            $end = $parts[3];
        } elseif (count($parts) === 3) {
            $label = $parts[0];
            $weekdays = [null];
            $start = $parts[1];
            $end = $parts[2];
        } elseif (count($parts) === 2) {
            $label = null;
            $weekdays = [null];
            $start = $parts[0];
            $end = $parts[1];
        } else {
            return [];
        }

        $startTime = self::parseTime($start);
        $endTime = self::parseTime($end);
        if (!$startTime || !$endTime || $startTime >= $endTime || $weekdays === []) {
            return [];
        }

        $windows = [];
        foreach ($weekdays as $weekday) {
            $windows[] = [$label, $weekday, $startTime, $endTime];
        }

        return $windows;
    }

    /** @return list<?int> */
    private function expandWeekdayPreset(string $value): array
    {
        $normalized = self::normalizeTextToken($value);

        if ($normalized === '' || $normalized === '0' || $normalized === 'tous' || $normalized === 'tous les jours' || $normalized === 'all') {
            return [null];
        }

        if (in_array($normalized, ['jours ouvres', 'ouvres', 'lundi vendredi', 'lundi a vendredi', 'business days'], true)) {
            return [1, 2, 3, 4, 5];
        }

        if (in_array($normalized, ['jours ouvrables', 'ouvrables', 'lundi samedi', 'lundi a samedi'], true)) {
            return [1, 2, 3, 4, 5, 6];
        }

        $weekday = $this->normalizeWeekday($value);

        return $weekday === null ? [] : [$weekday];
    }

    private function deliveryPointAlreadyHasWindow(DeliveryPoint $point, ?string $label, ?int $weekday, \DateTimeImmutable $startTime, \DateTimeImmutable $endTime): bool
    {
        foreach ($point->getTimeWindows() as $existingWindow) {
            if (
                $existingWindow->getWeekday() === $weekday
                && $existingWindow->getStartTime()->format('H:i') === $startTime->format('H:i')
                && $existingWindow->getEndTime()->format('H:i') === $endTime->format('H:i')
                && ($existingWindow->getLabel() ?? '') === ($label ?? '')
            ) {
                return true;
            }
        }

        return false;
    }


    /** @return list<int> */
    private function getSubmittedIntList(string $fieldName): array
    {
        $value = $this->getSubmittedValue($fieldName);
        if ($value === null) {
            return [];
        }

        $rawValues = is_array($value) ? $value : [$value];
        $ids = [];

        foreach ($rawValues as $rawValue) {
            if (is_array($rawValue)) {
                continue;
            }

            $rawValue = trim((string) $rawValue);
            if ($rawValue !== '' && ctype_digit($rawValue)) {
                $ids[] = (int) $rawValue;
            }
        }

        return array_values(array_unique($ids));
    }

    private function ensureDeliveryPointModeWhenNeeded(Product $product): void
    {
        if ($product->getDeliveryMode() === Product::DELIVERY_MODE_STANDARD) {
            $product->setDeliveryMode(Product::DELIVERY_MODE_POINT_OPTIONAL);
        }
    }

    private function getSubmittedString(string $fieldName): ?string
    {
        $value = $this->getSubmittedValue($fieldName);
        if ($value === null || is_array($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function getSubmittedInt(string $fieldName): ?int
    {
        $value = $this->getSubmittedString($fieldName);

        return $value !== null && ctype_digit($value) ? (int) $value : null;
    }

    private function getSubmittedValue(string $fieldName): mixed
    {
        $request = $this->requestStack->getCurrentRequest();
        if ($request === null || !$request->isMethod('POST')) {
            return null;
        }

        return $this->findSubmittedValue($request->request->all(), $fieldName);
    }

    private function findSubmittedValue(array $data, string $fieldName): mixed
    {
        foreach ($data as $key => $value) {
            if ($key === $fieldName) {
                return $value;
            }

            if (is_array($value)) {
                $found = $this->findSubmittedValue($value, $fieldName);
                if ($found !== null) {
                    return $found;
                }
            }
        }

        return null;
    }

    /** @return array<string, int> */
    private function getDeliveryPointChoices(): array
    {
        $choices = [];
        $points = $this->adminEntityManager
            ->getRepository(DeliveryPoint::class)
            ->findBy([], ['sortOrder' => 'ASC', 'name' => 'ASC']);

        foreach ($points as $point) {
            if ($point instanceof DeliveryPoint && $point->getId() !== null) {
                $choices[sprintf('%s — %s', $point->getName(), $point->getCode())] = $point->getId();
            }
        }

        return $choices;
    }

    /** @return array<string, int> */
    private function getDeliveryCommuneChoices(): array
    {
        $choices = [];
        $communes = $this->adminEntityManager
            ->getRepository(DeliveryCommune::class)
            ->findBy([], ['name' => 'ASC']);

        foreach ($communes as $commune) {
            if ($commune instanceof DeliveryCommune && $commune->getId() !== null && $commune->isActive()) {
                $choices[sprintf('%s (%s)', $commune->getName(), $commune->getTerritory())] = $commune->getId();
            }
        }

        return $choices;
    }

    private function normalizeWeekday(string $value): ?int
    {
        $value = self::normalizeTextToken($value);
        if ($value === '' || $value === '0' || $value === 'tous' || $value === 'tous les jours') {
            return null;
        }

        if (ctype_digit($value)) {
            $weekday = (int) $value;
            return array_key_exists($weekday, DeliveryPointTimeWindow::getWeekdayLabels()) ? $weekday : null;
        }

        $labels = DeliveryPointTimeWindow::getWeekdayLabels();
        foreach ($labels as $weekday => $label) {
            if (self::normalizeTextToken($label) === $value) {
                return $weekday;
            }
        }

        return null;
    }

    private static function normalizeTextToken(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }

        $value = (string) preg_replace('/[\s_\-]+/', ' ', $value);

        return trim($value);
    }

    private static function parseTime(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if (preg_match('/^\d{1,2}:\d{2}$/', $value)) {
            $value .= ':00';
        }

        $time = \DateTimeImmutable::createFromFormat('!H:i:s', $value);

        return $time instanceof \DateTimeImmutable ? $time : null;
    }

    private static function normalizeQuickCode(string $value): string
    {
        $value = trim($value);
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }

        $value = strtoupper((string) preg_replace('/[^A-Za-z0-9]+/', '_', $value));
        $value = trim($value, '_');

        return $value !== '' ? $value : 'POINT_REMISE_' . date('YMD_HIS');
    }

    private function sanitizeProduct(Product $product): void
    {
        // Stock illimité => stockQty doit être null (DB cohérente)
        if ($product->isUnlimitedStock()) {
            $product->setStockQty(null);
        } else {
            // Si pas illimité et stockQty vide -> on met 0 (évite incohérence)
            if ($product->getStockQty() === null) {
                $product->setStockQty(0);
            }
        }

        // Si pas précommande, manufacturingDays doit être null (ou 0 selon ta DB)
        if (!$product->isPreorder()) {
            $product->setManufacturingDays(null);
        } else {
            if ($product->getManufacturingDays() === null) {
                $product->setManufacturingDays(0);
            }
        }

        // deliveryDays peut rester null si tu le gères autrement, sinon sécurise :
        if ($product->getDeliveryDays() === null) {
            $product->setDeliveryDays(0);
        }
    }
}
