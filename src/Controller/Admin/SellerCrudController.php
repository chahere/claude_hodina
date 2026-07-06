<?php

namespace App\Controller\Admin;

use App\Entity\Address;
use App\Entity\Customer;
use App\Entity\DeliveryCommune;
use App\Entity\Seller;
use App\Service\SellerPickupLogisticsSynchronizer;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class SellerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly SellerPickupLogisticsSynchronizer $sellerPickupLogisticsSynchronizer,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Seller::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Vendeur')
            ->setEntityLabelInPlural('Vendeurs')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield FormField::addPanel('Identité du vendeur')->onlyOnForms();
        yield TextField::new('sellerFirstName', 'Prénom')
            ->onlyOnForms()
            ->setRequired(true)
            ->setHelp('Obligatoire : le vendeur est aussi un client Hodina.');
        yield TextField::new('sellerLastName', 'Nom')
            ->onlyOnForms()
            ->setRequired(true)
            ->setHelp('Nom de famille du vendeur/client.');
        yield TextField::new('phone', 'Téléphone')
            ->hideOnIndex()
            ->setRequired(false);
        yield TextField::new('email', 'Email')
            ->hideOnIndex()
            ->setRequired(false)
            ->setHelp('Utilisé pour créer ou rattacher automatiquement le compte client vendeur.');

        yield FormField::addPanel('Structure / affichage commercial')->onlyOnForms();
        yield TextField::new('businessName', 'Nom de structure')
            ->onlyOnForms()
            ->setRequired(false)
            ->setHelp('Optionnel. Exemple : Marché Petite-Terre, Boutique Combo, Ferme Abdallah. Si vide, le portail livreur affiche prénom + nom, et la boutique affiche le nom de famille.');

        yield TextField::new('name', 'Affichage boutique / legacy')
            ->hideOnForm()
            ->setHelp('Champ interne alimenté automatiquement depuis le nom de structure ou le nom de famille.');
        yield TextField::new('businessName', 'Nom de structure')
            ->hideOnForm()
            ->hideOnIndex();
        yield TextField::new('collectionValidationCode', 'Code collecte')
            ->hideOnForm()
            ->hideOnIndex();
        yield TextField::new('courierDisplayName', 'Affichage livreur')
            ->hideOnForm()
            ->hideOnIndex();
        yield TextField::new('publicDisplayName', 'Affichage boutique')
            ->hideOnForm()
            ->hideOnIndex();

        if ($pageName === Crud::PAGE_DETAIL) {
            yield AssociationField::new('customerAccount', 'Compte client vendeur')
                ->setHelp('Compte créé ou rattaché automatiquement depuis le vendeur. Non modifiable directement depuis le formulaire vendeur.');
            yield AssociationField::new('pickupAddress', 'Adresse / point de retrait')
                ->setHelp('Adresse terrain utilisée pour guider le livreur.');
        }

        yield FormField::addPanel('Adresse / point de retrait vendeur')->onlyOnForms();
        yield TextField::new('pickupAddressLine1', 'Adresse de retrait')
            ->onlyOnForms()
            ->setRequired(true)
            ->setHelp('Exemple : maison, atelier, dépôt, portail bleu, bord de route.');
        yield TextField::new('pickupAddressLine2', 'Complément')
            ->onlyOnForms()
            ->setRequired(false);
        yield Field::new('pickupDeliveryCommune', 'Commune de retrait')
            ->onlyOnForms()
            ->setFormType(EntityType::class)
            ->setFormTypeOptions([
                'class' => DeliveryCommune::class,
                'choice_label' => static fn (DeliveryCommune $commune): string => sprintf(
                    '%s — %s — %s',
                    $commune->getName(),
                    $commune->getPostalCode() ?: 'CP non renseigné',
                    $commune->getTerritory(),
                ),
                'placeholder' => 'Choisir une commune de Mayotte',
                'required' => true,
                'query_builder' => static fn ($repository) => $repository->createQueryBuilder('dc')
                    ->andWhere('dc.isActive = :active')
                    ->setParameter('active', true)
                    ->orderBy('dc.name', 'ASC'),
            ])
            ->setRequired(true)
            ->setHelp('Commune issue du seed DeliveryCommune. Le code postal, la commune logistique et la zone sont déduits automatiquement.');
        yield TextareaField::new('pickupAddressNotes', 'Instructions vendeur / accès')
            ->onlyOnForms()
            ->setRequired(false)
            ->setHelp('Exemple : appeler avant d’arriver, portail bleu, dépôt derrière la maison.');
        yield TextareaField::new('pickupAddressCourierNotes', 'Note terrain interne')
            ->onlyOnForms()
            ->setRequired(false)
            ->setHelp('Note interne enrichie par Hodina ou les livreurs.');
        yield TextField::new('pickupAddressGpsLatitude', 'GPS latitude')
            ->onlyOnForms()
            ->setRequired(false);
        yield TextField::new('pickupAddressGpsLongitude', 'GPS longitude')
            ->onlyOnForms()
            ->setRequired(false);
        yield TextField::new('pickupAddressGpsAccuracyMeters', 'Précision GPS (m)')
            ->onlyOnForms()
            ->setRequired(false);

        yield AssociationField::new('deliveryCommune', 'Commune logistique')
            ->hideOnForm()
            ->setHelp('Champ calculé à la sauvegarde depuis l’adresse de retrait. Source de vérité pour les trajets, la barge et les frais.');
        yield AssociationField::new('deliveryZone', 'Zone de livraison')
            ->hideOnForm()
            ->setHelp('Champ calculé à la sauvegarde depuis la commune logistique. Conservé car la colonne historique est obligatoire.');

        yield FormField::addPanel('Sécurité collecte')->onlyOnForms();
        yield TextField::new('collectionValidationCode', 'Code de validation collecte')
            ->onlyOnForms()
            ->setRequired(false)
            ->setHelp('Optionnel. Code fixe connu du vendeur et demandé par le livreur au retrait. Si vide, Hodina génère un code ponctuel par commande et l’envoie par SMS/e-mail au vendeur.');

        yield FormField::addPanel('Paramètres commerciaux')->onlyOnForms();
        yield NumberField::new('marginRate', 'Marge vendeur Hodina (%)')
            ->setNumDecimals(2)
            ->setHelp('Optionnel. Utilisée seulement si le produit n’a pas de marge spécifique.');
        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
    }

    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Seller) {
            $this->prepareSellerBeforeSave($entityManager, $entityInstance);
        }

        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance instanceof Seller) {
            $this->prepareSellerBeforeSave($entityManager, $entityInstance);
        }

        parent::updateEntity($entityManager, $entityInstance);
    }

    private function prepareSellerBeforeSave(EntityManagerInterface $entityManager, Seller $seller): void
    {
        $this->normalizeSellerIdentity($seller);
        $this->ensureSellerCustomerAccount($entityManager, $seller);

        $pickupAddress = $this->sellerPickupLogisticsSynchronizer->createOrUpdatePickupAddressFromSellerForm($seller);
        if ($pickupAddress instanceof Address) {
            $entityManager->persist($pickupAddress);
        }

        $this->synchronizeSellerBeforeSave($seller);
    }

    private function normalizeSellerIdentity(Seller $seller): void
    {
        $firstName = trim((string) $seller->getSellerFirstName());
        $lastName = trim((string) $seller->getSellerLastName());
        $businessName = trim((string) $seller->getBusinessName());

        if ($firstName === '') {
            throw new \DomainException('Le prénom du vendeur est obligatoire car un vendeur est aussi un client Hodina.');
        }

        if ($lastName === '') {
            throw new \DomainException('Le nom du vendeur est obligatoire car un vendeur est aussi un client Hodina.');
        }

        $seller
            ->setSellerFirstName($firstName)
            ->setSellerLastName($lastName)
            ->setBusinessName($businessName !== '' ? $businessName : null)
            ->setContactName(trim(sprintf('%s %s', $firstName, $lastName)))
            ->setName($businessName !== '' ? $businessName : $lastName);
    }

    private function ensureSellerCustomerAccount(EntityManagerInterface $entityManager, Seller $seller): void
    {
        $customer = $seller->getCustomerAccount();

        if (!$customer instanceof Customer && trim((string) $seller->getEmail()) !== '') {
            $existingCustomer = $entityManager->getRepository(Customer::class)->findOneBy([
                'email' => trim((string) $seller->getEmail()),
            ]);

            if ($existingCustomer instanceof Customer) {
                $customer = $existingCustomer;
                $seller->setCustomerAccount($customer);
                $this->addFlash('success', 'Compte client vendeur existant rattaché automatiquement via l’e-mail vendeur.');
            }
        }

        if (!$customer instanceof Customer) {
            $customer = $this->createCustomerAccountForSeller($seller);
            $entityManager->persist($customer);
            $seller->setCustomerAccount($customer);
            $this->addFlash('success', 'Compte client vendeur créé automatiquement. Le vendeur pourra utiliser “mot de passe oublié” pour activer son accès.');
        }

        $this->synchronizeCustomerIdentityFromSeller($customer, $seller);
        $this->ensureSellerRole($customer);
    }

    private function createCustomerAccountForSeller(Seller $seller): Customer
    {
        $customer = new Customer();

        $temporaryPassword = bin2hex(random_bytes(24));
        $customer->setPassword($this->passwordHasher->hashPassword($customer, $temporaryPassword));
        $customer->setIsVerified(false);

        return $customer;
    }

    private function synchronizeCustomerIdentityFromSeller(Customer $customer, Seller $seller): void
    {
        $firstName = trim((string) $seller->getSellerFirstName());
        $lastName = trim((string) $seller->getSellerLastName());
        $sellerPhone = trim((string) $seller->getPhone());
        $sellerEmail = trim((string) $seller->getEmail());

        $customer
            ->setFirstName($firstName)
            ->setLastName($lastName)
            ->setPhone($sellerPhone !== '' ? $sellerPhone : $this->getCustomerPhoneOrDefault($customer));

        if ($sellerEmail !== '') {
            $customer->setEmail($sellerEmail);
        }

        if ($sellerPhone === '') {
            $this->addFlash('warning', 'Le compte client vendeur utilise un téléphone temporaire ou existant. À corriger dès que possible si besoin.');
        }

        if ($sellerEmail === '') {
            $this->addFlash('warning', 'Le compte client vendeur n’a pas d’e-mail. Le vendeur ne pourra pas encore utiliser “mot de passe oublié”.');
        }
    }


    private function getCustomerPhoneOrDefault(Customer $customer): string
    {
        try {
            $phone = trim((string) $customer->getPhone());
        } catch (\Throwable) {
            $phone = '';
        }

        return $phone !== '' ? $phone : '0000000000';
    }

    private function ensureSellerRole(Customer $customer): bool
    {
        $roles = $customer->getRoles();

        if (in_array('ROLE_SELLER', $roles, true)) {
            return false;
        }

        $roles[] = 'ROLE_SELLER';
        $customer->setRoles(array_values(array_unique($roles)));

        return true;
    }

    private function synchronizeSellerBeforeSave(Seller $seller): void
    {
        $result = $this->sellerPickupLogisticsSynchronizer->synchronize($seller, false);

        if ($result['errors'] !== []) {
            throw new \DomainException(implode("\n", $result['errors']));
        }

        foreach ($result['warnings'] as $warning) {
            $this->addFlash('warning', $warning);
        }

        if ($result['changed']) {
            $this->addFlash('success', 'Commune logistique vendeur synchronisée depuis l’adresse de retrait.');
        }
    }
}
