<?php

namespace App\Controller\Client;

use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderFeedback;
use App\Service\CustomerOrderWorkflowService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/mon-compte')]
final class AccountController extends AbstractController
{
    /** @var list<string> */
    private const HISTORY_STATUSES = [
        CustomerOrder::STATUS_DELIVERED,
        CustomerOrder::STATUS_CANCELED,
    ];

    #[Route('', name: 'client_account_index', methods: ['GET'])]
    public function index(EntityManagerInterface $em): Response
    {
        $customer = $this->getCurrentCustomer();
        $orders = $this->findCustomerOrders($em, $customer);

        $activeOrders = [];
        $historyOrders = [];

        foreach ($orders as $order) {
            $view = $this->buildOrderView($order);

            if (in_array($order->getStatus(), self::HISTORY_STATUSES, true)) {
                $historyOrders[] = $view;
                continue;
            }

            $activeOrders[] = $view;
        }

        return $this->render('client/account/index.html.twig', [
            'customer' => $customer,
            'activeOrders' => array_slice($activeOrders, 0, 3),
            'historyOrders' => array_slice($historyOrders, 0, 3),
            'activeOrderCount' => count($activeOrders),
            'historyOrderCount' => count($historyOrders),
        ]);
    }

    #[Route('/commandes', name: 'client_orders_index', methods: ['GET'])]
    public function orders(EntityManagerInterface $em): Response
    {
        $customer = $this->getCurrentCustomer();
        $orders = $this->findCustomerOrders($em, $customer);

        $activeOrders = [];
        $historyOrders = [];

        foreach ($orders as $order) {
            $view = $this->buildOrderView($order);

            if (in_array($order->getStatus(), self::HISTORY_STATUSES, true)) {
                $historyOrders[] = $view;
                continue;
            }

            $activeOrders[] = $view;
        }

        return $this->render('client/orders/index.html.twig', [
            'customer' => $customer,
            'activeOrders' => $activeOrders,
            'historyOrders' => $historyOrders,
        ]);
    }

    #[Route('/commandes/{id}', name: 'client_orders_show', requirements: ['id' => '\\d+'], methods: ['GET'])]
    public function show(int $id, EntityManagerInterface $em): Response
    {
        $customer = $this->getCurrentCustomer();
        $order = $this->findCustomerOrderOr404($em, $customer, $id);

        return $this->render('client/orders/show.html.twig', [
            'customer' => $customer,
            'order' => $order,
            'orderView' => $this->buildOrderView($order),
            'timeline' => $this->buildTimeline($order),
            'cancellationReasons' => CustomerOrderFeedback::getReasonLabels(),
        ]);
    }

    #[Route('/commandes/{id}/annuler', name: 'client_orders_cancel', requirements: ['id' => '\\d+'], methods: ['POST'])]
    public function cancel(
        int $id,
        Request $request,
        EntityManagerInterface $em,
        CustomerOrderWorkflowService $workflow,
    ): RedirectResponse {
        $customer = $this->getCurrentCustomer();
        $order = $this->findCustomerOrderOr404($em, $customer, $id);

        if (!$this->isCsrfTokenValid('client_cancel_order_' . $order->getId(), (string) $request->request->get('_token'))) {
            $this->addFlash('danger', 'La demande d’annulation a expiré. Réessaie depuis le détail de la commande.');

            return $this->redirectToRoute('client_orders_show', ['id' => $order->getId()]);
        }

        if (!$workflow->canCancel($order)) {
            $this->addFlash('warning', 'La préparation est déjà engagée. Contacte Hodina rapidement si tu as un problème avec cette commande.');

            return $this->redirectToRoute('client_orders_show', ['id' => $order->getId()]);
        }

        $reason = $this->normalizeCancellationReason((string) $request->request->get('reason', ''));
        $comment = $this->normalizeNullableText((string) $request->request->get('comment', ''));

        $feedback = $em->getRepository(CustomerOrderFeedback::class)->findOneBy([
            'customerOrder' => $order,
            'targetKey' => 'cancellation',
        ]);

        if (!$feedback instanceof CustomerOrderFeedback) {
            $feedback = (new CustomerOrderFeedback())
                ->setCustomerOrder($order)
                ->setCustomer($customer)
                ->setTargetType(CustomerOrderFeedback::TARGET_CANCELLATION)
                ->setTargetKey('cancellation');

            $em->persist($feedback);
        }

        $feedback
            ->setCustomer($customer)
            ->setReason($reason)
            ->setComment($comment)
            ->setUpdatedAt(new \DateTimeImmutable());

        try {
            $workflow->cancelByCustomer($order);
        } catch (\DomainException $exception) {
            $this->addFlash('warning', $exception->getMessage());

            return $this->redirectToRoute('client_orders_show', ['id' => $order->getId()]);
        }

        $em->flush();

        $this->addFlash('success', 'Ta commande a bien été annulée. Merci pour ton retour, il aide Hodina à s’améliorer.');

        return $this->redirectToRoute('client_orders_show', ['id' => $order->getId()]);
    }

