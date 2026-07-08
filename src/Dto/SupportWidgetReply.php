<?php

declare(strict_types=1);

namespace App\Dto;

/**
 * Réponse du moteur de règles du widget "Assistant Hodina" (aucun appel IA).
 * actionKey est une clé symbolique (pas une URL) : le contrôleur la traduit
 * en lien réel via le router, pour garder le service découplé du routing.
 */
final class SupportWidgetReply
{
    /**
     * @param list<string> $quickReplies
     */
    public function __construct(
        public readonly string $text,
        public readonly array $quickReplies = [],
        public readonly bool $suggestEscalation = false,
        public readonly ?string $actionKey = null,
    ) {
    }
}
