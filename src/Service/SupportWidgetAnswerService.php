<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\SupportWidgetReply;
use App\Entity\FaqEntry;
use App\Repository\FaqEntryRepository;

/**
 * Moteur de réponses du widget flottant "Assistant Hodina" : uniquement des
 * règles métier (mots-clés + FAQ), aucun appel IA. Le nom "Assistant Hodina"
 * (jamais "AI Assistant") reflète ça — cf. ChatbotConversationService pour le
 * chatbot IA réel, réservé aux clients connectés dans /mon-compte/assistant.
 */
final class SupportWidgetAnswerService
{
    public const QUICK_DELIVERY = 'Frais de livraison';
    public const QUICK_ORDER = 'Où est ma commande ?';
    public const QUICK_PRODUCTS = 'Produits disponibles';
    public const QUICK_HUMAN = 'Parler à l’équipe';

    private const WELCOME_MESSAGE = 'Bonjour 👋 Je suis l’assistant Hodina. Je peux vous aider sur les commandes, la livraison, les produits ou vous mettre en contact avec l’équipe.';

    private const UNKNOWN_REPLY = 'Je ne suis pas sûr de la réponse. Je peux transmettre votre message à l’équipe Hodina.';

    /**
     * Mots déclenchant une demande explicite d'humain. Volontairement
     * dupliqué (et non importé) depuis ChatbotEscalationService::HUMAN_REQUEST_KEYWORDS :
     * ce widget est un moteur à règles totalement indépendant du chatbot IA,
     * pas une variante qui en dépend.
     *
     * @var list<string>
     */
    private const HUMAN_REQUEST_KEYWORDS = [
        'humain',
        'conseiller',
        'vraie personne',
        'personne réelle',
        'quelqu\'un de chez hodina',
        'vrai conseiller',
        'parler à l’équipe',
        'parler a l\'equipe',
    ];

    /** @var list<string> */
    private const DELIVERY_KEYWORDS = ['livraison', 'livrer', 'livré', 'frais', 'tarif', 'commune', 'zone', 'transport', 'mamoudzou'];

    /** @var list<string> */
    private const ORDER_KEYWORDS = ['commande', 'suivi', 'suivre', 'statut', 'où est', 'ou est', 'colis'];

    /** @var list<string> */
    private const PRODUCT_KEYWORDS = ['disponible', 'dispo', 'stock', 'rupture', 'saison', 'saisonnier', 'produit'];

    /** @var list<string> */
    private const SELLER_KEYWORDS = ['vendeur', 'producteur', 'ferme', 'agriculteur', 'agricultrice'];

    /** @var list<string> */
    private const STOP_WORDS = [
        'le', 'la', 'les', 'de', 'des', 'du', 'un', 'une', 'et', 'ou', 'est',
        'pour', 'avec', 'sur', 'dans', 'mon', 'ma', 'mes', 'je', 'tu', 'il',
        'elle', 'vous', 'nous', 'au', 'aux', 'ce', 'cette', 'que', 'qui',
        'quoi', 'comment', 'bonjour', 'merci', 'svp',
    ];

    public function __construct(
        private readonly FaqEntryRepository $faqEntryRepository,
    ) {
    }

    public function welcomeReply(): SupportWidgetReply
    {
        return new SupportWidgetReply(
            text: self::WELCOME_MESSAGE,
            quickReplies: $this->defaultQuickReplies(),
        );
    }

    /** @return list<string> */
    public function defaultQuickReplies(): array
    {
        return [self::QUICK_DELIVERY, self::QUICK_ORDER, self::QUICK_PRODUCTS, self::QUICK_HUMAN];
    }

