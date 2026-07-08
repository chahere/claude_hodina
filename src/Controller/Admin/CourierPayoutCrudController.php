<?php

namespace App\Controller\Admin;

use App\Entity\CourierPayout;
use App\Service\CourierPayoutService;
use App\Service\CourierPayoutSettingsService;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
final class CourierPayoutCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return CourierPayout::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Rémunération livreur')
            ->setEntityLabelInPlural('Rémunérations livreurs')
            ->setPageTitle(Crud::PAGE_INDEX, 'Rémunérations livreurs')
            ->setDefaultSort(['periodStart' => 'DESC', 'id' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        $generateCurrent = Action::new('generateCurrentPeriod', 'Générer période en cours', 'fa fa-calculator')
            ->createAsGlobalAction()
            ->linkToRoute('admin_courier_payout_generate_current')
            ->setCssClass('btn btn-primary');

        $generatePrevious = Action::new('generatePreviousPeriod', 'Générer période précédente', 'fa fa-clock-rotate-left')
            ->createAsGlobalAction()
            ->linkToRoute('admin_courier_payout_generate_previous')
            ->setCssClass('btn btn-secondary');

        $validate = Action::new('validatePayout', 'Valider', 'fa fa-check')
            ->linkToCrudAction('validatePayout')
            ->setCssClass('btn btn-success')
            ->displayIf(static fn (CourierPayout $payout): bool => $payout->getStatus() === CourierPayout::STATUS_DRAFT);

        $markPaid = Action::new('markPaid', 'Marquer payé', 'fa fa-money-bill-wave')
            ->linkToCrudAction('markPaid')
            ->setCssClass('btn btn-primary')
            ->displayIf(static fn (CourierPayout $payout): bool => in_array($payout->getStatus(), [CourierPayout::STATUS_DRAFT, CourierPayout::STATUS_VALIDATED], true));

        $cancel = Action::new('cancelPayout', 'Annuler', 'fa fa-ban')
            ->linkToCrudAction('cancelPayout')
            ->setCssClass('btn btn-danger')
            ->displayIf(static fn (CourierPayout $payout): bool => $payout->getStatus() !== CourierPayout::STATUS_PAID && $payout->getStatus() !== CourierPayout::STATUS_CANCELED);

        return $actions
            ->add(Crud::PAGE_INDEX, $generateCurrent)
            ->add(Crud::PAGE_INDEX, $generatePrevious)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $validate)
            ->add(Crud::PAGE_INDEX, $markPaid)
            ->add(Crud::PAGE_INDEX, $cancel)
            ->add(Crud::PAGE_DETAIL, $validate)
            ->add(Crud::PAGE_DETAIL, $markPaid)
            ->add(Crud::PAGE_DETAIL, $cancel)
            ->disable(Action::NEW, Action::DELETE)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static fn (Action $action): Action => $action->setLabel('Voir'));
    }

    public function configureFields(string $pageName): iterable
    {
        if ($pageName === Crud::PAGE_EDIT) {
            yield TextField::new('paymentMethod', 'Mode paiement')
                ->setHelp('Exemple : espèces, virement, transfert mobile money.');
            yield TextField::new('paymentReference', 'Référence paiement')
                ->setHelp('Référence libre : reçu, identifiant virement, note comptable.');
            yield TextareaField::new('adminNote', 'Note admin');
            return;
        }

        yield IdField::new('id')->hideOnForm();
        yield AssociationField::new('courier', 'Livreur');
        yield DateField::new('periodStart', 'Début période');
        yield DateField::new('periodEnd', 'Fin période');
        yield DateField::new('paymentDueDate', 'Paiement prévu');
        yield ChoiceField::new('status', 'Statut')->setChoices(array_flip(CourierPayout::getStatusChoices()));
        yield MoneyField::new('totalAmount', 'Montant')->setCurrency('EUR')->setStoredAsCents(false);
        yield IntegerField::new('ordersCount', 'Commandes');
        yield DateTimeField::new('validatedAt', 'Validé le')->hideOnForm();
        yield DateTimeField::new('paidAt', 'Payé le')->hideOnForm();
        yield TextField::new('paymentMethod', 'Mode paiement')->hideOnIndex();
        yield TextField::new('paymentReference', 'Référence paiement')->hideOnIndex();
        yield TextareaField::new('adminNote', 'Note admin')->hideOnIndex();
        yield CollectionField::new('lines', 'Commandes payées')->onlyOnDetail();
        yield DateTimeField::new('createdAt', 'Créé le')->onlyOnDetail();
        yield DateTimeField::new('updatedAt', 'Mis à jour le')->onlyOnDetail();
    }

    #[Route('/ouegnewe/courier-payouts/generate-current', name: 'admin_courier_payout_generate_current')]
    public function generateCurrent(CourierPayoutService $payoutService, CourierPayoutSettingsService $settingsService, AdminUrlGenerator $adminUrlGenerator): Response
    {
        if (!$this->canGenerateCourierPayouts($settingsService, $adminUrlGenerator)) {
            return $this->redirect($this->buildIndexUrl($adminUrlGenerator));
        }

        $period = $payoutService->getCurrentPeriod();
        $result = $payoutService->generateForPeriod($period);

        $this->addGenerationFlash($period['label'], $result);

        return $this->redirect($this->buildIndexUrl($adminUrlGenerator));
    }

    #[Route('/ouegnewe/courier-payouts/generate-previous', name: 'admin_courier_payout_generate_previous')]
    public function generatePrevious(CourierPayoutService $payoutService, CourierPayoutSettingsService $settingsService, AdminUrlGenerator $adminUrlGenerator): Response
    {
        if (!$this->canGenerateCourierPayouts($settingsService, $adminUrlGenerator)) {
            return $this->redirect($this->buildIndexUrl($adminUrlGenerator));
        }

        $period = $payoutService->getPreviousPeriod();
        $result = $payoutService->generateForPeriod($period);

        $this->addGenerationFlash($period['label'], $result);

        return $this->redirect($this->buildIndexUrl($adminUrlGenerator));
    }

    public function validatePayout(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $payout = $this->getPayoutFromRequest($request, $entityManager);

        try {
            $payout->validate();
            $entityManager->flush();
            $this->addFlash('success', sprintf('Rémunération %s validée.', $payout->getPeriodLabel()));
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirect($this->buildDetailUrl($adminUrlGenerator, $payout));
    }

    public function markPaid(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $payout = $this->getPayoutFromRequest($request, $entityManager);

        try {
            $payout->markPaid();
            $entityManager->flush();
            $this->addFlash('success', sprintf('Rémunération %s marquée comme payée.', $payout->getPeriodLabel()));
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirect($this->buildDetailUrl($adminUrlGenerator, $payout));
    }

    public function cancelPayout(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $payout = $this->getPayoutFromRequest($request, $entityManager);

        try {
            $payout->cancel();
            $entityManager->flush();
            $this->addFlash('success', sprintf('Rémunération %s annulée.', $payout->getPeriodLabel()));
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());
        }

        return $this->redirect($this->buildDetailUrl($adminUrlGenerator, $payout));
    }

    /**
     * Charge la rémunération directement via entityId (query string), sans dépendre
     * du contexte CRUD d'EasyAdmin : AdminContext::getEntity() peut lever
     * "Cannot get entity outside of a CRUD context" sur certaines actions custom
     * selon la version d'EasyAdminBundle installée.
     */
    private function getPayoutFromRequest(Request $request, EntityManagerInterface $entityManager): CourierPayout
    {
        $entityId = $request->query->get('entityId');
        $payout = $entityId !== null && $entityId !== '' ? $entityManager->getRepository(CourierPayout::class)->find($entityId) : null;

        if (!$payout instanceof CourierPayout) {
            throw $this->createNotFoundException('Rémunération livreur introuvable.');
        }

        return $payout;
    }

    private function canGenerateCourierPayouts(CourierPayoutSettingsService $settingsService, AdminUrlGenerator $adminUrlGenerator): bool
    {
        if (!$settingsService->isCourierPayoutEnabled()) {
            $this->addFlash('warning', 'Paiements livreurs désactivés dans Réglages > Paiements. Aucune génération lancée.');

            return false;
        }

        if (!$settingsService->isSemiMonthlyFrequency()) {
            $this->addFlash('warning', sprintf(
                'Fréquence paiements livreurs non prise en charge : %s. Fréquence attendue : semi_monthly.',
                $settingsService->getFrequency()
            ));

            return false;
        }

        return true;
    }

    /** @param array{created: int, updated: int, skippedOrders: int, lines: int, payouts: list<CourierPayout>} $result */
    private function addGenerationFlash(string $periodLabel, array $result): void
    {
        if ($result['lines'] === 0) {
            $this->addFlash('info', sprintf('Aucune nouvelle commande livrée à rémunérer pour la période %s. Commandes ignorées : %d.', $periodLabel, $result['skippedOrders']));
            return;
        }

        $this->addFlash('success', sprintf(
            'Période %s générée : %d paiement(s) créé(s), %d mis à jour, %d ligne(s) commande, %d commande(s) ignorée(s).',
            $periodLabel,
            $result['created'],
            $result['updated'],
            $result['lines'],
            $result['skippedOrders']
        ));
    }

    private function buildIndexUrl(AdminUrlGenerator $adminUrlGenerator): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
    }

    private function buildDetailUrl(AdminUrlGenerator $adminUrlGenerator, CourierPayout $payout): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::DETAIL)
            ->setEntityId($payout->getId())
            ->generateUrl();
    }
}