    private function getCurrentCustomer(): Customer
    {
        $user = $this->getUser();

        if (!$user instanceof Customer) {
            throw $this->createAccessDeniedException('Compte client requis.');
        }

        return $user;
    }

    /** @return list<CustomerOrder> */
    private function findCustomerOrders(EntityManagerInterface $em, Customer $customer): array
    {
        /** @var list<CustomerOrder> $orders */
        $orders = $em->getRepository(CustomerOrder::class)
            ->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')->addSelect('i')
            ->leftJoin('i.product', 'p')->addSelect('p')
            ->leftJoin('i.seller', 's')->addSelect('s')
            ->where('o.customer = :customer')
            ->andWhere('o.status != :draft')
            ->setParameter('customer', $customer)
            ->setParameter('draft', CustomerOrder::STATUS_DRAFT)
            ->orderBy('o.createdAt', 'DESC')
            ->getQuery()
            ->getResult();

        return $orders;
    }

    private function findCustomerOrderOr404(EntityManagerInterface $em, Customer $customer, int $id): CustomerOrder
    {
        $order = $em->getRepository(CustomerOrder::class)
            ->createQueryBuilder('o')
            ->leftJoin('o.items', 'i')->addSelect('i')
            ->leftJoin('i.product', 'p')->addSelect('p')
            ->leftJoin('i.seller', 's')->addSelect('s')
            ->where('o.id = :id')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.status != :draft')
            ->setParameter('id', $id)
            ->setParameter('customer', $customer)
            ->setParameter('draft', CustomerOrder::STATUS_DRAFT)
            ->getQuery()
            ->getOneOrNullResult();

        if (!$order instanceof CustomerOrder) {
            throw $this->createNotFoundException('Commande introuvable.');
        }

        return $order;
    }

    /** @return array<string, mixed> */
    private function buildOrderView(CustomerOrder $order): array
    {
        return [
            'order' => $order,
            'reference' => $order->getOrderReference() ?: ('#' . $order->getId()),
            'statusLabel' => $this->getClientStatusLabel($order->getStatus()),
            'statusClass' => $this->getClientStatusClass($order->getStatus()),
            'nextStep' => $this->getNextStepMessage($order),
            'canCancel' => in_array($order->getStatus(), [
                CustomerOrder::STATUS_PENDING_VALIDATION,
                CustomerOrder::STATUS_CONFIRMED,
            ], true),
            'isHistory' => in_array($order->getStatus(), self::HISTORY_STATUSES, true),
        ];
    }

