<?php

declare(strict_types=1);

namespace App\Service;

use App\Dto\ChatbotAiRequest;
use App\Entity\ChatbotConversation;
use App\Entity\ChatbotMessage;
use App\Entity\Customer;
use App\Repository\AiChatbotSettingRepository;
use App\Repository\ChatbotConversationRepository;
use App\Service\Ai\ChatbotAiClientResolver;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Orchestration d'un tour de conversation chatbot : construit le contexte,
 * appelle le fournisseur IA configuré, détecte les cas d'escalade et
 * journalise les messages. Ni le contrôleur ni le frontend ne voient jamais
 * autre chose que ce texte.
 */
final class ChatbotConversationService
{
    private const MAX_HISTORY_MESSAGES = 20;

    private const ESCALATION_REPLY = 'Je ne peux pas t’aider complètement sur ce point. Un membre de l’équipe Hodina va te recontacter rapidement.';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ChatbotConversationRepository $conversationRepository,
        private readonly ChatbotContextBuilderService $contextBuilder,
        private readonly ChatbotAiClientResolver $aiClientResolver,
        private readonly AiChatbotCredentialCipher $credentialCipher,
        private readonly AiChatbotSettingRepository $aiChatbotSettingRepository,
        private readonly ChatbotEscalationService $escalationService,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function getOrCreateActiveConversation(Customer $customer): ChatbotConversation
    {
        $conversation = $this->conversationRepository->findActiveForCustomer($customer);
        if ($conversation instanceof ChatbotConversation) {
            return $conversation;
        }

        $conversation = (new ChatbotConversation())->setCustomer($customer);
        $this->entityManager->persist($conversation);
        $this->entityManager->flush();

        return $conversation;
    }

    public function reply(ChatbotConversation $conversation, string $userMessage): string
    {
        $userMessage = trim($userMessage);
        $history = $this->buildHistory($conversation);

        $conversation->addMessage(
            (new ChatbotMessage())->setRole(ChatbotMessage::ROLE_USER)->setContent($userMessage)
        );

        $escalationReason = $this->escalationService->customerRequestsHuman($userMessage)
            ? 'demande explicite du client'
            : null;
        $replyText = null;

        if ($escalationReason === null) {
            $systemPrompt = $this->contextBuilder->buildSystemPrompt($conversation->getCustomer());
            $setting = $this->aiChatbotSettingRepository->getOrCreateSingleton();
            $apiKey = $this->resolveApiKey($setting->getApiKeyEncrypted());

            $client = $this->aiClientResolver->resolve($setting->getProvider());
            $aiReply = $client->generateReply(new ChatbotAiRequest(
                $systemPrompt,
                $history,
                $userMessage,
                $setting->getModel(),
                $apiKey,
            ));

            if (!$aiReply->successful) {
                $escalationReason = 'échec technique de réponse IA';
                $this->logger->warning('Échec de réponse du chatbot IA.', [
                    'conversation_id' => $conversation->getId(),
                    'provider' => $setting->getProvider(),
                    'reason' => $aiReply->errorReason,
                ]);
            } elseif ($this->escalationService->containsEscalationMarker($aiReply->replyText)) {
                $escalationReason = 'hors périmètre identifié par l’assistant';
            } else {
                $replyText = $aiReply->replyText;
            }
        }

        if ($replyText === null) {
            $replyText = self::ESCALATION_REPLY;
        }

        $conversation->addMessage(
            (new ChatbotMessage())->setRole(ChatbotMessage::ROLE_ASSISTANT)->setContent($replyText)
        );

        if ($escalationReason !== null) {
            $this->escalationService->escalate($conversation, $escalationReason);
        } else {
            $this->entityManager->flush();
        }

        return $replyText;
    }

    private function resolveApiKey(?string $apiKeyEncrypted): ?string
    {
        if ($apiKeyEncrypted === null || $apiKeyEncrypted === '') {
            return null;
        }

        try {
            return $this->credentialCipher->decrypt($apiKeyEncrypted);
        } catch (\DomainException $exception) {
            $this->logger->error('Clé API du fournisseur IA illisible.', ['exception' => $exception]);

            return null;
        }
    }

    /** @return list<array{role: string, content: string}> */
    private function buildHistory(ChatbotConversation $conversation): array
    {
        $history = [];

        foreach ($conversation->getMessages() as $message) {
            if ($message->getRole() === ChatbotMessage::ROLE_USER) {
                $history[] = ['role' => 'user', 'content' => $message->getContent()];
            } elseif ($message->getRole() === ChatbotMessage::ROLE_ASSISTANT) {
                $history[] = ['role' => 'assistant', 'content' => $message->getContent()];
            }
        }

        if (count($history) > self::MAX_HISTORY_MESSAGES) {
            $history = array_slice($history, -self::MAX_HISTORY_MESSAGES);
        }

        return $history;
    }
}
