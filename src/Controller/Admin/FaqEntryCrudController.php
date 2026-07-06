<?php

namespace App\Controller\Admin;

use App\Entity\FaqEntry;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

final class FaqEntryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return FaqEntry::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Question FAQ')
            ->setEntityLabelInPlural('FAQ Hodina')
            ->setDefaultSort(['displayOrder' => 'ASC', 'id' => 'ASC'])
            ->setHelp('index', 'Contenu institutionnel Hodina injecté au chatbot IA et affiché comme FAQ. Seules les entrées actives sont utilisées.');
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();
        yield TextField::new('question');
        yield TextareaField::new('answer', 'Réponse')->setNumOfRows(6);
        yield BooleanField::new('isActive', 'Active')->renderAsSwitch(true);
        yield IntegerField::new('displayOrder', 'Ordre d’affichage')
            ->setHelp('Plus le nombre est petit, plus la question remonte.');
        yield DateTimeField::new('createdAt', 'Créée le')->hideOnForm();
        yield DateTimeField::new('updatedAt', 'Mise à jour le')->hideOnForm()->onlyOnDetail();
    }
}
