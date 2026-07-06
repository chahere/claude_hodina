<?php

namespace App\Controller\Admin;

use App\Entity\LaunchSubscriber;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class LaunchSubscriberCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return LaunchSubscriber::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('abonné ouverture')
            ->setEntityLabelInPlural('abonnés ouverture')
            ->setPageTitle(Crud::PAGE_INDEX, 'Abonnés ouverture Hodina')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Détail abonné ouverture')
            ->setDefaultSort(['createdAt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->disable(Action::NEW, Action::EDIT, Action::DELETE, Action::BATCH_DELETE)
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::DETAIL, fn (Action $action): Action => $action->setLabel('Voir'))
            ->update(Crud::PAGE_DETAIL, Action::INDEX, fn (Action $action): Action => $action->setLabel('Retour à la liste'));
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield EmailField::new('email', 'E-mail');
        yield TextField::new('source', 'Source');
        yield TextField::new('ipAddress', 'IP')->hideOnIndex();
        yield TextField::new('userAgent', 'Navigateur')->hideOnIndex();
        yield DateTimeField::new('createdAt', 'Inscrit le');
    }
}