    private function getClientStatusLabel(string $status): string
    {
        return match ($status) {
            CustomerOrder::STATUS_PENDING_VALIDATION => 'Commande reçue',
            CustomerOrder::STATUS_CONFIRMED => 'Commande validée',
            CustomerOrder::STATUS_PREPARING => 'En préparation',
            CustomerOrder::STATUS_READY_FOR_PICKUP => 'Prête pour le livreur',
            CustomerOrder::STATUS_PICKED_UP => 'Prise en charge',
            CustomerOrder::STATUS_OUT_FOR_DELIVERY => 'En cours de livraison',
            CustomerOrder::STATUS_DELIVERED => 'Livrée',
            CustomerOrder::STATUS_CANCELED => 'Annulée',
            default => 'Commande en cours',
        };
    }

    private function getClientStatusClass(string $status): string
    {
        return match ($status) {
            CustomerOrder::STATUS_PENDING_VALIDATION => 'is-pending',
            CustomerOrder::STATUS_CONFIRMED => 'is-confirmed',
            CustomerOrder::STATUS_PREPARING => 'is-preparing',
            CustomerOrder::STATUS_READY_FOR_PICKUP => 'is-ready',
            CustomerOrder::STATUS_PICKED_UP => 'is-picked-up',
            CustomerOrder::STATUS_OUT_FOR_DELIVERY => 'is-out-for-delivery',
            CustomerOrder::STATUS_DELIVERED => 'is-delivered',
            CustomerOrder::STATUS_CANCELED => 'is-canceled',
            default => 'is-default',
        };
    }

    private function getNextStepMessage(CustomerOrder $order): string
    {
        return match ($order->getStatus()) {
            CustomerOrder::STATUS_PENDING_VALIDATION => 'L’équipe Hodina vérifie la disponibilité des produits auprès des vendeurs.',
            CustomerOrder::STATUS_CONFIRMED => 'Ta commande est validée. La préparation va démarrer.',
            CustomerOrder::STATUS_PREPARING => 'Les produits sont en cours de préparation.',
            CustomerOrder::STATUS_READY_FOR_PICKUP => 'La commande est prête pour le livreur.',
            CustomerOrder::STATUS_PICKED_UP => 'Le livreur récupère les produits auprès des vendeurs.',
            CustomerOrder::STATUS_OUT_FOR_DELIVERY => 'La livraison est en cours. Donne ton code de réception uniquement après avoir reçu ta commande.',
            CustomerOrder::STATUS_DELIVERED => 'La commande est terminée. Merci pour ta confiance.',
            CustomerOrder::STATUS_CANCELED => 'Cette commande a été annulée.',
            default => 'Hodina suit ta commande.',
        };
    }

    /** @return list<array{label: string, date: ?\DateTimeImmutable, done: bool}> */
    private function buildTimeline(CustomerOrder $order): array
    {
        $steps = [
            ['label' => 'Commande reçue', 'date' => $order->getSubmittedAt() ?? $order->getCreatedAt()],
            ['label' => 'Commande validée', 'date' => $order->getConfirmedAt()],
            ['label' => 'Préparation', 'date' => $order->getPreparingAt()],
            ['label' => 'Prête pour le livreur', 'date' => $order->getReadyAt()],
            ['label' => 'Prise en charge', 'date' => $order->getCourierAssignedAt()],
            ['label' => 'En livraison', 'date' => $order->getOutForDeliveryAt()],
            ['label' => 'Livrée', 'date' => $order->getDeliveredAt()],
        ];

        if ($order->getStatus() === CustomerOrder::STATUS_CANCELED) {
            $steps[] = ['label' => 'Annulée', 'date' => $order->getCanceledAt()];
        }

        return array_map(static fn (array $step): array => [
            'label' => $step['label'],
            'date' => $step['date'],
            'done' => $step['date'] instanceof \DateTimeImmutable,
        ], $steps);
    }

    private function normalizeCancellationReason(string $reason): ?string
    {
        $reason = trim($reason);

        return array_key_exists($reason, CustomerOrderFeedback::getReasonLabels()) ? $reason : null;
    }

    private function normalizeNullableText(string $value): ?string
    {
        $value = trim($value);

        return $value !== '' ? $value : null;
    }
}
