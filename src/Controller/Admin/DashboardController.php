<?php

namespace App\Controller\Admin;

use App\Entity\Category;
use App\Entity\Customer;
use App\Entity\CourierPayout;
use App\Entity\CourierPayoutLine;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderFeedback;
use App\Entity\CustomerSignup;
use App\Entity\DeliveryCommune;
use App\Entity\DeliveryCommuneConnection;
use App\Entity\DeliveryPoint;
use App\Entity\DeliveryPointTimeWindow;
use App\Entity\DeliveryPricingZone;
use App\Entity\DeliveryZone;
use App\Entity\EmailLog;
use App\Entity\FaqEntry;
use App\Entity\HodinaSetting;
use App\Entity\LaunchSubscriber;
use App\Entity\OrderItem;
use App\Entity\Product;
use App\Entity\ProductDeliveryPoint;
use App\Entity\Seller;
use App\Entity\SmsLog;
use App\Entity\SupportTicket;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class DashboardController extends AbstractDashboardController
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly AdminUrlGenerator $adminUrlGenerator,
    ) {
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            ->addJsFile('js/easyadmin-stock.js')
            ->addJsFile('js/hodina-easyadmin-fr.js')
            ->addAssetMapperEntry('admin');
    }

    /**
     * Entrée backoffice.
     * IMPORTANT : index() doit rester sans argument (signature parent).
     */
    #[Route('/ouegnewe', name: 'backoffice')]
    public function index(): Response
    {
        return $this->redirectToRoute('backoffice_dashboard');
    }

    /**
     * Tableau de bord admin : indicateurs rapides + commandes récentes.
     */
    #[Route('/ouegnewe/dashboard', name: 'backoffice_dashboard')]
    public function dashboard(): Response
    {
        $sellerCount = $this->em->getRepository(Seller::class)->count([]);
        $categoryCount = $this->em->getRepository(Category::class)->count([]);
        $productCount = $this->em->getRepository(Product::class)->count([]);
        $orderCount = $this->em->getRepository(CustomerOrder::class)->count([]);
        $pendingOrderCount = $this->em->getRepository(CustomerOrder::class)->count([
            'status' => CustomerOrder::STATUS_PENDING_VALIDATION,
        ]);
        $activeOrderCount = $this->em->getRepository(CustomerOrder::class)->count([
            'status' => CustomerOrder::STATUS_CONFIRMED,
        ]) + $this->em->getRepository(CustomerOrder::class)->count([
            'status' => CustomerOrder::STATUS_PREPARING,
        ]) + $this->em->getRepository(CustomerOrder::class)->count([
            'status' => CustomerOrder::STATUS_READY_FOR_PICKUP,
        ]) + $this->em->getRepository(CustomerOrder::class)->count([
            'status' => CustomerOrder::STATUS_PICKED_UP,
        ]) + $this->em->getRepository(CustomerOrder::class)->count([
            'status' => CustomerOrder::STATUS_OUT_FOR_DELIVERY,
        ]);
        $deliveredOrderCount = $this->em->getRepository(CustomerOrder::class)->count([
            'status' => CustomerOrder::STATUS_DELIVERED,
        ]);

        $gmv = $this->em->createQueryBuilder()
            ->select('COALESCE(SUM(o.total), 0)')
            ->from(CustomerOrder::class, 'o')
            ->where('o.status != :canceled')
            ->setParameter('canceled', CustomerOrder::STATUS_CANCELED)
            ->getQuery()
            ->getSingleScalarResult();

        $recentOrders = $this->em->getRepository(CustomerOrder::class)->findBy(
            [],
            ['createdAt' => 'DESC'],
            6
        );

        $orderUrls = [];
        foreach ($recentOrders as $order) {
            $orderUrls[$order->getId()] = [
                'detail' => $this->buildCrudUrl(CustomerOrderCrudController::class, Action::DETAIL, $order->getId()),
                'operationalSheet' => $this->buildCrudUrl(CustomerOrderCrudController::class, 'operationalSheet', $order->getId()),
            ];
        }

        return $this->render('admin/dashboard.html.twig', [
            'stats' => [
                'sellers' => $sellerCount,
                'categories' => $categoryCount,
                'products' => $productCount,
                'orders' => $orderCount,
                'pendingOrders' => $pendingOrderCount,
                'activeOrders' => $activeOrderCount,
                'deliveredOrders' => $deliveredOrderCount,
                'gmv' => $gmv,
            ],
            'recentOrders' => $recentOrders,
            'orderUrls' => $orderUrls,
            'urls' => [
                'orders' => $this->buildCrudUrl(CustomerOrderCrudController::class, Action::INDEX),
                'products' => $this->buildCrudUrl(ProductCrudController::class, Action::INDEX),
                'sellers' => $this->buildCrudUrl(SellerCrudController::class, Action::INDEX),
                'settings' => $this->buildCrudUrl(HodinaSettingCrudController::class, Action::INDEX),
            ],
            'statusLabels' => self::getStatusLabels(),
        ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Hodina — Backoffice')
            ->setFaviconPath('favicon.ico');
    }

    public function configureCrud(): Crud
    {
        return Crud::new()
            ->showEntityActionsInlined()
            ->setDefaultSort(['id' => 'DESC']);
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToRoute('Tableau de bord', 'fa fa-home', 'backoffice_dashboard');
        
        yield MenuItem::section('Réglages');
        yield MenuItem::linkToCrud('Tous les paramètres', 'fa fa-sliders-h', HodinaSetting::class)
            ->setController(HodinaSettingCrudController::class);
        yield MenuItem::linkToCrud('Général', 'fa fa-cog', HodinaSetting::class)
            ->setController(HodinaSettingGeneralCrudController::class);
        yield MenuItem::linkToCrud('Commerce & commandes', 'fa fa-store-alt', HodinaSetting::class)
            ->setController(HodinaSettingCommerceCrudController::class);
        yield MenuItem::linkToCrud('Livraison & logistique', 'fa fa-truck', HodinaSetting::class)
            ->setController(HodinaSettingLogisticsCrudController::class);
        yield MenuItem::linkToCrud('Notifications', 'fa fa-bell', HodinaSetting::class)
            ->setController(HodinaSettingNotificationsCrudController::class);
        yield MenuItem::linkToCrud('Branding e-mail', 'fa fa-envelope-open-text', HodinaSetting::class)
            ->setController(HodinaSettingEmailBrandingCrudController::class);
        yield MenuItem::linkToCrud('Paiements', 'fa fa-coins', HodinaSetting::class)
            ->setController(HodinaSettingPaymentsCrudController::class);
        yield MenuItem::linkToCrud('Technique / maintenance', 'fa fa-tools', HodinaSetting::class)
            ->setController(HodinaSettingTechnicalCrudController::class);
        yield MenuItem::linkToRoute('Initialiser préouverture', 'fa fa-hourglass-start', 'admin_sales_opening_init');

        yield MenuItem::section('Logistique');
        yield MenuItem::linkToCrud('Zones de livraison', 'fa fa-map', DeliveryZone::class);
        yield MenuItem::linkToCrud('Zones tarifaires', 'fa fa-euro-sign', DeliveryPricingZone::class)
            ->setController(DeliveryPricingZoneCrudController::class);
        yield MenuItem::linkToCrud('Communes livrées', 'fa fa-map-marker-alt', DeliveryCommune::class)
            ->setController(DeliveryCommuneCrudController::class);
        yield MenuItem::linkToCrud('Localités d’adresse', 'fa fa-map-pin', AddressLocality::class)
            ->setController(AddressLocalityCrudController::class);
        yield MenuItem::linkToCrud('Liaisons logistiques', 'fa fa-route', DeliveryCommuneConnection::class)
            ->setController(DeliveryCommuneConnectionCrudController::class);
        yield MenuItem::linkToCrud('Points de remise', 'fa fa-location-dot', DeliveryPoint::class)
            ->setController(DeliveryPointCrudController::class);
        yield MenuItem::linkToCrud('Plages points de remise', 'fa fa-clock', DeliveryPointTimeWindow::class)
            ->setController(DeliveryPointTimeWindowCrudController::class);

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Catégories', 'fa fa-tags', Category::class);
        yield MenuItem::linkToCrud('Produits', 'fa fa-box', Product::class);
        yield MenuItem::linkToCrud('Produits ↔ points de remise', 'fa fa-link', ProductDeliveryPoint::class)
            ->setController(ProductDeliveryPointCrudController::class);

        yield MenuItem::section('Commandes');
        yield MenuItem::linkToCrud('Commandes clients', 'fa fa-shopping-cart', CustomerOrder::class)
            ->setController(CustomerOrderCrudController::class);
        yield MenuItem::linkToCrud('Retours clients', 'fa fa-comment-dots', CustomerOrderFeedback::class)
            ->setController(CustomerOrderFeedbackCrudController::class);
        yield MenuItem::linkToCrud('Lignes de commande', 'fa fa-list', OrderItem::class)
            ->setController(OrderItemCrudController::class);

        yield MenuItem::section('Clients');
        yield MenuItem::linkToCrud('Clients', 'fa fa-users', Customer::class)
            ->setController(CustomerCrudController::class);

        yield MenuItem::section('Vendeurs');
        yield MenuItem::linkToCrud('Vendeurs', 'fa fa-store', Seller::class);

        yield MenuItem::section('Livreurs');
        yield MenuItem::linkToCrud('Livreurs', 'fa fa-motorcycle', Customer::class)
            ->setController(CourierCrudController::class);
        yield MenuItem::linkToCrud('Rémunérations livreurs', 'fa fa-money-bill-wave', CourierPayout::class)
            ->setController(CourierPayoutCrudController::class);
        yield MenuItem::linkToCrud('Lignes rémunération', 'fa fa-receipt', CourierPayoutLine::class)
            ->setController(CourierPayoutLineCrudController::class);

        yield MenuItem::section('Support');
        yield MenuItem::linkToCrud('Tickets support', 'fa fa-life-ring', SupportTicket::class)
            ->setController(SupportTicketCrudController::class);
        yield MenuItem::linkToCrud('FAQ', 'fa fa-question-circle', FaqEntry::class)
            ->setController(FaqEntryCrudController::class);

        yield MenuItem::section('Logs');
        yield MenuItem::linkToCrud('Adhésions clients', 'fas fa-user-check', CustomerSignup::class);
        yield MenuItem::linkToCrud('Abonnés ouverture', 'fas fa-envelope-open-text', LaunchSubscriber::class)
            ->setController(LaunchSubscriberCrudController::class);
        yield MenuItem::linkToCrud('SMS (logs)', 'fas fa-comment', SmsLog::class);
        yield MenuItem::linkToCrud('E-mails (logs)', 'fas fa-envelope', EmailLog::class)
            ->setController(EmailLogCrudController::class);


    }

    private function buildCrudUrl(string $controller, string $action, ?int $entityId = null): string
    {
        $urlGenerator = $this->adminUrlGenerator
            ->unsetAll()
            ->setController($controller)
            ->setAction($action);

        if ($entityId !== null) {
            $urlGenerator->setEntityId($entityId);
        }

        return $urlGenerator->generateUrl();
    }

    /**
     * @return array<string, string>
     */
    private static function getStatusLabels(): array
    {
        return [
            CustomerOrder::STATUS_DRAFT => 'Brouillon',
            CustomerOrder::STATUS_PENDING_VALIDATION => 'En validation admin',
            CustomerOrder::STATUS_CONFIRMED => 'Confirmée',
            CustomerOrder::STATUS_PREPARING => 'En préparation',
            CustomerOrder::STATUS_READY_FOR_PICKUP => 'Prête pour livraison',
            CustomerOrder::STATUS_PICKED_UP => 'Prise en charge',
            CustomerOrder::STATUS_OUT_FOR_DELIVERY => 'En cours de livraison',
            CustomerOrder::STATUS_DELIVERED => 'Livrée',
            CustomerOrder::STATUS_CANCELED => 'Annulée',
        ];
    }
}
