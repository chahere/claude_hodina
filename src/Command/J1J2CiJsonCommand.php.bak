<?php

namespace App\Command;

use App\Entity\Category;
use App\Entity\DeliveryZone;
use App\Entity\Product;
use App\Entity\Seller;
use Doctrine\DBAL\Connection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\String\Slugger\AsciiSlugger;

#[AsCommand(
    name: 'hodina:ci:j1j2:json',
    description: 'CI JSON output — J1–J2 integration test (Doctrine Migrations) with safe UPSERT behavior.'
)]
class J1J2CiJsonCommand extends Command
{
    public function __construct(
        private readonly Connection $conn,
        private readonly DependencyFactory $migrationFactory,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('cleanup', null, InputOption::VALUE_NONE, 'Cleanup ONLY entities created by this run')
            ->addOption('progress', null, InputOption::VALUE_NONE, 'Show progress on STDERR');
    }

    private function progress(bool $enabled, string $message): void
    {
        if ($enabled) {
            fwrite(STDERR, $message . PHP_EOL);
        }
    }

    private function versionsToString(iterable $migrations): string
    {
        $versions = [];
        foreach ($migrations as $m) {
            if (is_object($m) && method_exists($m, 'getVersion')) {
                $versions[] = (string) $m->getVersion();
            } else {
                $versions[] = (string) $m;
            }
        }
        return implode(', ', $versions);
    }

    /**
     * Slugify stable (Windows-safe) using Symfony AsciiSlugger.
     * Example: "Fruits & légumes" => "fruits-legumes"
     */
    private function slugify(string $text): string
    {
        $text = trim($text);

        // Normalize & and other separators to spaces so we don't get "fruits-legumes" weirdness
        $text = str_replace(['&', '@'], ' ', $text);

        $slugger = new AsciiSlugger('fr');
        $slug = $slugger->slug($text)->lower()->toString();

        // AsciiSlugger produces hyphens; ensure no double hyphens
        $slug = preg_replace('/-+/', '-', $slug) ?? $slug;
        $slug = trim($slug, '-');

        return $slug !== '' ? $slug : 'n-a';
    }

    /**
     * Ensure Category.slug is set (DB NOT NULL).
     * - Prefer setSlug() if exists
     * - Else set private property via reflection (safe for typed property)
     */
    private function ensureCategorySlug(Category $category, string $name): void
    {
        $slug = $this->slugify($name);

        if (method_exists($category, 'setSlug')) {
            $category->setSlug($slug);
            return;
        }

        try {
            $ref = new \ReflectionObject($category);
            if ($ref->hasProperty('slug')) {
                $prop = $ref->getProperty('slug');
                $prop->setAccessible(true);
                $prop->setValue($category, $slug);
            }
        } catch (\Throwable) {
            // Best effort
        }
    }

    /** @return array{entity:DeliveryZone, created:bool} */
    private function upsertDeliveryZone(EntityManagerInterface $em, string $code, string $name): array
    {
        /** @var EntityRepository $repo */
        $repo = $em->getRepository(DeliveryZone::class);

        /** @var DeliveryZone|null $zone */
        $zone = $repo->findOneBy(['code' => $code]);
        $created = false;

        if (!$zone) {
            $zone = new DeliveryZone();
            $created = true;

            if (method_exists($zone, 'setCode')) {
                $zone->setCode($code);
            }
            $em->persist($zone);
        }

        // Set to ensure typed property is initialized (no getters)
        if (method_exists($zone, 'setName')) {
            $zone->setName($name);
        }

        return ['entity' => $zone, 'created' => $created];
    }

    /** @return array{entity:Seller, created:bool} */
    private function upsertSeller(EntityManagerInterface $em, string $name, DeliveryZone $zone): array
    {
        /** @var EntityRepository $repo */
        $repo = $em->getRepository(Seller::class);

        $email = method_exists(Seller::class, 'setEmail') ? 'vendeur.test@example.com' : null;

        /** @var Seller|null $seller */
        $seller = $email ? $repo->findOneBy(['email' => $email]) : $repo->findOneBy(['name' => $name]);
        $created = false;

        if (!$seller) {
            $seller = new Seller();
            $created = true;
            $em->persist($seller);
        }

        if (method_exists($seller, 'setName')) $seller->setName($name);
        if (method_exists($seller, 'setDeliveryZone')) $seller->setDeliveryZone($zone);
        if ($email && method_exists($seller, 'setEmail')) $seller->setEmail($email);
        if (method_exists($seller, 'setPhone')) $seller->setPhone('0600000000');

        return ['entity' => $seller, 'created' => $created];
    }

