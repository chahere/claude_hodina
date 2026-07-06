<?php

namespace App\Controller\Courier;

use App\Entity\Address;
use App\Entity\CourierPayout;
use App\Entity\CourierPayoutLine;
use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\OrderItem;
use App\Entity\Seller;
use App\Service\CustomerDeliveryCodeService;
use App\Service\CustomerOrderWorkflowService;
use App\Service\CustomerOrderNotificationService;
use App\Service\CourierPayoutService;
use App\Service\SellerCollectionCodeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_COURIER')]
final class CourierDashboardController extends AbstractController
{
    private const DEFAULT_TIMEZONE = 'Indian/Mayotte';

    #[Route('/djama', name: 'courier_dashboard', methods: ['GET'])]
    public function index(EntityManagerInterface $entityManager, CourierPayoutService $courierPayoutService): Response
    {
        $courier = $this->getCourier();
        $repository = $entityManager->getRepository(CustomerOrder::class);

        $availableOrders = $repository
            ->createQueryBuilder('o')
            ->leftJoin('o.customer', 'customer')->addSelect('customer')
            ->leftJoin('o.deliveryAddress', 'deliveryAddress')->addSelect('deliveryAddress')
            ->leftJoin('o.deliveryZone', 'deliveryZone')->addSelect('deliveryZone')
            ->leftJoin('o.items', 'items')->addSelect('items')
            ->leftJoin('items.product', 'product')->addSelect('product')
            ->leftJoin('items.seller', 'seller')->addSelect('seller')
            ->leftJoin('seller.deliveryCommune', 'sellerDeliveryCommune')->addSelect('sellerDeliveryCommune')
            ->leftJoin('seller.deliveryZone', 'sellerDeliveryZone')->addSelect('sellerDeliveryZone')
            ->leftJoin('seller.customerAccount', 'sellerCustomerAccount')->addSelect('sellerCustomerAccount')
            ->leftJoin('seller.pickupAddress', 'sellerPickupAddress')->addSelect('sellerPickupAddress')
            ->leftJoin('sellerCustomerAccount.deliveryAddress', 'sellerCustomerDeliveryAddress')->addSelect('sellerCustomerDeliveryAddress')
            ->andWhere('o.status = :status')
            ->andWhere('o.assignedCourier IS NULL')
            ->setParameter('status', CustomerOrder::STATUS_READY_FOR_PICKUP)
            ->orderBy('o.readyAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();

        $currentOrders = $repository
            ->createQueryBuilder('o')
            ->leftJoin('o.customer', 'customer')->addSelect('customer')
            ->leftJoin('o.deliveryAddress', 'deliveryAddress')->addSelect('deliveryAddress')
            ->leftJoin('o.deliveryZone', 'deliveryZone')->addSelect('deliveryZone')
            ->leftJoin('o.items', 'items')->addSelect('items')
            ->leftJoin('items.product', 'product')->addSelect('product')
            ->leftJoin('items.seller', 'seller')->addSelect('seller')
            ->leftJoin('seller.deliveryCommune', 'sellerDeliveryCommune')->addSelect('sellerDeliveryCommune')
            ->leftJoin('seller.deliveryZone', 'sellerDeliveryZone')->addSelect('sellerDeliveryZone')
            ->leftJoin('seller.customerAccount', 'sellerCustomerAccount')->addSelect('sellerCustomerAccount')
            ->leftJoin('seller.pickupAddress', 'sellerPickupAddress')->addSelect('sellerPickupAddress')
            ->leftJoin('sellerCustomerAccount.deliveryAddress', 'sellerCustomerDeliveryAddress')->addSelect('sellerCustomerDeliveryAddress')
            ->andWhere('o.status IN (:statuses)')
            ->andWhere('o.assignedCourier = :courier')
            ->setParameter('statuses', [
                CustomerOrder::STATUS_PICKED_UP,
                CustomerOrder::STATUS_OUT_FOR_DELIVERY,
            ])
            ->setParameter('courier', $courier)
            ->orderBy('o.courierAssignedAt', 'ASC')
            ->addOrderBy('o.outForDeliveryAt', 'ASC')
            ->addOrderBy('o.id', 'ASC')
            ->getQuery()
            ->getResult();

        $deliveredSince = (new \DateTimeImmutable('today'))->modify('-6 days');
        $deliveredWeekOrders = $repository
            ->createQueryBuilder('o')
            ->leftJoin('o.customer', 'customer')->addSelect('customer')
            ->leftJoin('o.deliveryAddress', 'deliveryAddress')->addSelect('deliveryAddress')
            ->leftJoin('o.deliveryZone', 'deliveryZone')->addSelect('deliveryZone')
            ->leftJoin('o.items', 'items')->addSelect('items')
            ->leftJoin('items.product', 'product')->addSelect('product')
            ->leftJoin('items.seller', 'seller')->addSelect('seller')
            ->leftJoin('seller.deliveryCommune', 'sellerDeliveryCommune')->addSelect('sellerDeliveryCommune')
            ->leftJoin('seller.deliveryZone', 'sellerDeliveryZone')->addSelect('sellerDeliveryZone')
            ->leftJoin('seller.customerAccount', 'sellerCustomerAccount')->addSelect('sellerCustomerAccount')
            ->leftJoin('seller.pickupAddress', 'sellerPickupAddress')->addSelect('sellerPickupAddress')
            ->leftJoin('sellerCustomerAccount.deliveryAddress', 'sellerCustomerDeliveryAddress')->addSelect('sellerCustomerDeliveryAddress')
            ->andWhere('o.status = :status')
            ->andWhere('o.assignedCourier = :courier')
            ->andWhere('o.deliveredAt >= :deliveredSince')
            ->setParameter('status', CustomerOrder::STATUS_DELIVERED)
            ->setParameter('courier', $courier)
            ->setParameter('deliveredSince', $deliveredSince)
            ->orderBy('o.deliveredAt', 'DESC')
            ->addOrderBy('o.id', 'DESC')
            ->getQuery()
            ->getResult();

        $deliveredWeekTotal = array_reduce(
            $deliveredWeekOrders,
            fn (float $total, CustomerOrder $order): float => $total + $this->getCourierPayoutForOrder($order),
            0.0
        );

        $pendingPayouts = $courierPayoutService->findPayoutsForCourier($courier, [
            CourierPayout::STATUS_DRAFT,
            CourierPayout::STATUS_VALIDATED,
        ], 6);
        $paidPayouts = $courierPayoutService->findPayoutsForCourier($courier, [
            CourierPayout::STATUS_PAID,
        ], 8);

        return $this->render('courier/dashboard.html.twig', [
            'courier' => $courier,
            'availableOrderCards' => $this->buildOrderCards($availableOrders),
            'currentOrderCards' => $this->buildOrderCards($currentOrders),
            'deliveredWeekOrderCards' => $this->buildOrderCards($deliveredWeekOrders),
            'deliveredWeekTotal' => $deliveredWeekTotal,
            'currentPayoutEstimate' => $courierPayoutService->buildCurrentEstimateForCourier($courier),
            'pendingPayoutCards' => $this->buildPayoutCards($pendingPayouts),
            'paidPayoutCards' => $this->buildPayoutCards($paidPayouts),
        ]);
    }

    #[Route('/djama/commande/{id}/prendre', name: 'courier_order_take', methods: ['POST'])]
    public function take(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerOrderWorkflowService $workflow
    ): Response {
        $order = $entityManager->getRepository(CustomerOrder::class)->find($id);

        if (!$order instanceof CustomerOrder) {
            return $this->courierActionResponse($request, 'danger', 'Commande introuvable.', false, Response::HTTP_NOT_FOUND);
        }

        if (!$this->isCsrfTokenValid('courier_take_' . $order->getId(), (string) $request->request->get('_token'))) {
            return $this->courierActionResponse($request, 'danger', 'Jeton de sécurité invalide.', false, Response::HTTP_FORBIDDEN);
        }

        try {
            $workflow->takeForDelivery($order, $this->getCourier());

            return $this->courierActionResponse(
                $request,
                'success',
                sprintf('Commande %s prise en charge. Valide maintenant les collectes vendeur avant de démarrer la livraison client.', $this->getOrderLabel($order))
            );
        } catch (\DomainException $exception) {
            return $this->courierActionResponse($request, 'warning', $exception->getMessage(), false, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }


    #[Route('/djama/commande/{id}/demarrer-livraison', name: 'courier_order_start_delivery', methods: ['POST'])]
    public function startDelivery(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerOrderWorkflowService $workflow,
        CustomerDeliveryCodeService $customerDeliveryCodeService
    ): Response {
        $order = $entityManager->getRepository(CustomerOrder::class)->find($id);

        if (!$order instanceof CustomerOrder) {
            return $this->courierActionResponse($request, 'danger', 'Commande introuvable.', false, Response::HTTP_NOT_FOUND);
        }

        if (!$this->isCsrfTokenValid('courier_start_delivery_' . $order->getId(), (string) $request->request->get('_token'))) {
            return $this->courierActionResponse($request, 'danger', 'Jeton de sécurité invalide.', false, Response::HTTP_FORBIDDEN);
        }

        $courier = $this->getCourier();

        try {
            $workflow->startDelivery($order, $courier);
        } catch (\DomainException $exception) {
            return $this->courierActionResponse($request, 'warning', $exception->getMessage(), false, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        try {
            $result = $customerDeliveryCodeService->generateAndSendForStartedDelivery($order, $courier);

            return $this->courierActionResponse($request, 'success', $result['message']);
        } catch (\DomainException $exception) {
            return $this->courierActionResponse(
                $request,
                'warning',
                sprintf('Commande %s passée en cours de livraison, mais le code client n’a pas pu être envoyé : %s', $this->getOrderLabel($order), $exception->getMessage()),
                false,
                Response::HTTP_UNPROCESSABLE_ENTITY
            );
        }
    }


    #[Route('/djama/commande/{orderId}/collecte-vendeur/{sellerId}', name: 'courier_order_seller_collection_validate', methods: ['POST'])]
    public function validateSellerCollection(
        int $orderId,
        int $sellerId,
        Request $request,
        EntityManagerInterface $entityManager,
        SellerCollectionCodeService $sellerCollectionCodeService,
        CustomerOrderNotificationService $orderNotificationService
    ): Response {
        $order = $entityManager->getRepository(CustomerOrder::class)->find($orderId);
        $seller = $entityManager->getRepository(Seller::class)->find($sellerId);
        $courier = $this->getCourier();

        if (!$order instanceof CustomerOrder || !$seller instanceof Seller || !$order->containsSeller($seller)) {
            return $this->courierActionResponse($request, 'danger', 'Collecte vendeur introuvable pour cette commande.', false, Response::HTTP_NOT_FOUND);
        }

        if (!$this->isCsrfTokenValid('courier_collect_seller_' . $order->getId() . '_' . $seller->getId(), (string) $request->request->get('_token'))) {
            return $this->courierActionResponse($request, 'danger', 'Jeton de sécurité invalide.', false, Response::HTTP_FORBIDDEN);
        }

        try {
            $result = $sellerCollectionCodeService->validateOrSendCode(
                $order,
                $seller,
                $courier,
                $request->request->get('collection_code') !== null ? (string) $request->request->get('collection_code') : null,
                $this->normalizeCourierNote($request->request->get('collection_note'))
            );

            if ($result['status'] === SellerCollectionCodeService::RESULT_COLLECTED && $order->areAllSellerCollectionsDone()) {
                $orderNotificationService->notifySellerCollectionsCompleted($order);

                return $this->courierActionResponse(
                    $request,
                    'success',
                    sprintf('%s Tous les vendeurs de la commande %s sont collectés. Le client est informé que les produits sont collectés.', $result['message'], $this->getOrderLabel($order))
                );
            }

            return $this->courierActionResponse(
                $request,
                $result['status'] === SellerCollectionCodeService::RESULT_CODE_SENT ? 'info' : 'success',
                $result['message']
            );
        } catch (\DomainException $exception) {
            return $this->courierActionResponse($request, 'warning', $exception->getMessage(), false, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }


    #[Route('/djama/commande/{id}/note-adresse', name: 'courier_order_address_note', methods: ['POST'])]
    public function updateAddressNote(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        $order = $entityManager->getRepository(CustomerOrder::class)->find($id);
        $courier = $this->getCourier();

        if (!$order instanceof CustomerOrder) {
            return $this->courierActionResponse($request, 'danger', 'Commande introuvable.', false, Response::HTTP_NOT_FOUND);
        }

        if (!$this->isCsrfTokenValid('courier_address_note_' . $order->getId(), (string) $request->request->get('_token'))) {
            return $this->courierActionResponse($request, 'danger', 'Jeton de sécurité invalide.', false, Response::HTTP_FORBIDDEN);
        }

        if (!$this->canUpdateAddressNote($order, $courier)) {
            return $this->courierActionResponse($request, 'warning', 'Tu ne peux pas modifier la note terrain de cette commande.', false, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $address = $order->getDeliveryAddress();
        if (!$address instanceof Address) {
            return $this->courierActionResponse($request, 'warning', 'Adresse de livraison introuvable pour cette commande.', false, Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $note = $this->normalizeCourierNote($request->request->get('courier_note'));
        $address->setCourierNotes($note);
        $order->setDeliveryAddressCourierNotes($note);

        $entityManager->flush();

        return $this->courierActionResponse($request, 'success', 'Commentaire terrain enregistré pour les prochaines livraisons.');
    }

    #[Route('/djama/commande/{id}/livree', name: 'courier_order_delivered', methods: ['POST'])]
    public function delivered(
        int $id,
        Request $request,
        EntityManagerInterface $entityManager,
        CustomerOrderWorkflowService $workflow,
        CustomerDeliveryCodeService $customerDeliveryCodeService
    ): Response {
        $order = $entityManager->getRepository(CustomerOrder::class)->find($id);

        if (!$order instanceof CustomerOrder) {
            return $this->courierActionResponse($request, 'danger', 'Commande introuvable.', false, Response::HTTP_NOT_FOUND);
        }

        if (!$this->isCsrfTokenValid('courier_deliver_' . $order->getId(), (string) $request->request->get('_token'))) {
            return $this->courierActionResponse($request, 'danger', 'Jeton de sécurité invalide.', false, Response::HTTP_FORBIDDEN);
        }

        $courier = $this->getCourier();
        $submittedCode = $request->request->get('delivery_code') !== null ? (string) $request->request->get('delivery_code') : null;

        try {
            $result = $customerDeliveryCodeService->validateOrResendCode($order, $courier, $submittedCode);

            if ($result['status'] === CustomerDeliveryCodeService::RESULT_CODE_SENT) {
                return $this->courierActionResponse($request, 'info', $result['message']);
            }

            $workflow->markDeliveredByCourier($order, $courier);
            $customerDeliveryCodeService->markValidatedAndClearCode($order);

            return $this->courierActionResponse($request, 'success', sprintf('%s Commande %s marquée comme livrée.', $result['message'], $this->getOrderLabel($order)));
        } catch (\DomainException $exception) {
            return $this->courierActionResponse($request, 'warning', $exception->getMessage(), false, Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }


    private function courierActionResponse(Request $request, string $flashType, string $message, bool $ok = true, int $statusCode = Response::HTTP_OK): Response
    {
        $this->addFlash($flashType, $message);

        if ($this->isDjamaAjaxRequest($request)) {
            return new JsonResponse([
                'ok' => $ok,
                'message' => $message,
                'flashType' => $flashType,
                'refreshUrl' => $this->generateUrl('courier_dashboard'),
            ], $statusCode);
        }

        return $this->redirectToRoute('courier_dashboard');
    }

    private function isDjamaAjaxRequest(Request $request): bool
    {
        return $request->isXmlHttpRequest()
            || str_contains((string) $request->headers->get('Accept'), 'application/json');
    }

    /**
     * @param list<CustomerOrder> $orders
     * @return list<array{
     *     order: CustomerOrder,
     *     label: string,
     *     shortLabel: string,
     *     summaryLine: string,
     *     commune: string,
     *     customerLabel: string,
     *     deliveryAddressSummary: string,
     *     deliveryInstructions: string,
     *     courierNotes: string,
     *     gpsCoordinatesLabel: string,
     *     gpsMapUrl: string,
     *     canEditAddressNote: bool,
     *     terrainAlerts: list<array{level: string, label: string, description: string}>,
     *     sellerLabels: list<string>,
     *     sellerCollectionGroups: list<array<string, mixed>>,
     *     sellersSummary: string,
     *     itemCount: int,
     *     total: float,
     *     deliveryFee: float,
     *     courierPayout: float,
     *     courierPayoutUncapped: float|null,
     *     courierPayoutCapApplied: bool,
     *     status: string,
     *     isPickedUp: bool,
     *     isOutForDelivery: bool,
     *     deliveryCodeSentAtLabel: string,
     *     deliveryCodeSendCount: int,
     *     deliveryCodeFailedAttempts: int,
     *     hasPendingDeliveryValidationCode: bool,
     *     phone: string,
     *     sanitizedPhone: string,
     *     smsMessage: string
     * }>
     */
    private function buildOrderCards(array $orders): array
    {
        return array_map(fn (CustomerOrder $order): array => $this->buildOrderCard($order), $orders);
    }

    /**
     * @return array{
     *     order: CustomerOrder,
     *     label: string,
     *     shortLabel: string,
     *     summaryLine: string,
     *     commune: string,
     *     customerLabel: string,
     *     deliveryAddressSummary: string,
     *     deliveryInstructions: string,
     *     courierNotes: string,
     *     gpsCoordinatesLabel: string,
     *     gpsMapUrl: string,
     *     canEditAddressNote: bool,
     *     terrainAlerts: list<array{level: string, label: string, description: string}>,
     *     sellerLabels: list<string>,
     *     sellerCollectionGroups: list<array<string, mixed>>,
     *     sellersSummary: string,
     *     itemCount: int,
     *     total: float,
     *     deliveryFee: float,
     *     courierPayout: float,
     *     courierPayoutUncapped: float|null,
     *     courierPayoutCapApplied: bool,
     *     status: string,
     *     isPickedUp: bool,
     *     isOutForDelivery: bool,
     *     deliveryCodeSentAtLabel: string,
     *     deliveryCodeSendCount: int,
     *     deliveryCodeFailedAttempts: int,
     *     hasPendingDeliveryValidationCode: bool,
     *     phone: string,
     *     sanitizedPhone: string,
     *     smsMessage: string
     * }
     */
    private function buildOrderCard(CustomerOrder $order): array
    {
        $customer = $order->getCustomer();
        $status = $order->getStatus();
        $label = $this->getOrderLabel($order);
        $shortLabel = $this->getShortOrderLabel($label);
        $commune = $this->getDeliveryCommuneLabel($order);
        $sellerLabels = $this->getDistinctSellerLabels($order);
        $sellerCollectionGroups = $this->getSellerCollectionGroups($order);
        $terrainAlerts = $this->buildOrderTerrainAlerts($order, $customer, $sellerCollectionGroups);
        $itemCount = $this->getTotalItemCount($order);
        $total = (float) $order->getTotal();
        $courierPayout = $this->getCourierPayoutForOrder($order);
        $courierPayoutUncapped = $this->getUncappedCourierPayoutForOrder($order);
        $courierPayoutCapApplied = $this->isCourierPayoutCapApplied($order);
        $rawPhone = trim((string) $customer->getPhone());
        $phone = $this->isUsablePhone($rawPhone) ? $rawPhone : '';
        $firstName = trim((string) $customer->getFirstName()) ?: 'client';
        $isPickedUp = $status === CustomerOrder::STATUS_PICKED_UP;

        return [
            'order' => $order,
            'label' => $label,
            'shortLabel' => $shortLabel,
            'summaryLine' => sprintf('%s · %s · %d pdt%s · gain %s €', $shortLabel, $commune, $itemCount, $itemCount > 1 ? 's' : '', number_format($courierPayout, 2, ',', ' ')),
            'commune' => $commune,
            'customerLabel' => $this->getCustomerLabel($customer),
            'deliveryAddressSummary' => $order->getDeliveryAddressSummary(),
            'deliveryPointSummary' => trim((string) $order->getDeliveryPointSummary()),
            'deliveryPointAppointmentSummary' => trim((string) $order->getDeliveryPointAppointmentSummary()),
            'deliveryPointTimeWindowSummary' => trim((string) $order->getDeliveryPointTimeWindowSummary()),
            'deliveryPointCustomerInstructions' => trim((string) $order->getDeliveryPointCustomerInstructions()),
            'deliveryPointPublicInstructions' => trim((string) $order->getDeliveryPointPublicInstructions()),
            'deliveryInstructions' => trim((string) ($order->getDeliveryPointCustomerInstructions() ?: $order->getDeliveryAddressInstructions())),
            'courierNotes' => trim((string) ($order->getDeliveryPointCourierInstructions() ?: $order->getDeliveryAddressCourierNotes())),
            'gpsCoordinatesLabel' => trim((string) $order->getDeliveryGpsCoordinatesLabel()),
            'gpsMapUrl' => trim((string) ($order->getDeliveryPointGpsMapUrl() ?: $order->getDeliveryGpsMapUrl())),
            'canEditAddressNote' => $this->canUpdateAddressNote($order, $this->getCourier()),
            'terrainAlerts' => $terrainAlerts,
            'canCollectSellers' => $this->canCollectSeller($order, $this->getCourier()),
            'sellerCollectionProgress' => $order->getSellerCollectionProgress(),
            'allSellerCollectionsDone' => $order->areAllSellerCollectionsDone(),
            'sellerLabels' => $sellerLabels,
            'sellerCollectionGroups' => $sellerCollectionGroups,
            'sellersSummary' => implode(', ', $sellerLabels),
            'itemCount' => $itemCount,
            'total' => $total,
            'deliveryFee' => (float) $order->getDeliveryFee(),
            'courierPayout' => $courierPayout,
            'courierPayoutUncapped' => $courierPayoutUncapped,
            'courierPayoutCapApplied' => $courierPayoutCapApplied,
            'status' => $status,
            'isPickedUp' => $isPickedUp,
            'isOutForDelivery' => $status === CustomerOrder::STATUS_OUT_FOR_DELIVERY,
            'deliveryCodeSentAtLabel' => $this->formatCollectionDate($order->getDeliveryValidationCodeSentAt()?->format(\DateTimeInterface::ATOM), $order->getDisplayTimezone()),
            'deliveryCodeSendCount' => $order->getDeliveryValidationCodeSendCount(),
            'deliveryCodeFailedAttempts' => $order->getDeliveryValidationCodeFailedAttempts(),
            'hasPendingDeliveryValidationCode' => $order->hasPendingDeliveryValidationCode(),
            'phone' => $phone,
            'sanitizedPhone' => $this->sanitizePhone($phone),
            'smsMessage' => $isPickedUp
                ? sprintf('Gégé %s, Hodina – je prends en charge ta commande %s. Je démarre la livraison bientôt.', $firstName, $label)
                : sprintf('Gégé %s, Hodina – je suis en route pour ta commande %s.', $firstName, $label),
        ];
    }


    /**
     * @param list<CourierPayout> $payouts
     * @return list<array<string, mixed>>
     */
    private function buildPayoutCards(array $payouts): array
    {
        return array_map(fn (CourierPayout $payout): array => $this->buildPayoutCard($payout), $payouts);
    }

    /** @return array<string, mixed> */
    private function buildPayoutCard(CourierPayout $payout): array
    {
        $lines = [];
        foreach ($payout->getLines() as $line) {
            if (!$line instanceof CourierPayoutLine) {
                continue;
            }

            $lines[] = [
                'orderReference' => $line->getOrderReference(),
                'deliveredAtLabel' => $line->getDeliveredAt()->format('d/m/Y H:i'),
                'commune' => trim((string) $line->getCustomerCommune()) ?: 'Commune à confirmer',
                'amount' => (float) $line->getCourierPayoutAmount(),
                'deliveryFeeCustomer' => (float) $line->getDeliveryFeeCustomer(),
            ];
        }

        return [
            'id' => $payout->getId(),
            'summaryLine' => sprintf('%s · %s € · %s', $payout->getPeriodLabel(), number_format((float) $payout->getTotalAmount(), 2, ',', ' '), $payout->getStatusLabel()),
            'periodLabel' => $payout->getPeriodLabel(),
            'status' => $payout->getStatus(),
            'statusLabel' => $payout->getStatusLabel(),
            'totalAmount' => (float) $payout->getTotalAmount(),
            'ordersCount' => $payout->getOrdersCount(),
            'paymentDueLabel' => $payout->getPaymentDueDate()?->format('d/m/Y') ?? 'À confirmer',
            'paidAtLabel' => $payout->getPaidAt()?->format('d/m/Y H:i') ?? '',
            'paymentReference' => trim((string) $payout->getPaymentReference()),
            'paymentMethod' => trim((string) $payout->getPaymentMethod()),
            'adminNote' => trim((string) $payout->getAdminNote()),
            'lines' => $lines,
        ];
    }


    private function getCourierPayoutForOrder(CustomerOrder $order): float
    {
        $snapshot = $this->getDeliveryLogisticsPreviewSnapshot($order);

        if (isset($snapshot['estimatedCourierPayout'])) {
            return max(0.0, round((float) $snapshot['estimatedCourierPayout'], 2));
        }

        if (isset($snapshot['courierPayout'])) {
            return max(0.0, round((float) $snapshot['courierPayout'], 2));
        }

        // Fallback historique : anciennes commandes sans snapshot payout.
        return max(0.0, round((float) $order->getDeliveryFee(), 2));
    }

    private function getUncappedCourierPayoutForOrder(CustomerOrder $order): ?float
    {
        $snapshot = $this->getDeliveryLogisticsPreviewSnapshot($order);

        if (!isset($snapshot['uncappedCourierPayout'])) {
            return null;
        }

        return max(0.0, round((float) $snapshot['uncappedCourierPayout'], 2));
    }

    private function isCourierPayoutCapApplied(CustomerOrder $order): bool
    {
        $snapshot = $this->getDeliveryLogisticsPreviewSnapshot($order);

        return (bool) ($snapshot['courierPayoutCapApplied'] ?? false);
    }

    /** @return array<string, mixed> */
    private function getDeliveryLogisticsPreviewSnapshot(CustomerOrder $order): array
    {
        $snapshot = $order->getDeliveryLogisticsSnapshot();
        if (!is_array($snapshot)) {
            return [];
        }

        $preview = $snapshot['preview'] ?? null;
        if (is_array($preview)) {
            return $preview;
        }

        // Compatibilité avec les snapshots J5N-B écrits directement à la racine.
        return $snapshot;
    }

    private function getCustomerLabel(Customer $customer): string
    {
        $name = trim(sprintf('%s %s', $customer->getFirstName(), (string) $customer->getLastName()));

        return $name !== '' ? $name : 'Client #' . $customer->getId();
    }

    private function getShortOrderLabel(string $label): string
    {
        $label = trim($label);

        if ($label === '') {
            return '—';
        }

        return mb_substr($label, -5);
    }

    private function getDeliveryCommuneLabel(CustomerOrder $order): string
    {
        $commune = trim((string) $order->getDeliveryAddressCommune());

        return $commune !== '' ? $commune : 'Commune à confirmer';
    }

    /** @return list<string> */
    private function getDistinctSellerLabels(CustomerOrder $order): array
    {
        $labelsByKey = [];

        foreach ($order->getItems() as $item) {
            if (!$item instanceof OrderItem) {
                continue;
            }

            $seller = $item->getSeller();
            if (!$seller instanceof Seller) {
                continue;
            }

            $key = (string) ($seller->getId() ?? $seller->getCourierDisplayName());
            $labelsByKey[$key] = $this->getSellerLabel($seller);
        }

        return array_values($labelsByKey);
    }

    /**
     * @return list<array{
     *     name: string,
     *     commune: string,
     *     logisticsCommune: string,
     *     pickupCommune: string,
     *     pickupLogisticsMismatch: bool,
     *     pickupAddressSummary: string,
     *     pickupInstructions: string,
     *     pickupGpsCoordinatesLabel: string,
     *     pickupGpsMapUrl: string,
     *     phone: string,
     *     sanitizedPhone: string,
     *     email: string,
     *     alerts: list<array{level: string, label: string, description: string}>,
     *     items: list<array{name: string, quantity: int}>
     * }>
     */
    private function getSellerCollectionGroups(CustomerOrder $order): array
    {
        $groupsBySeller = [];

        foreach ($order->getItems() as $item) {
            if (!$item instanceof OrderItem) {
                continue;
            }

            $seller = $item->getSeller();
            if (!$seller instanceof Seller) {
                continue;
            }

            $sellerKey = (string) ($seller->getId() ?? $seller->getCourierDisplayName());

            if (!isset($groupsBySeller[$sellerKey])) {
                $pickupAddress = $seller->getEffectivePickupAddress();
                $logisticsCommune = $this->getSellerLogisticsCommuneLabel($seller);
                $pickupCommune = $pickupAddress instanceof Address ? trim($pickupAddress->getCommune()) : '';
                $displayCommune = $logisticsCommune !== '' ? $logisticsCommune : ($pickupCommune !== '' ? $pickupCommune : trim((string) $seller->getCommune()));

                $collectionEntry = $order->getSellerCollectionEntry($seller);
                $isCollected = $order->isSellerCollected($seller);
                $hasDefaultCollectionCode = $seller->hasCollectionValidationCode();
                $pickupAddressSummary = $this->getPickupAddressSummary($pickupAddress);
                $pickupGpsCoordinatesLabel = $pickupAddress instanceof Address ? trim((string) $pickupAddress->getGpsCoordinatesLabel()) : '';
                $pickupGpsMapUrl = $pickupAddress instanceof Address ? trim((string) $pickupAddress->getGpsMapUrl()) : '';
                $sellerPhone = $this->getSellerPhone($seller);
                $sellerEmail = $this->getSellerEmail($seller);

                $groupsBySeller[$sellerKey] = [
                    'sellerId' => $seller->getId(),
                    'name' => $seller->getCourierDisplayName(),
                    'commune' => $displayCommune,
                    'logisticsCommune' => $logisticsCommune,
                    'pickupCommune' => $pickupCommune,
                    'pickupLogisticsMismatch' => $this->hasPickupLogisticsCommuneMismatch($logisticsCommune, $pickupCommune),
                    'pickupAddressSummary' => $pickupAddressSummary,
                    'pickupInstructions' => $pickupAddress instanceof Address ? trim((string) $pickupAddress->getDeliveryInstructions()) : '',
                    'pickupGpsCoordinatesLabel' => $pickupGpsCoordinatesLabel,
                    'pickupGpsMapUrl' => $pickupGpsMapUrl,
                    'phone' => $sellerPhone,
                    'sanitizedPhone' => $this->sanitizePhone($sellerPhone),
                    'email' => $sellerEmail,
                    'alerts' => $this->buildSellerTerrainAlerts(
                        $pickupAddress,
                        $logisticsCommune,
                        $pickupCommune,
                        $hasDefaultCollectionCode,
                        $sellerPhone,
                        $sellerEmail
                    ),
                    'isCollected' => $isCollected,
                    'hasDefaultCollectionCode' => $hasDefaultCollectionCode,
                    'collectionCodeSentAtLabel' => $this->formatCollectionDate($collectionEntry['codeSentAt'] ?? null, $order->getDisplayTimezone()),
                    'collectionFailedAttempts' => (int) ($collectionEntry['failedAttempts'] ?? 0),
                    'collectionSmsLogId' => isset($collectionEntry['smsLogId']) ? (int) $collectionEntry['smsLogId'] : null,
                    'collectionEmailLogId' => isset($collectionEntry['emailLogId']) ? (int) $collectionEntry['emailLogId'] : null,
                    'collectionValidationMode' => is_scalar($collectionEntry['validationMode'] ?? null) ? trim((string) $collectionEntry['validationMode']) : '',
                    'collectedAtLabel' => $this->formatCollectionDate($collectionEntry['collectedAt'] ?? null, $order->getDisplayTimezone()),
                    'collectionNote' => is_scalar($collectionEntry['note'] ?? null) ? trim((string) $collectionEntry['note']) : '',
                    'items' => [],
                ];
            }

            $product = $item->getProduct();
            $groupsBySeller[$sellerKey]['items'][] = [
                'name' => $product ? $product->getName() : 'Produit',
                'quantity' => $item->getQuantity(),
            ];
        }

        return array_values($groupsBySeller);
    }

    /**
     * @param list<array<string, mixed>> $sellerCollectionGroups
     * @return list<array{level: string, label: string, description: string}>
     */
    private function buildOrderTerrainAlerts(CustomerOrder $order, Customer $customer, array $sellerCollectionGroups): array
    {
        $alerts = [];

        if (!$this->isUsablePhone((string) $customer->getPhone())) {
            $alerts[] = $this->terrainAlert(
                'warning',
                'Client sans téléphone',
                'Prévoir un canal alternatif si le SMS de code réception ne peut pas partir.'
            );
        }

        if (!$this->isUsableEmail((string) $customer->getEmail())) {
            $alerts[] = $this->terrainAlert(
                'warning',
                'Client sans e-mail',
                'Le suivi client reposera surtout sur le SMS et l’appel.'
            );
        }

        if (!$order->hasDeliveryGpsCoordinates()) {
            $alerts[] = $this->terrainAlert(
                'warning',
                'Client sans GPS',
                'La livraison dépendra des indications et du commentaire terrain.'
            );
        }

        $criticalSellerAlerts = 0;
        $sellerAlerts = 0;
        foreach ($sellerCollectionGroups as $group) {
            foreach (($group['alerts'] ?? []) as $sellerAlert) {
                if (!is_array($sellerAlert)) {
                    continue;
                }

                $sellerAlerts++;
                if (($sellerAlert['level'] ?? '') === 'danger') {
                    $criticalSellerAlerts++;
                }
            }
        }

        if ($criticalSellerAlerts > 0) {
            $alerts[] = $this->terrainAlert(
                'danger',
                'Collecte vendeur à sécuriser',
                sprintf('%d alerte%s bloquante%s côté vendeur.', $criticalSellerAlerts, $criticalSellerAlerts > 1 ? 's' : '', $criticalSellerAlerts > 1 ? 's' : '')
            );
        } elseif ($sellerAlerts > 0) {
            $alerts[] = $this->terrainAlert(
                'info',
                'Infos vendeur à vérifier',
                sprintf('%d point%s terrain vendeur à contrôler avant la tournée.', $sellerAlerts, $sellerAlerts > 1 ? 's' : '')
            );
        }

        return $alerts;
    }

    /** @return list<array{level: string, label: string, description: string}> */
    private function buildSellerTerrainAlerts(
        ?Address $pickupAddress,
        string $logisticsCommune,
        string $pickupCommune,
        bool $hasDefaultCollectionCode,
        string $sellerPhone,
        string $sellerEmail
    ): array {
        $alerts = [];
        $hasSellerPhone = $sellerPhone !== '';
        $hasSellerEmail = $sellerEmail !== '';

        if (!$hasSellerPhone && !$hasSellerEmail) {
            $alerts[] = $this->terrainAlert(
                'danger',
                'Vendeur sans contact',
                'Ajoute un téléphone, un e-mail ou un code permanent avant la tournée.'
            );
        } else {
            if (!$hasSellerPhone) {
                $alerts[] = $this->terrainAlert(
                    'warning',
                    'Vendeur sans téléphone',
                    'L’appel terrain ne sera pas disponible depuis Djama.'
                );
            }

            if (!$hasSellerEmail) {
                $alerts[] = $this->terrainAlert(
                    'warning',
                    'Vendeur sans e-mail',
                    'Un code ponctuel ne pourra partir que par SMS si aucun code permanent n’est configuré.'
                );
            }
        }

        if (!$hasDefaultCollectionCode && !$hasSellerPhone && !$hasSellerEmail) {
            $alerts[] = $this->terrainAlert(
                'danger',
                'Code collecte impossible',
                'Sans code permanent ni contact vendeur, le code ponctuel ne peut pas être envoyé.'
            );
        } elseif (!$hasDefaultCollectionCode) {
            $alerts[] = $this->terrainAlert(
                'info',
                'Code ponctuel requis',
                'Le premier clic sans code enverra un code au vendeur par les canaux disponibles.'
            );
        }

        if (!$pickupAddress instanceof Address || $this->getPickupAddressSummary($pickupAddress) === '') {
            $alerts[] = $this->terrainAlert(
                'warning',
                'Adresse retrait à préciser',
                'Le point de collecte vendeur doit être complété côté admin.'
            );
        } elseif (!$pickupAddress->hasGpsCoordinates()) {
            $alerts[] = $this->terrainAlert(
                'warning',
                'GPS retrait manquant',
                'Le livreur devra se baser sur l’adresse texte et les consignes.'
            );
        }

        if ($this->hasPickupLogisticsCommuneMismatch($logisticsCommune, $pickupCommune)) {
            $alerts[] = $this->terrainAlert(
                'warning',
                'Commune retrait différente',
                'Vérifier que la commune terrain correspond bien à la commune logistique utilisée pour le calcul.'
            );
        }

        return $alerts;
    }

    /** @return array{level: string, label: string, description: string} */
    private function terrainAlert(string $level, string $label, string $description): array
    {
        return [
            'level' => $level,
            'label' => $label,
            'description' => $description,
        ];
    }

    private function getSellerLabel(Seller $seller): string
    {
        $label = trim($seller->getCourierDisplayName());
        $commune = $this->getSellerLogisticsCommuneLabel($seller);

        if ($commune !== '') {
            $label .= ' — ' . $commune;
        }

        return $label;
    }

    /**
     * Commune affichee dans les resumes livreur et utilisee comme repere
     * coherent avec le calcul logistique. L'adresse de retrait reste un detail
     * terrain separe.
     */
    private function getSellerLogisticsCommuneLabel(Seller $seller): string
    {
        return trim((string) ($seller->getDeliveryCommune()?->getName() ?: $seller->getCommune()));
    }

    private function hasPickupLogisticsCommuneMismatch(string $logisticsCommune, string $pickupCommune): bool
    {
        return $logisticsCommune !== ''
            && $pickupCommune !== ''
            && $this->normalizeCommuneKey($logisticsCommune) !== $this->normalizeCommuneKey($pickupCommune);
    }

    private function normalizeCommuneKey(string $value): string
    {
        $value = mb_strtolower(trim($value));
        $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value) ?: $value;

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function getPickupAddressSummary(?Address $address): string
    {
        if (!$address instanceof Address) {
            return '';
        }

        $parts = array_filter([
            trim($address->getLine1()),
            trim((string) $address->getLine2()),
            trim(sprintf('%s %s', $address->getPostalCode(), $address->getCommune())),
        ]);

        return implode("\n", $parts);
    }

    private function getSellerPhone(Seller $seller): string
    {
        foreach ([$seller->getPhone(), $seller->getCustomerAccount()?->getPhone()] as $phone) {
            $phone = trim((string) $phone);

            if ($this->isUsablePhone($phone)) {
                return $phone;
            }
        }

        return '';
    }

    private function getSellerEmail(Seller $seller): string
    {
        foreach ([$seller->getEmail(), $seller->getCustomerAccount()?->getEmail()] as $email) {
            $email = trim((string) $email);

            if ($this->isUsableEmail($email)) {
                return $email;
            }
        }

        return '';
    }

    private function isUsablePhone(string $phone): bool
    {
        $phone = trim($phone);

        return $phone !== '' && preg_replace('/\D+/', '', $phone) !== '0000000000';
    }

    private function isUsableEmail(string $email): bool
    {
        $email = trim($email);

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        return mb_strtolower($email) !== 'contact@hodina.fr';
    }

    private function sanitizePhone(string $phone): string
    {
        return preg_replace('/[^+0-9]/', '', $phone) ?? '';
    }

    private function getTotalItemCount(CustomerOrder $order): int
    {
        $count = 0;

        foreach ($order->getItems() as $item) {
            if ($item instanceof OrderItem) {
                $count += $item->getQuantity();
            }
        }

        return $count;
    }


    private function canCollectSeller(CustomerOrder $order, Customer $courier): bool
    {
        return $order->getStatus() === CustomerOrder::STATUS_PICKED_UP
            && $order->getAssignedCourier()?->getId() === $courier->getId();
    }

    private function formatCollectionDate(mixed $value, ?string $timezone = null): string
    {
        if (!is_scalar($value) || trim((string) $value) === '') {
            return '';
        }

        try {
            return (new \DateTimeImmutable((string) $value))
                ->setTimezone(new \DateTimeZone($this->resolveDisplayTimezone($timezone)))
                ->format('d/m H:i');
        } catch (\Throwable) {
            return '';
        }
    }

    private function resolveDisplayTimezone(?string $timezone): string
    {
        $timezone = $timezone !== null ? trim($timezone) : '';

        return in_array($timezone, \DateTimeZone::listIdentifiers(), true) ? $timezone : self::DEFAULT_TIMEZONE;
    }

    private function canUpdateAddressNote(CustomerOrder $order, Customer $courier): bool
    {
        if ($order->getStatus() === CustomerOrder::STATUS_READY_FOR_PICKUP && $order->getAssignedCourier() === null) {
            return true;
        }

        return in_array($order->getStatus(), [
                CustomerOrder::STATUS_PICKED_UP,
                CustomerOrder::STATUS_OUT_FOR_DELIVERY,
            ], true)
            && $order->getAssignedCourier()?->getId() === $courier->getId();
    }

    private function normalizeCourierNote(mixed $value): ?string
    {
        $value = is_scalar($value) ? trim((string) $value) : '';

        if ($value === '') {
            return null;
        }

        return mb_substr($value, 0, 1000);
    }

    private function getCourier(): Customer
    {
        $user = $this->getUser();

        if (!$user instanceof Customer) {
            throw $this->createAccessDeniedException('Livreur introuvable.');
        }

        return $user;
    }

    private function getOrderLabel(CustomerOrder $order): string
    {
        return $order->getOrderReference() ?: 'n°' . $order->getId();
    }
}
