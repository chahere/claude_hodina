<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use App\Entity\SmsLog;
use App\Service\CustomerAnonymizerService;
use App\Service\CustomerPilotCascadeDeleter;

use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class CustomerCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Utilisateur')
            ->setEntityLabelInPlural('Utilisateurs')
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield EmailField::new('email');
        yield TextField::new('firstName');
        yield TextField::new('lastName');
        yield TextField::new('phone');

        yield TextField::new('plainPassword', 'Mot de passe temporaire')
            ->setFormType(PasswordType::class)
            ->setRequired($pageName === Crud::PAGE_NEW)
            ->setHelp($pageName === Crud::PAGE_NEW
                ? 'Obligatoire à la création. Il sera hashé avant enregistrement et ne sera jamais affiché.'
                : 'Optionnel. Renseigne ce champ uniquement pour remplacer le mot de passe de cet utilisateur.')
            ->setFormTypeOptions([
                'required' => $pageName === Crud::PAGE_NEW,
                'attr' => [
                    'autocomplete' => 'new-password',
                    'placeholder' => $pageName === Crud::PAGE_NEW ? 'Mot de passe initial' : 'Laisser vide pour ne pas changer',
                ],
            ])
            ->onlyOnForms();
        yield AssociationField::new('billingAddress', 'Adresse de facturation')
            ->setHelp('Adresse unique utilisée pour la facturation.')
            ->onlyOnForms();

        yield CollectionField::new('addresses', 'Adresses du client')
            ->useEntryCrudForm(AddressCrudController::class)
            ->allowAdd()
            ->allowDelete()
            ->setFormTypeOption('by_reference', false)
            ->setHelp('Le client peut avoir plusieurs adresses. Chaque adresse doit préciser son type : livraison ou facturation.')
            ->onlyOnForms();
        yield ChoiceField::new('roles', 'Rôles')
            ->allowMultipleChoices()
            ->setChoices([
                'Client — accès catalogue, panier et commandes' => 'ROLE_USER',
                'Livreur — accès au dashboard /djama' => 'ROLE_COURIER',
                'Vendeur — compte lié à une fiche vendeur Hodina' => 'ROLE_SELLER',
                'Testeur commerce — peut tester panier et commande pendant un blocage public' => 'ROLE_COMMERCE_TESTER',
                'Administrateur — accès complet au backoffice /ouegnewe' => 'ROLE_ADMIN',
            ])
            ->renderExpanded(false)
            ->setHelp('Sélectionne un ou plusieurs rôles. ROLE_USER est ajouté automatiquement. ROLE_COMMERCE_TESTER permet de tester panier et commande même si les commandes publiques sont bloquées.');
        yield BooleanField::new('isVerified');
        yield BooleanField::new('isActive', 'Compte actif')
            ->setHelp('Passe automatiquement à faux lors de l’anonymisation. Ne pas modifier manuellement.')
            ->hideOnForm();
        yield DateTimeField::new('anonymizedAt', 'Anonymisé le')
            ->hideOnForm()
            ->hideOnIndex();

        yield NumberField::new('courierPayoutCap', 'Plafond rémunération livreur (€)')
            ->setNumDecimals(2)
            ->setHelp('Optionnel, utilisé seulement pour les comptes livreurs. Vide ou 0 = plafond global Hodina. Exemple pilote : 20 €.')
            ->hideOnIndex();

        yield DateTimeField::new('createdAt')->hideOnForm();
    }
    
    public function configureActions(Actions $actions): Actions
    {
        $deletePilot = Action::new('confirmPilotCascadeDelete', 'Supprimer pilote', 'fa fa-trash')
            ->linkToCrudAction('confirmPilotCascadeDelete')
            ->setCssClass('btn btn-danger')
            ->displayIf(static function (Customer $customer): bool {
                return !in_array('ROLE_ADMIN', $customer->getRoles(), true);
            });

        $anonymize = Action::new('confirmAnonymize', 'Anonymiser', 'fa fa-user-secret')
            ->linkToCrudAction('confirmAnonymize')
            ->setCssClass('btn btn-warning')
            ->displayIf(static function (Customer $customer): bool {
                return !in_array('ROLE_ADMIN', $customer->getRoles(), true) && !$customer->isAnonymized();
            });

        $generateResetLink = Action::new('generatePasswordResetLink', 'Lien reset', 'fa fa-key')
            ->linkToCrudAction('generatePasswordResetLink')
            ->setCssClass('btn btn-secondary')
            ->displayIf(static function (Customer $customer): bool {
                return $customer->getEmail() !== null && trim($customer->getEmail()) !== '';
            });

        return $actions
            ->add(Crud::PAGE_INDEX, $generateResetLink)
            ->add(Crud::PAGE_DETAIL, $generateResetLink)
            ->add(Crud::PAGE_INDEX, $anonymize)
            ->add(Crud::PAGE_DETAIL, $anonymize)
            ->add(Crud::PAGE_INDEX, $deletePilot)
            ->add(Crud::PAGE_DETAIL, $deletePilot)
            ->disable(Action::DELETE)
            ->disable(Action::BATCH_DELETE);
    }


    public function persistEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Customer) {
            parent::persistEntity($entityManager, $entityInstance);
            return;
        }

        $this->hashPlainPasswordIfProvided($entityInstance, true);
        parent::persistEntity($entityManager, $entityInstance);
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if (!$entityInstance instanceof Customer) {
            parent::updateEntity($entityManager, $entityInstance);
            return;
        }

        $this->hashPlainPasswordIfProvided($entityInstance, false);
        parent::updateEntity($entityManager, $entityInstance);
    }

    public function generatePasswordResetLink(
        Request $request,
        EntityManagerInterface $entityManager,
        AdminUrlGenerator $adminUrlGenerator,
    ): Response {
        $customer = $this->findCustomerFromRequest($request, $entityManager);

        if (!$customer instanceof Customer) {
            throw $this->createNotFoundException('Utilisateur introuvable.');
        }

        if ($customer->getEmail() === null || trim($customer->getEmail()) === '') {
            $this->addFlash('danger', 'Impossible de générer un lien : cet utilisateur n’a pas d’e-mail.');
            return $this->redirect($this->buildCustomerDetailUrl($adminUrlGenerator, $customer));
        }

        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+45 minutes');

        $customer
            ->setResetPasswordToken($token)
            ->setResetPasswordTokenExpiresAt($expiresAt);

        $resetUrl = $this->generateUrl(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        if (trim($customer->getPhone()) !== '') {
            $smsMessage = sprintf(
                'Gégé %s, voici ton lien Hodina pour réinitialiser ton mot de passe : %s. Ce lien expire dans 45 minutes.',
                trim($customer->getFirstName()) ?: 'toi',
                $resetUrl
            );

            $smsLog = (new SmsLog())
                ->setPhone($customer->getPhone())
                ->setContext('admin_customer_password_reset_link')
                ->setRecipientType('customer')
                ->setMessage($smsMessage)
                ->setStatus(SmsLog::STATUS_SENT)
                ->setProvider('manual_admin_link')
                ->setSentAt(new \DateTimeImmutable());

            $entityManager->persist($smsLog);
        }

        $entityManager->flush();

        $this->addFlash('success', sprintf(
            'Lien de réinitialisation généré pour %s. Expiration : 45 minutes. Lien : %s',
            (string) $customer,
            $resetUrl
        ));

        return $this->redirect($this->buildCustomerDetailUrl($adminUrlGenerator, $customer));
    }

    private function hashPlainPasswordIfProvided(Customer $customer, bool $creation): void
    {
        $plainPassword = trim((string) $customer->getPlainPassword());

        if ($plainPassword === '') {
            if ($creation && ($customer->getPassword() === null || $customer->getPassword() === '')) {
                throw new \InvalidArgumentException('Un mot de passe initial est obligatoire pour créer un utilisateur depuis le backoffice.');
            }

            return;
        }

        if (mb_strlen($plainPassword) < 6) {
            throw new \InvalidArgumentException('Le mot de passe doit contenir au moins 6 caractères.');
        }

        $customer
            ->setPassword($this->passwordHasher->hashPassword($customer, $plainPassword))
            ->setPlainPassword(null);
    }

    public function confirmPilotCascadeDelete(
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerPilotCascadeDeleter $customerPilotCascadeDeleter,
        AdminUrlGenerator $adminUrlGenerator,
    ): Response {
        $customer = $this->findCustomerFromRequest($request, $entityManager);

        if (!$customer instanceof Customer) {
            throw $this->createNotFoundException('Client introuvable.');
        }

        if (in_array('ROLE_ADMIN', $customer->getRoles(), true)) {
            $this->addFlash('danger', 'Impossible de supprimer un compte administrateur.');
            return $this->redirect($this->buildCustomerDetailUrl($adminUrlGenerator, $customer));
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof Customer && $currentUser->getId() === $customer->getId()) {
            $this->addFlash('danger', 'Impossible de supprimer ton propre compte connecté.');
            return $this->redirect($this->buildCustomerDetailUrl($adminUrlGenerator, $customer));
        }

        $preview = $customerPilotCascadeDeleter->preview($customer);

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('pilot_delete_customer_' . $customer->getId(), $token)) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $customerId = $customer->getId();
            $customerLabel = (string) $customer;
            $summary = $customerPilotCascadeDeleter->delete($customer);

            $this->addFlash('success', sprintf(
                'Client pilote #%d supprimé : %d commande(s), %d SMS log(s), %d adresse(s), %d conversation(s) IA, %d paiement(s) livreur.',
                $customerId,
                $summary['orders'],
                $summary['smsLogs'],
                $summary['addresses'],
                $summary['chatbotConversations'],
                $summary['courierPayouts']
            ));

            return $this->redirect($this->buildCustomerIndexUrl($adminUrlGenerator));
        }

        return $this->render('admin/customer/pilot_cascade_delete.html.twig', [
            'customer' => $customer,
            'preview' => $preview,
            'cancelUrl' => $this->buildCustomerDetailUrl($adminUrlGenerator, $customer),
        ]);
    }

    public function confirmAnonymize(
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerAnonymizerService $customerAnonymizerService,
        AdminUrlGenerator $adminUrlGenerator,
    ): Response {
        $customer = $this->findCustomerFromRequest($request, $entityManager);

        if (!$customer instanceof Customer) {
            throw $this->createNotFoundException('Client introuvable.');
        }

        if (in_array('ROLE_ADMIN', $customer->getRoles(), true)) {
            $this->addFlash('danger', 'Impossible d’anonymiser un compte administrateur.');
            return $this->redirect($this->buildCustomerDetailUrl($adminUrlGenerator, $customer));
        }

        $currentUser = $this->getUser();
        if ($currentUser instanceof Customer && $currentUser->getId() === $customer->getId()) {
            $this->addFlash('danger', 'Impossible d’anonymiser ton propre compte connecté.');
            return $this->redirect($this->buildCustomerDetailUrl($adminUrlGenerator, $customer));
        }

        if ($customer->isAnonymized()) {
            $this->addFlash('danger', 'Ce client est déjà anonymisé.');
            return $this->redirect($this->buildCustomerDetailUrl($adminUrlGenerator, $customer));
        }

        $preview = $customerAnonymizerService->preview($customer);

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('anonymize_customer_' . $customer->getId(), $token)) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $customerId = $customer->getId();
            $summary = $customerAnonymizerService->anonymize($customer);

            $this->addFlash('success', sprintf(
                'Client #%d anonymisé : %d adresse(s) supprimée(s). Commandes, tickets support et conversations IA conservés.',
                $customerId,
                $summary['addresses']
            ));

            return $this->redirect($this->buildCustomerIndexUrl($adminUrlGenerator));
        }

        return $this->render('admin/customer/anonymize_confirm.html.twig', [
            'customer' => $customer,
            'preview' => $preview,
            'cancelUrl' => $this->buildCustomerDetailUrl($adminUrlGenerator, $customer),
        ]);
    }

    /**
     * Charge le client directement via entityId (query string), sans dépendre du
     * contexte CRUD d'EasyAdmin : AdminContext::getEntity() peut lever
     * "Cannot get entity outside of a CRUD context" sur certaines actions custom
     * selon la version d'EasyAdminBundle installée.
     */
    private function findCustomerFromRequest(Request $request, EntityManagerInterface $entityManager): ?Customer
    {
        $entityId = $request->query->get('entityId');

        if ($entityId === null || $entityId === '') {
            return null;
        }

        $customer = $entityManager->getRepository(Customer::class)->find($entityId);

        return $customer instanceof Customer ? $customer : null;
    }

    private function buildCustomerDetailUrl(AdminUrlGenerator $adminUrlGenerator, Customer $customer): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($customer->getId())
            ->generateUrl();
    }

    private function buildCustomerIndexUrl(AdminUrlGenerator $adminUrlGenerator): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
    }
}