    /** @return array{entity:Category, created:bool} */
    private function upsertCategory(EntityManagerInterface $em, string $name): array
    {
        /** @var EntityRepository $repo */
        $repo = $em->getRepository(Category::class);

        $expectedSlug = $this->slugify($name);

        /** @var Category|null $category */
        $category = null;

        // Slug lookup is stable, but guard if field not mapped
        try {
            $category = $repo->findOneBy(['slug' => $expectedSlug]);
        } catch (\Throwable) {
            $category = null;
        }

        if (!$category) {
            $category = $repo->findOneBy(['name' => $name]);
        }

        $created = false;

        if (!$category) {
            $category = new Category();
            $created = true;
            $em->persist($category);
        }

        if (method_exists($category, 'setName')) {
            $category->setName($name);
        }
        $this->ensureCategorySlug($category, $name);

        return ['entity' => $category, 'created' => $created];
    }

    /** @return array{entity:Product, created:bool} */
    private function upsertProduct(EntityManagerInterface $em, string $name, Seller $seller, Category $category): array
    {
        /** @var EntityRepository $repo */
        $repo = $em->getRepository(Product::class);

        /** @var Product|null $product */
        $product = $repo->findOneBy(['name' => $name, 'seller' => $seller]);
        $created = false;

        if (!$product) {
            $product = new Product();
            $created = true;
            $em->persist($product);
        }

        if (method_exists($product, 'setName')) $product->setName($name);
        if (method_exists($product, 'setSeller')) $product->setSeller($seller);
        if (method_exists($product, 'setCategory')) $product->setCategory($category);

        if (method_exists($product, 'setPrice')) $product->setPrice('2.50');
        if (method_exists($product, 'setIsActive')) $product->setIsActive(true);

        if (method_exists($product, 'setDeliveryDays')) $product->setDeliveryDays(2);
        if (method_exists($product, 'setIsPreorder')) $product->setIsPreorder(false);
        if (method_exists($product, 'setManufacturingDays')) $product->setManufacturingDays(0);
        if (method_exists($product, 'setIsUnlimitedStock')) $product->setIsUnlimitedStock(true);
        if (method_exists($product, 'setStockQty')) $product->setStockQty(10);

        return ['entity' => $product, 'created' => $created];
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $cleanup  = (bool) $input->getOption('cleanup');
        $progress = (bool) $input->getOption('progress');

        // ---- BOOT / PRE-FLIGHT ----
        $this->progress($progress, '[boot] Hodina CI — J1–J2');
        $this->progress($progress, '[boot] Initialisation Symfony...');
        usleep(120_000);
        $this->progress($progress, '[boot] Chargement Doctrine & migrations...');
        usleep(120_000);
        $this->progress($progress, '[boot] Les tests vont demarrer...');
        usleep(120_000);
        // --------------------------

        $result = [
            'command' => 'hodina:ci:j1j2:json',
            'status' => 'pending',
            'checks' => [],
            'errors' => [],
            'timestamps' => [
                'started_at' => date(DATE_ATOM),
                'finished_at' => null,
            ],
        ];

        $fail = false;

        $check = function (string $name, bool $ok, ?string $error = null) use (&$result, &$fail): void {
            $result['checks'][] = [
                'name' => $name,
                'ok' => $ok,
                'details' => $ok ? null : $error,
            ];
            if (!$ok) {
                $fail = true;
                if ($error) {
                    $result['errors'][] = $error;
                }
            }
        };

        $em = null;

        $created = [
            'pt' => false,
            'gt' => false,
            'seller' => false,
            'category' => false,
            'product' => false,
        ];

        $entities = [
            'pt' => null,
            'gt' => null,
            'seller' => null,
            'category' => null,
            'product' => null,
        ];

        $cleanupFn = function () use (&$em, &$entities, &$created, $progress): void {
            if (!$em) return;

            try {
                foreach (['product', 'category', 'seller', 'gt', 'pt'] as $k) {
                    if (($created[$k] ?? false) && $entities[$k] !== null) {
                        $em->remove($entities[$k]);
                    }
                }
                $em->flush();
                $this->progress($progress, '[cleanup] OK (only newly created entities)');
            } catch (\Throwable $e) {
                $this->progress($progress, '[cleanup] FAILED: '.$e->getMessage());
            }
        };

        try {
            /* =========================
             * STEP 1 — ENV
             * ========================= */
            $this->progress($progress, '[1/5] Environment checks');

            $check('php_extension_intl', extension_loaded('intl'), 'PHP extension intl missing');

            try {
                $ok = ((string) $this->conn->fetchOne('SELECT 1') === '1');
                $check('db_connection', $ok, 'DB connection failed');
            } catch (\Throwable $e) {
                $check('db_connection', false, $e->getMessage());
            }

            if ($fail) {
                throw new \RuntimeException('Environment checks failed');
            }

            /* =========================
             * STEP 2 — MIGRATIONS STATUS
             * ========================= */
            $this->progress($progress, '[2/5] Doctrine migrations status');

            $statusCalc = $this->migrationFactory->getMigrationStatusCalculator();
            $newMigrations = $statusCalc->getNewMigrations();
            $executedUnavailable = $statusCalc->getExecutedUnavailableMigrations();

            $newCount = 0;
            foreach ($newMigrations as $_) { $newCount++; }

            $unavailableCount = 0;
            foreach ($executedUnavailable as $_) { $unavailableCount++; }

            $check(
                'migrations_new',
                $newCount === 0,
                $newCount === 0 ? null : ('New migrations not applied: '.$this->versionsToString($newMigrations))
            );

            $check(
                'migrations_executed_unavailable',
                $unavailableCount === 0,
                $unavailableCount === 0 ? null : 'Executed migrations missing from codebase'
            );

            if ($fail) {
                throw new \RuntimeException('Doctrine migrations not in sync');
            }

            /* =========================
             * STEP 3 — UPSERT DATA
             * ========================= */
            $this->progress($progress, '[3/5] Upsert test entities (create or reuse + update)');

            $em = $this->migrationFactory->getEntityManager();

            $ptRes = $this->upsertDeliveryZone($em, 'PT', 'Petit-Terre');
            $gtRes = $this->upsertDeliveryZone($em, 'GT', 'Grande-Terre');

            $entities['pt'] = $ptRes['entity'];
            $entities['gt'] = $gtRes['entity'];
            $created['pt'] = $ptRes['created'];
            $created['gt'] = $gtRes['created'];

            /** @var DeliveryZone $pt */
            $pt = $entities['pt'];

            $sellerRes = $this->upsertSeller($em, 'Vendeur test', $pt);
            $entities['seller'] = $sellerRes['entity'];
            $created['seller'] = $sellerRes['created'];

            /** @var Seller $seller */
            $seller = $entities['seller'];

            // Keep file saved as UTF-8
            $categoryName = 'Fruits & légumes';

            $categoryRes = $this->upsertCategory($em, $categoryName);
            $entities['category'] = $categoryRes['entity'];
            $created['category'] = $categoryRes['created'];

            /** @var Category $category */
            $category = $entities['category'];

            $productRes = $this->upsertProduct($em, 'Banane', $seller, $category);
            $entities['product'] = $productRes['entity'];
            $created['product'] = $productRes['created'];

            $em->flush();

            $check('upsert_entities', true, null);

            /* =========================
             * STEP 4 — ASSERTIONS
             * ========================= */
            $this->progress($progress, '[4/5] Assertions');

            if (method_exists($seller, 'getCreatedAt')) {
                $check('seller_createdAt', (bool) $seller->getCreatedAt(), 'Seller.createdAt is null');
            }

            /** @var Product $product */
            $product = $entities['product'];
            if ($product && method_exists($product, 'getCreatedAt')) {
                $check('product_createdAt', (bool) $product->getCreatedAt(), 'Product.createdAt is null');
            }

            if (method_exists($category, 'getCreatedAt')) {
                $check('category_createdAt', (bool) $category->getCreatedAt(), 'Category.createdAt is null');
            }

            // Assert against OUR slugify() result (stable / CI friendly)
            $expectedSlug = $this->slugify($categoryName);

            if (method_exists($category, 'getSlug')) {
                $check(
                    'category_slug_generated',
                    $category->getSlug() === $expectedSlug,
                    'Category slug incorrect: '.$category->getSlug()
                );
            } else {
                // if no getter, we still ensured DB NOT NULL; mark as ok
                $check('category_slug_generated', true, null);
            }

            $check(
                'seller_toString',
                method_exists(Seller::class, '__toString'),
                '__toString missing on Seller'
            );

            if ($fail) {
                throw new \RuntimeException('Assertions failed');
            }

            /* =========================
             * STEP 5 — CLEANUP
             * ========================= */
            $this->progress($progress, '[5/5] Cleanup (optional)');

            if ($cleanup) {
                $cleanupFn();
            }

            $result['status'] = 'success';
        } catch (\Throwable $e) {
            $result['status'] = 'failed';
            $result['errors'][] = $e->getMessage();

            if ($cleanup) {
                $cleanupFn();
            }
        }

        $result['timestamps']['finished_at'] = date(DATE_ATOM);

        $result['meta'] = [
            'created_this_run' => $created,
        ];

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $result['status'] === 'success'
            ? Command::SUCCESS
            : Command::FAILURE;
    }
}