    public function answer(string $userMessage, bool $isConnected): SupportWidgetReply
    {
        $normalized = mb_strtolower(trim($userMessage));

        if ($this->matchesAny($normalized, self::HUMAN_REQUEST_KEYWORDS)) {
            return new SupportWidgetReply(
                text: 'Bien sûr. Décrivez votre demande ci-dessous, l’équipe Hodina vous répondra rapidement.',
                quickReplies: [],
                suggestEscalation: true,
            );
        }

        if ($this->matchesAny($normalized, self::DELIVERY_KEYWORDS)) {
            return new SupportWidgetReply(
                text: 'Les frais de livraison dépendent de la commune livrée, du contenu du panier et de l’organisation logistique locale. Le montant exact est calculé automatiquement dans votre panier. Vous trouverez le détail par commune sur la page dédiée.',
                quickReplies: [self::QUICK_ORDER, self::QUICK_HUMAN],
                actionKey: 'delivery_info',
            );
        }

        if ($this->matchesAny($normalized, self::ORDER_KEYWORDS)) {
            return $isConnected
                ? new SupportWidgetReply(
                    text: 'Vous pouvez suivre le statut de vos commandes directement depuis votre espace client.',
                    quickReplies: [self::QUICK_HUMAN],
                    actionKey: 'my_orders',
                )
                : new SupportWidgetReply(
                    text: 'Le suivi de commande se fait depuis votre espace client. Connectez-vous pour voir son statut.',
                    quickReplies: [self::QUICK_HUMAN],
                    actionKey: 'login',
                );
        }

        if ($this->matchesAny($normalized, self::PRODUCT_KEYWORDS)) {
            return new SupportWidgetReply(
                text: 'Certains produits sont saisonniers ou dépendent de la disponibilité de nos vendeurs locaux. Le catalogue n’affiche que les produits actuellement en vente.',
                quickReplies: [self::QUICK_HUMAN],
                actionKey: 'catalogue',
            );
        }

        if ($this->matchesAny($normalized, self::SELLER_KEYWORDS)) {
            return new SupportWidgetReply(
                text: 'Hodina travaille avec des vendeurs et producteurs locaux de Mayotte. Le vendeur est indiqué sur chaque fiche produit.',
                quickReplies: [self::QUICK_PRODUCTS, self::QUICK_HUMAN],
            );
        }

        $faqMatch = $this->findFaqMatch($normalized);
        if ($faqMatch instanceof FaqEntry) {
            return new SupportWidgetReply(
                text: $faqMatch->getAnswer(),
                quickReplies: [self::QUICK_HUMAN],
            );
        }

        return new SupportWidgetReply(
            text: self::UNKNOWN_REPLY,
            quickReplies: [self::QUICK_HUMAN],
            suggestEscalation: true,
        );
    }

    /** @param list<string> $keywords */
    private function matchesAny(string $normalizedMessage, array $keywords): bool
    {
        foreach ($keywords as $keyword) {
            if (mb_stripos($normalizedMessage, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    private function findFaqMatch(string $normalizedMessage): ?FaqEntry
    {
        $messageWords = $this->extractSignificantWords($normalizedMessage);
        if ($messageWords === []) {
            return null;
        }

        $bestEntry = null;
        $bestScore = 0;

        foreach ($this->faqEntryRepository->findActiveOrdered() as $entry) {
            $haystack = mb_strtolower($entry->getQuestion() . ' ' . $entry->getAnswer());
            $score = 0;

            foreach ($messageWords as $word) {
                if (mb_stripos($haystack, $word) !== false) {
                    ++$score;
                }
            }

            if ($score > $bestScore) {
                $bestScore = $score;
                $bestEntry = $entry;
            }
        }

        return $bestScore > 0 ? $bestEntry : null;
    }

    /** @return list<string> */
    private function extractSignificantWords(string $normalizedMessage): array
    {
        $words = preg_split('/[^\p{L}0-9]+/u', $normalizedMessage, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return array_values(array_filter(
            $words,
            static fn (string $word): bool => mb_strlen($word) >= 3 && !in_array($word, self::STOP_WORDS, true)
        ));
    }
}
