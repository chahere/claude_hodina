<?php

declare(strict_types=1);

namespace App\Service\Ai;

/**
 * Choisit l'implémentation ChatbotAiClientInterface correspondant au
 * fournisseur configuré par l'admin (AiChatbotSetting::provider). Changer de
 * fournisseur ne demande aucune modification de code, uniquement un
 * changement de réglage.
 */
final class ChatbotAiClientResolver
{
    public function __construct(
        private readonly MockChatbotAiClient $mockClient,
        private readonly AnthropicChatbotAiClient $anthropicClient,
        private readonly OpenAiChatbotAiClient $openAiClient,
    ) {
    }

    public function resolve(string $providerKey): ChatbotAiClientInterface
    {
        return match ($providerKey) {
            $this->anthropicClient->getProviderKey() => $this->anthropicClient,
            $this->openAiClient->getProviderKey() => $this->openAiClient,
            default => $this->mockClient,
        };
    }
}
