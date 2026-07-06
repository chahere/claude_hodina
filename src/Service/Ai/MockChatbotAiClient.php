<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Dto\ChatbotAiReply;
use App\Dto\ChatbotAiRequest;
use App\Entity\AiChatbotSetting;

/**
 * Client IA simulé : aucun appel réseau. Sert à valider tout le pipeline
 * (contexte, rate limiter, UI, persistance des messages) avant de brancher
 * un fournisseur réel — cf. AiChatbotSetting::PROVIDER_MOCK, sélectionnable
 * depuis l'écran EasyAdmin "Réglages IA".
 */
final class MockChatbotAiClient implements ChatbotAiClientInterface
{
    public function getProviderKey(): string
    {
        return AiChatbotSetting::PROVIDER_MOCK;
    }

    public function generateReply(ChatbotAiRequest $request): ChatbotAiReply
    {
        $message = trim($request->userMessage);

        return ChatbotAiReply::success(sprintf(
            '[Mode test — réponse simulée] Tu as écrit : « %s ». Une fois un fournisseur IA réel configuré, l’assistant Hodina répondra ici en utilisant le contexte de ton compte.',
            $message !== '' ? $message : '(message vide)'
        ));
    }
}
