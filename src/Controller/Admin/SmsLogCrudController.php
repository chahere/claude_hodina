<?php

namespace App\Controller\Admin;

use App\Entity\SmsLog;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SmsLogCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return SmsLog::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('SMS log')
            ->setEntityLabelInPlural('SMS logs')
            ->setPageTitle(Crud::PAGE_INDEX, 'SMS logs')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail SMS log')
            ->setDefaultSort(['createdAt' => 'DESC']);
    }

    public function configureActions(Actions $actions): Actions
    {
        $sendSms = Action::new('sendSmsOnPhone', 'Envoyer le SMS', 'fa fa-paper-plane')
            ->linkToUrl(static fn (SmsLog $smsLog): string => self::buildSmsUrl($smsLog))
            ->displayIf(static fn (SmsLog $smsLog): bool => self::canOpenSmsUrl($smsLog))
            ->setHtmlAttributes([
                'class' => 'btn btn-success',
            ]);

        $clearAll = Action::new('clearAllSmsLogs', 'Vider les SMS logs', 'fa fa-trash')
            ->createAsGlobalAction()
            ->linkToRoute('admin_sms_log_clear')
            ->setCssClass('btn btn-danger');

        return $actions
            ->disable(Action::NEW)/*, Action::EDIT, Action::DELETE)*/
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendSms)
            ->add(Crud::PAGE_DETAIL, $sendSms)
            ->add(Crud::PAGE_INDEX, $clearAll)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static fn (Action $action): Action => $action->setLabel('Voir'))
            ->update(Crud::PAGE_DETAIL, Action::INDEX, static fn (Action $action): Action => $action->setLabel('Retour aux SMS'));
    }

    #[Route('/ouegnewe/sms-logs/vider', name: 'admin_sms_log_clear')]
    public function clearAll(Request $request, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator): Response
    {
        $count = (int) $entityManager->createQuery('SELECT COUNT(s.id) FROM '.SmsLog::class.' s')->getSingleScalarResult();

        if ($request->isMethod('POST')) {
            $token = (string) $request->request->get('_token');
            if (!$this->isCsrfTokenValid('clear_sms_logs', $token)) {
                throw $this->createAccessDeniedException('Jeton CSRF invalide.');
            }

            $entityManager->createQuery('DELETE FROM '.SmsLog::class.' s')->execute();

            $this->addFlash('success', sprintf('%d SMS log(s) supprimé(s).', $count));

            return $this->redirect($this->buildIndexUrl($adminUrlGenerator));
        }

        return $this->render('admin/_clear_all_confirm.html.twig', [
            'title' => 'Vider les SMS logs',
            'entityLabel' => 'SMS log(s)',
            'count' => $count,
            'warningText' => 'Cette action supprime définitivement tous les SMS logs enregistrés, y compris ceux liés à des commandes existantes. Elle est irréversible.',
            'csrfTokenId' => 'clear_sms_logs',
            'cancelUrl' => $this->buildIndexUrl($adminUrlGenerator),
        ]);
    }

    private function buildIndexUrl(AdminUrlGenerator $adminUrlGenerator): string
    {
        return $adminUrlGenerator
            ->unsetAll()
            ->setController(self::class)
            ->setAction(Action::INDEX)
            ->generateUrl();
    }

    private static function canOpenSmsUrl(SmsLog $smsLog): bool
    {
        return self::normalizePhone($smsLog->getPhone()) !== '' && trim($smsLog->getMessage() ?? '') !== '';
    }

    private static function buildSmsUrl(SmsLog $smsLog): string
    {
        $phone = self::normalizePhone($smsLog->getPhone());
        $message = trim($smsLog->getMessage() ?? '');

        if ($phone === '' || $message === '') {
            return '#';
        }

        return sprintf('sms:%s&body=%s', $phone, rawurlencode($message));
    }

    private static function normalizePhone(?string $phone): string
    {
        return preg_replace('/[^+0-9]/', '', trim((string) $phone)) ?? '';
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id', 'ID');
        yield DateTimeField::new('createdAt', 'Créé le');
        yield DateTimeField::new('sentAt', 'Envoyé le');

        yield AssociationField::new('customerOrder', 'Commande');
        yield TextField::new('phone', 'Téléphone');
        yield ChoiceField::new('recipientType', 'Destinataire')->setChoices([
            'Client' => 'customer',
            'Vendeur' => 'seller',
            'Livreur' => 'delivery',
            'Autre' => 'other',
        ]);
        yield ChoiceField::new('status', 'Statut')->setChoices([
            'En attente' => SmsLog::STATUS_PENDING,
            'Envoyé' => SmsLog::STATUS_SENT,
            'Échec' => SmsLog::STATUS_FAILED,
        ]);
        yield TextField::new('provider', 'Provider');
        yield TextField::new('providerMessageId', 'ID provider')->onlyOnDetail();
        yield TextField::new('context', 'Contexte');
        yield TextareaField::new('message', 'Message');
        yield TextareaField::new('errorMessage', 'Erreur')->onlyOnDetail();
    }
}
