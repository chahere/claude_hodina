<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\FaqEntry;
use App\Entity\Product;
use App\Repository\FaqEntryRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Construit le prompt système du chatbot à partir d'un contexte lecture-seule
 * minimal et filtré (commandes récentes, zone de livraison, catalogue actif,
 * FAQ institutionnelle). L'IA n'a jamais d'accès direct à l'ORM : tout ce
 * qu'elle reçoit passe par ce service, sous forme de texte déjà résumé.
 */
final class ChatbotContextBuilderService
{
    private const MAX_RECENT_ORDERS = 3;
    private const MAX_PRODUCTS = 40;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly FaqEntryRepository $faqEntryRepository,
    ) {
    }

    public function buildSystemPrompt(Customer $customer): string
    {
        $sections = [
            $this->buildIntroduction(),
            $this->buildFaqSection(),
            $this->buildOrdersSection($customer),
            $this->buildDeliveryZoneSection($customer),
            $this->buildCatalogueSection(),
            $this->buildBehaviorRules(),
        ];

        return implode("\n\n", array_filter($sections, static fn (string $section): bool => trim($section) !== ''));
    }

    private function buildIntroduction(): string
    {
        return 'Tu es l’assistant IA de Hodina, une marketplace locale de produits à Mayotte. '
            . 'Tu réponds au client Hodina connecté ci-dessous, en français, de façon concise et factuelle.';
    }

    private function buildFaqSection(): string
    {
        $entries = $this->faqEntryRepository->findActiveOrdered();
        if ($entries === []) {
            return '';
        }

        $lines = ['FAQ institutionnelle Hodina (seule source de vérité sur les informations générales) :'];
        foreach ($entries as $entry) {
            /** @var FaqEntry $entry */
            $lines[] = sprintf('Q: %s' . "\n" . 'R: %s', $entry->getQuestion(), $entry->getAnswer());
        }

        return implode("\n\n", $lines);
    }

    private function buildOrdersSection(Customer $customer): string
    {
        /** @var list<CustomerOrder> $orders */
        $orders = $this->entityManager->getRepository(CustomerOrder::class)
            ->createQueryBuilder('o')
            ->andWhere('o.customer = :customer')
            ->andWhere('o.status != :draft')
            ->setParameter('customer', $customer)
            ->setParameter('draft', CustomerOrder::STATUS_DRAFT)
            ->orderBy('o.createdAt', 'DESC')
            ->setMaxResults(self::MAX_RECENT_ORDERS)
            ->getQuery()
            ->getResult();

        if ($orders === []) {
            return 'Commandes du client : ce client n’a encore aucune commande.';
        }

        $lines = ['Commandes récentes de ce client (les seules données de commande disponibles, ne jamais en inventer d’autres) :'];
        foreach ($orders as $order) {
            $lines[] = sprintf(
                '- %s : statut « %s »%s, créée le %s.',
                $order->getOrderReference() ?: ('#' . $order->getId()),
                $this->getStatusLabel($order->getStatus()),
                $order->getDeliveryPointAppointmentSummary() !== null
                    ? sprintf(', rendez-vous de livraison prévu le %s', $order->getDeliveryPointAppointmentSummary())
                    : '',
                $order->getCreatedAt()?->format('d/m/Y') ?? 'date inconnue'
            );
        }

        return implode("\n", $lines);
    }

    private function buildDeliveryZoneSection(Customer $customer): string
    {
        $zoneName = $customer->getDeliveryAddress()?->getDeliveryZone()?->getName();

        return $zoneName !== null
            ? sprintf('Zone de livraison habituelle du client : %s.', $zoneName)
            : 'Zone de livraison habituelle du client : non renseignée.';
    }

    private function buildCatalogueSection(): string
    {
        /** @var list<Product> $products */
        $products = $this->entityManager->getRepository(Product::class)
            ->createQueryBuilder('p')
            ->andWhere('p.isActive = :active')
            ->setParameter('active', true)
            ->orderBy('p.displayPriority', 'ASC')
            ->addOrderBy('p.id', 'ASC')
            ->setMaxResults(self::MAX_PRODUCTS)
            ->getQuery()
            ->getResult();

        if ($products === []) {
            return '';
        }

        $lines = ['Produits actifs du catalogue Hodina (liste non exhaustive, ne jamais promettre un produit absent de cette liste) :'];
        foreach ($products as $product) {
            $lines[] = sprintf(
                '- %s (%s) — %s € %s',
                $product->getName(),
                $product->getCategory()->getName(),
                $product->getPrice(),
                $product->getUnitLabel()
            );
        }

        return implode("\n", $lines);
    }

    private function buildBehaviorRules(): string
    {
        return implode("\n", [
            'Règles impératives :',
            '- Réponds uniquement à partir des informations fournies ci-dessus. N’invente jamais de statut de commande, de prix ou de produit.',
            '- Ton périmètre : statut de commande, informations de livraison, catalogue et questions générales couvertes par la FAQ.',
            '- Si la demande sort de ce périmètre (remboursement, litige, réclamation...), si tu ne trouves pas l’information, ou si le client demande explicitement à parler à un humain, dis-le clairement et indique qu’un humain de Hodina va le recontacter.',
            '- Ne donne jamais de conseil de prix, de remise ou de suggestion de produit personnalisée.',
        ]);
    }

    private function getStatusLabel(string $status): string
    {
        return match ($status) {
            CustomerOrder::STATUS_PENDING_VALIDATION => 'commande reçue, en cours de validation',
            CustomerOrder::STATUS_CONFIRMED => 'commande validée',
            CustomerOrder::STATUS_PREPARING => 'en préparation',
            CustomerOrder::STATUS_READY_FOR_PICKUP => 'prête, en attente du livreur',
            CustomerOrder::STATUS_PICKED_UP => 'prise en charge par le livreur',
            CustomerOrder::STATUS_OUT_FOR_DELIVERY => 'en cours de livraison',
            CustomerOrder::STATUS_DELIVERED => 'livrée',
            CustomerOrder::STATUS_CANCELED => 'annulée',
            default => $status,
        };
    }
}
