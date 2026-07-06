<?php

namespace App\Repository;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Product>
 */
class ProductRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Product::class);
    }

    /**
     * Catalogue public Hodina.
     *
     * @return list<Product>
     */
    public function findCatalogueProducts(?string $search = null, ?Category $category = null, string $sort = ''): array
    {
        $queryBuilder = $this->createQueryBuilder('p')
            ->select('DISTINCT p, s, c, img')
            ->innerJoin('p.seller', 's')
            ->innerJoin('p.category', 'c')
            ->leftJoin('p.images', 'img')
            ->andWhere('p.isActive = :active')
            ->andWhere('c.isActive = :active')
            ->setParameter('active', true);

        $search = $search !== null ? trim($search) : '';
        if ($search !== '') {
            $queryBuilder
                ->andWhere("LOWER(p.name) LIKE :search OR LOWER(COALESCE(p.description, '')) LIKE :search OR LOWER(c.name) LIKE :search OR LOWER(s.name) LIKE :search OR LOWER(COALESCE(s.businessName, '')) LIKE :search OR LOWER(COALESCE(s.contactName, '')) LIKE :search")
                ->setParameter('search', '%' . mb_strtolower($search, 'UTF-8') . '%');
        }

        if ($category instanceof Category) {
            $queryBuilder
                ->andWhere('p.category = :category')
                ->setParameter('category', $category);
        }

        match ($sort) {
            'newest' => $queryBuilder
                ->orderBy('p.createdAt', 'DESC')
                ->addOrderBy('p.displayPriority', 'ASC')
                ->addOrderBy('p.name', 'ASC'),
            'price_asc', 'price_desc' => $queryBuilder
                ->orderBy('p.name', 'ASC'),
            default => $queryBuilder
                ->orderBy('c.isFeatured', 'DESC')
                ->addOrderBy('c.displayOrder', 'ASC')
                ->addOrderBy('c.name', 'ASC')
                ->addOrderBy('p.isFeatured', 'DESC')
                ->addOrderBy('p.displayPriority', 'ASC')
                ->addOrderBy('p.createdAt', 'DESC')
                ->addOrderBy('p.name', 'ASC'),
        };

        /** @var list<Product> $products */
        $products = $queryBuilder->getQuery()->getResult();

        return $products;
    }
}
