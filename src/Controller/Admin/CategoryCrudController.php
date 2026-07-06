<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class CategoryCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Category::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setEntityLabelInSingular('Catégorie')
            ->setEntityLabelInPlural('Catégories')
            ->setDefaultSort(['isFeatured' => 'DESC', 'displayOrder' => 'ASC', 'name' => 'ASC'])
            ->showEntityActionsInlined();
    }

    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')->hideOnForm();

        yield TextField::new('name', 'Nom');
        yield TextField::new('slug', 'Slug')->hideOnForm();

        yield BooleanField::new('isActive', 'Visible catalogue')
            ->renderAsSwitch(true)
            ->setHelp('Si désactivée, la catégorie disparaît des filtres publics et ses produits ne sont pas listés dans le catalogue.');

        yield BooleanField::new('isFeatured', 'Mettre en tête du catalogue')
            ->renderAsSwitch(true)
            ->setHelp('Si coché, cette catégorie passe devant les catégories non cochées dans l’ordre Hodina du catalogue.');

        yield IntegerField::new('displayOrder', 'Ordre d’affichage catégorie')
            ->setHelp('Plus le chiffre est faible, plus la catégorie remonte. Exemple : 0 avant 10.')
            ->setFormTypeOption('attr', ['min' => 0]);

        yield TextareaField::new('publicDescription', 'Description publique')
            ->setRequired(false)
            ->hideOnIndex()
            ->setHelp('Texte court optionnel pour présenter la catégorie côté client.');

        yield DateTimeField::new('createdAt', 'Créé le')
            ->hideOnForm();
    }
}
