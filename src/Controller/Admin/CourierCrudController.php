<?php

namespace App\Controller\Admin;

use App\Entity\Customer;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FieldCollection;
use EasyCorp\Bundle\EasyAdminBundle\Collection\FilterCollection;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Dto\EntityDto;
use EasyCorp\Bundle\EasyAdminBundle\Dto\SearchDto;

class CourierCrudController extends CustomerCrudController
{
    public static function getEntityFqcn(): string
    {
        return Customer::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setEntityLabelInSingular('Livreur')
            ->setEntityLabelInPlural('Livreurs')
            ->setPageTitle(Crud::PAGE_INDEX, 'Livreurs')
            ->setPageTitle(Crud::PAGE_DETAIL, 'Livreur')
            ->setPageTitle(Crud::PAGE_EDIT, 'Modifier un livreur')
            ->setPageTitle(Crud::PAGE_NEW, 'Créer un livreur');
    }

    public function createIndexQueryBuilder(
        SearchDto $searchDto,
        EntityDto $entityDto,
        FieldCollection $fields,
        FilterCollection $filters,
    ): QueryBuilder {
        $queryBuilder = parent::createIndexQueryBuilder($searchDto, $entityDto, $fields, $filters);
        $rootAlias = $queryBuilder->getRootAliases()[0] ?? 'entity';

        return $queryBuilder
            ->andWhere(sprintf('%s.roles LIKE :courierRole', $rootAlias))
            ->setParameter('courierRole', '%"ROLE_COURIER"%');
    }
}
