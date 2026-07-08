<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ChatbotConversation;
use App\Entity\ChatbotMessage;
use App\Entity\SupportTicket;
use App\Entity\SupportTicketMessage;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Détecte l'échec de réponse du chatbot ou une demande explicite d'humain,
 * et transforme la conversation en SupportTicket traçable avec transcript
 * complet joint. Ne clôture jamais automatiquement un ticket : cf. contrainte
 * "pas de clôture automatique des tickets par l'IA".
 */
final class ChatbotEscalationService
{
    /**
     * Marqueur que le prompt système (ChatbotContextBuilderService) demande à
     * l'IA d'ajouter quand la demande sort de son périmètre. Jamais montré au
     * client : toujours retiré du texte affiché.
     */
    public const ESCALATION_MARKER = '[ESCALADE_HUMAIN]';

    /**
     * Heuristique simple par mots-clés : volume MVP faible, pas de
     * classification NLP dédiée pour ce lot.
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
    ];

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupportTicketNotificationService $notificationService,
    ) {
    }

    public function customerRequestsHuman(string $userMessage): bool
    {
        $normalized = mb_strtolower($userMessage);

        foreach (self::HUMAN_REQUEST_KEYWORDS as $keyword) {
            if (mb_stripos($normalized, $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    public function containsEscalationMarker(string $aiReplyText): bool
    {
        return str_contains($aiReplyText, self::ESCALATION_MARKER);
    }

    public function stripEscalationMarker(string $aiReplyText): string
    {
        return trim(str_replace(self::ESCALATION_MARKER, '', $aiReplyText));
    }

    /**
     * Crée le ticket d'escalade et notifie l'admin. Le transcript complet de
     * la conversation (déjà à jour au moment de l'appel) est joint comme
     * premier message du ticket.
     */
    public function escalate(ChatbotConversation $conversation, string $reasonLabel): SupportTicket
    {
        $customer = $conversation->getCustomer();

        $ticket = (new SupportTicket())
            ->setOrigin(SupportTicket::ORIGIN_CHATBOT_ESCALATION)
            ->setCustomer($customer)
            ->setContactName(trim(sprintf('%s %s', $customer->getFirstName(), (string) $customer->getLastName())))
            ->setContactEmail((string) $customer->getEmail())
            ->setContactPhone($customer->getPhone())
            ->setSubject(sprintf('Escalade chatbot IA — %s', $reasonLabel))
            ->setChatbotConversation($conversation);

        $ticket->addMessage(
            (new SupportTicketMessage())
                ->setSenderType(SupportTicketMessage::SENDER_SYSTEM)
                ->setContent($this->buildTranscript($conversation, $reasonLabel))
        );

        $conversation->setStatus(ChatbotConversation::STATUS_ESCALATED);

        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $this->notificationService->notifyAdminOfNewTicket($ticket);

        return $ticket;
    }

    private function buildTranscript(ChatbotConversation $conversation, string $reasonLabel): string
    {
        $lines = [
            sprintf('Escalade automatique du chatbot IA — motif : %s.', $reasonLabel),
            '',
            'Transcript complet de la conversation :',
            '',
        ];

        foreach ($conversation->getMessages() as $message) {
            $speaker = match ($message->getRole()) {
                ChatbotMessage::ROLE_USER => 'Client',
                ChatbotMessage::ROLE_ASSISTANT => 'Assistant IA',
                default => 'Système',
            };

            $lines[] = sprintf(
                '[%s] %s : %s',
                $message->getCreatedAt()->format('d/m/Y H:i'),
                $speaker,
                $this->stripEscalationMarker($message->getContent())
            );
        }

        return implode("\n", $lines);
    }
}
