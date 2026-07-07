<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer;
use App\Entity\SupportTicket;
use App\Entity\SupportTicketMessage;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Transforme une demande du widget "Assistant Hodina" en SupportTicket
 * traçable (origine CHAT_WIDGET), en réutilisant les mêmes entités et le
 * même mécanisme de notification admin que le formulaire de contact et
 * l'escalade du chatbot IA — pas de nouvelle entité dédiée.
 */
final class SupportWidgetEscalationService
{
    private const SUBJECT = 'Escalade widget Assistant Hodina';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SupportTicketNotificationService $notificationService,
    ) {
    }

    /**
     * @param list<array{role: string, text: string}> $recentExchange
     */
    public function escalateForCustomer(Customer $customer, string $message, array $recentExchange): SupportTicket
    {
        $ticket = (new SupportTicket())
            ->setOrigin(SupportTicket::ORIGIN_CHAT_WIDGET)
            ->setCustomer($customer)
            ->setContactName(trim(sprintf('%s %s', $customer->getFirstName(), (string) $customer->getLastName())))
            ->setContactEmail((string) $customer->getEmail())
            ->setContactPhone($customer->getPhone())
            ->setSubject(self::SUBJECT);

        $ticket->addMessage(
            (new SupportTicketMessage())
                ->setSenderType(SupportTicketMessage::SENDER_CUSTOMER)
                ->setAuthorCustomer($customer)
                ->setContent($this->buildContent($message, $recentExchange))
        );

        return $this->persistAndNotify($ticket);
    }

    /**
     * @param list<array{role: string, text: string}> $recentExchange
     */
    public function escalateForGuest(string $name, string $email, ?string $phone, string $message, array $recentExchange): SupportTicket
    {
        $ticket = (new SupportTicket())
            ->setOrigin(SupportTicket::ORIGIN_CHAT_WIDGET)
            ->setContactName($name)
            ->setContactEmail($email)
            ->setContactPhone($phone)
            ->setSubject(self::SUBJECT);

        $ticket->addMessage(
            (new SupportTicketMessage())
                ->setSenderType(SupportTicketMessage::SENDER_CUSTOMER)
                ->setContent($this->buildContent($message, $recentExchange))
        );

        return $this->persistAndNotify($ticket);
    }

    /**
     * @param list<array{role: string, text: string}> $recentExchange
     */
    private function buildContent(string $message, array $recentExchange): string
    {
        $lines = [];

        if ($recentExchange !== []) {
            $lines[] = 'Échange récent dans le widget Assistant Hodina :';
            $lines[] = '';

            foreach ($recentExchange as $entry) {
                $speaker = ($entry['role'] ?? '') === 'user' ? 'Client' : 'Assistant Hodina';
                $text = trim((string) ($entry['text'] ?? ''));

                if ($text !== '') {
                    $lines[] = sprintf('[%s] %s', $speaker, $text);
                }
            }

            $lines[] = '';
        }

        $lines[] = 'Demande transmise :';
        $lines[] = trim($message);

        return implode("\n", $lines);
    }

    private function persistAndNotify(SupportTicket $ticket): SupportTicket
    {
        $this->entityManager->persist($ticket);
        $this->entityManager->flush();

        $this->notificationService->notifyAdminOfNewTicket($ticket);

        return $ticket;
    }
}
