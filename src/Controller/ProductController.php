<?php

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\DeliveryPricingZone;
use App\Entity\Product;
use App\Repository\CategoryRepository;
use App\Repository\ProductRepository;
use App\Service\DeliveryCommuneMatcherService;
use App\Service\ProductDeliveryPromiseService;
use App\Service\ProductPricingService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class ProductController extends AbstractController
{
    private const SORT_DEFAULT = '';
    private const SORT_NEWEST = 'newest';
    private const SORT_PRICE_ASC = 'price_asc';
    private const SORT_PRICE_DESC = 'price_desc';

    #[Route('/', name: 'app_home')]
    #[Route('/', name: 'product_catalogue')]
    public function catalogue(
        Request $request,
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        ProductPricingService $pricing,
    ): Response {
        $search = $this->normalizeSearch($request->query->get('q'));
        $categorySlug = $this->normalizeCategorySlug($request->query->get('categorie'));
        $sort = $this->normalizeSort($request->query->get('tri'));
        $selectedCategory = $categorySlug !== '' ? $categoryRepository->findOneActiveBySlug($categorySlug) : null;

        $products = $categorySlug !== '' && !$selectedCategory instanceof Category
            ? []
            : $productRepository->findCatalogueProducts($search, $selectedCategory, $sort);

        $prices = $this->buildPriceMap($products, $pricing);

        if (in_array($sort, [self::SORT_PRICE_ASC, self::SORT_PRICE_DESC], true)) {
            $this->sortProductsByCustomerPrice($products, $prices, $sort);
        }

        $context = [
            'products' => $products,
            'prices' => $prices,
            'categories' => $categoryRepository->findActiveForCatalogue(),
            'selectedCategory' => $selectedCategory,
            'selectedCategorySlug' => $categorySlug,
            'currentSearch' => $search,
            'currentSort' => $sort,
            'sortChoices' => self::getCatalogueSortChoices(),
            'resultCount' => count($products),
        ];

        if ($request->isXmlHttpRequest() || $request->query->getBoolean('fragment')) {
            return $this->render('product/_catalogue_results.html.twig', $context);
        }

        return $this->render('product/catalogue.html.twig', $context);
    }

    #[Route('/catalogue', name: 'product_catalogue_legacy')]
    public function legacyCatalogue(): Response
    {
        return $this->redirectToRoute('product_catalogue', [], Response::HTTP_MOVED_PERMANENTLY);
    }

    #[Route('/produit/{slug}', name: 'product_show')]
    public function show(
        string $slug,
        Request $request,
        ProductRepository $productRepository,
        ProductPricingService $pricing,
        DeliveryCommuneMatcherService $deliveryCommuneMatcher,
        ProductDeliveryPromiseService $productDeliveryPromiseService,
    ): Response {
        $product = $productRepository->findOneBy(['slug' => $slug, 'isActive' => true]);

        if (!$product) {
            throw $this->createNotFoundException('Produit introuvable');
        }

        $selectedPricingZone = $this->resolveSelectedPricingZone($request, $deliveryCommuneMatcher);

        return $this->render('product/show.html.twig', [
            'product' => $product,
            'price' => $pricing->getCustomerPrice($product),
            'productDeliveryPromise' => $productDeliveryPromiseService
                ->buildForProduct($product, $selectedPricingZone)
                ->toArray(),
        ]);
    }

    // (Optionnel) tu peux supprimer cette route /product si tu ne l'utilises pas
    #[Route('/product', name: 'app_product')]
    public function index(): Response
    {
        return $this->render('product/index.html.twig', [
            'controller_name' => 'ProductController',
        ]);
    }

    /** @return array<string, string> */
    private static function getCatalogueSortChoices(): array
    {
        return [
            self::SORT_NEWEST => 'Nouveautés',
            self::SORT_PRICE_ASC => 'Prix croissant',
            self::SORT_PRICE_DESC => 'Prix décroissant',
        ];
    }

    private function resolveSelectedPricingZone(Request $request, DeliveryCommuneMatcherService $deliveryCommuneMatcher): ?DeliveryPricingZone
    {
        $commune = trim((string) $request->query->get('commune', ''));

        if ($commune === '') {
            $user = $this->getUser();
            if ($user instanceof Customer) {
                $commune = trim((string) ($user->getDeliveryAddress()?->getCommune() ?? ''));
            }
        }

        if ($commune === '') {
            return null;
        }

        $deliveryCommune = $deliveryCommuneMatcher->resolveByCommuneName($commune);

        return $deliveryCommune?->getLocalPricingZone();
    }

    private function normalizeSearch(mixed $search): string
    {
        $search = trim((string) $search);
        $search = preg_replace('/\s+/', ' ', $search) ?? $search;

        return mb_substr($search, 0, 80);
    }

    private function normalizeCategorySlug(mixed $categorySlug): string
    {
        $categorySlug = trim((string) $categorySlug);

        return mb_substr($categorySlug, 0, 120);
    }

    private function normalizeSort(mixed $sort): string
    {
        $sort = trim((string) $sort);

        if ($sort === self::SORT_DEFAULT) {
            return self::SORT_DEFAULT;
        }

        return array_key_exists($sort, self::getCatalogueSortChoices()) ? $sort : self::SORT_DEFAULT;
    }

    /**
     * @param list<Product> $products
     * @return array<int, string>
     */
    private function buildPriceMap(array $products, ProductPricingService $pricing): array
    {
        $prices = [];
        foreach ($products as $product) {
            $productId = $product->getId();
            if ($productId !== null) {
                $prices[$productId] = $pricing->getCustomerPrice($product);
            }
        }

        return $prices;
    }

    /**
     * @param list<Product> $products
     * @param array<int, string> $prices
     */
    private function sortProductsByCustomerPrice(array &$products, array $prices, string $sort): void
    {
        usort($products, static function (Product $a, Product $b) use ($prices, $sort): int {
            $aPrice = (float) ($prices[$a->getId() ?? 0] ?? $a->getPrice());
            $bPrice = (float) ($prices[$b->getId() ?? 0] ?? $b->getPrice());

            $comparison = $aPrice <=> $bPrice;
            if ($comparison === 0) {
                $comparison = strcasecmp($a->getCategory()?->getName() ?? '', $b->getCategory()?->getName() ?? '');
            }
            if ($comparison === 0) {
                $comparison = $a->getDisplayPriority() <=> $b->getDisplayPriority();
            }
            if ($comparison === 0) {
                $comparison = strcasecmp($a->getName(), $b->getName());
            }

            return $sort === self::SORT_PRICE_DESC ? -$comparison : $comparison;
        });
    }

    public function __toString(): string
    {
        return 'Catalogue Hodina';
    }
}
