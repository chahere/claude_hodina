<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Dto\ChatbotAiReply;
use App\Dto\ChatbotAiRequest;
use App\Entity\AiChatbotSetting;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpClient\Exception\TransportException;
use Symfony\Contracts\HttpClient\Exception\ExceptionInterface as HttpClientExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Appel serveur-à-serveur vers l'API Anthropic (Messages API). La clé API
 * ne transite jamais ailleurs que dans l'en-tête de cette requête sortante :
 * jamais loggée, jamais renvoyée au frontend.
 */
final class AnthropicChatbotAiClient implements ChatbotAiClientInterface
{
    private const API_VERSION = '2023-06-01';
    private const MAX_TOKENS = 1024;

    public function __construct(
        #[Autowire(service: 'anthropic.client')]
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getProviderKey(): string
    {
        return AiChatbotSetting::PROVIDER_ANTHROPIC;
    }

    public function generateReply(ChatbotAiRequest $request): ChatbotAiReply
    {
        if ($request->apiKey === null || trim($request->apiKey) === '') {
            return ChatbotAiReply::failure('Clé API Anthropic manquante.');
        }

        $messages = $request->history;
        $messages[] = ['role' => 'user', 'content' => $request->userMessage];

        try {
            $response = $this->httpClient->request('POST', '/v1/messages', [
                'headers' => [
                    'x-api-key' => $request->apiKey,
                    'anthropic-version' => self::API_VERSION,
                    'content-type' => 'application/json',
                ],
                'json' => [
                    'model' => $request->model,
                    'max_tokens' => self::MAX_TOKENS,
                    'system' => $request->systemPrompt,
                    'messages' => $messages,
                ],
            ]);

            $data = $response->toArray();
            $text = $data['content'][0]['text'] ?? null;

            if (!is_string($text) || trim($text) === '') {
                return ChatbotAiReply::failure('Réponse Anthropic vide ou invalide.');
            }

            return ChatbotAiReply::success(trim($text));
        } catch (TransportException $exception) {
            return ChatbotAiReply::failure('Impossible de joindre Anthropic : ' . $exception->getMessage());
        } catch (HttpClientExceptionInterface $exception) {
            return ChatbotAiReply::failure('Erreur Anthropic : ' . $exception->getMessage());
        }
    }
}
