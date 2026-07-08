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
 * Appel serveur-à-serveur vers l'API OpenAI (Chat Completions). La clé API
 * ne transite jamais ailleurs que dans l'en-tête de cette requête sortante :
 * jamais loggée, jamais renvoyée au frontend.
 */
final class OpenAiChatbotAiClient implements ChatbotAiClientInterface
{
    public function __construct(
        #[Autowire(service: 'openai.client')]
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getProviderKey(): string
    {
        return AiChatbotSetting::PROVIDER_OPENAI;
    }

    public function generateReply(ChatbotAiRequest $request): ChatbotAiReply
    {
        if ($request->apiKey === null || trim($request->apiKey) === '') {
            return ChatbotAiReply::failure('Clé API OpenAI manquante.');
        }

        $messages = [['role' => 'system', 'content' => $request->systemPrompt]];
        foreach ($request->history as $entry) {
            $messages[] = $entry;
        }
        $messages[] = ['role' => 'user', 'content' => $request->userMessage];

        try {
            $response = $this->httpClient->request('POST', '/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $request->apiKey,
                    'Content-Type' => 'application/json',
                ],
                'json' => [
                    'model' => $request->model,
                    'messages' => $messages,
                ],
            ]);

            $data = $response->toArray();
            $text = $data['choices'][0]['message']['content'] ?? null;

            if (!is_string($text) || trim($text) === '') {
                return ChatbotAiReply::failure('Réponse OpenAI vide ou invalide.');
            }

            return ChatbotAiReply::success(trim($text));
        } catch (TransportException $exception) {
            return ChatbotAiReply::failure('Impossible de joindre OpenAI : ' . $exception->getMessage());
        } catch (HttpClientExceptionInterface $exception) {
            return ChatbotAiReply::failure('Erreur OpenAI : ' . $exception->getMessage());
        }
    }
}
