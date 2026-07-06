<?php

namespace App\Controller\Admin;

use App\Entity\SmsLog;
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

        return $actions
            ->disable(Action::NEW)/*, Action::EDIT, Action::DELETE)*/
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->add(Crud::PAGE_INDEX, $sendSms)
            ->add(Crud::PAGE_DETAIL, $sendSms)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, static fn (Action $action): Action => $action->setLabel('Voir'))
            ->update(Crud::PAGE_DETAIL, Action::INDEX, static fn (Action $action): Action => $action->setLabel('Retour aux SMS'));
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
