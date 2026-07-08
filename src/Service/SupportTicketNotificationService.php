<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\Customer;
use App\Entity\EmailLog;
use App\Entity\SupportTicket;
use App\Entity\SupportTicketMessage;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

/**
 * Notifie les administrateurs Hodina à chaque création de ticket support,
 * quelle que soit son origine (formulaire de contact, escalade chatbot...).
 */
final class SupportTicketNotificationService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MailerInterface $mailer,
        private readonly LoggerInterface $logger,
        private readonly EmailBrandingService $emailBrandingService,
        private readonly string $mailerFrom = 'contact@hodina.fr',
        private readonly string $mailerFromName = 'Hodina',
    ) {
    }

    public function notifyAdminOfNewTicket(SupportTicket $ticket): void
    {
        $admins = $this->findAdminRecipients();

        if ($admins === []) {
            $this->logger->warning('Nouveau ticket support : aucun administrateur avec une adresse e-mail valide n’a été trouvé.', [
                'ticket_id' => $ticket->getId(),
            ]);

            return;
        }

        $subject = $this->emailBrandingService->brandSubject(sprintf('Nouveau ticket support — %s', $ticket->getSubject()));
        $firstMessage = $ticket->getMessages()->first() ?: null;

        foreach ($admins as $admin) {
            $recipientEmail = $this->getValidEmail($admin);
            if ($recipientEmail === '') {
                continue;
            }

            $recipientName = $this->formatCustomerLabel($admin);

            $emailLog = (new EmailLog())
                ->setCustomer($ticket->getCustomer())
                ->setRecipientEmail($recipientEmail)
                ->setSubject($subject)
                ->setTemplateKey('emails/admin/support_ticket_created.html.twig')
                ->setEventKey(EmailLog::EVENT_SUPPORT_TICKET_CREATED)
                ->setBody($this->buildPlainBody($ticket, $firstMessage, $recipientName))
                ->setStatus(EmailLog::STATUS_PENDING);

            $this->forceMutableDateTimeFields($emailLog);

            try {
                $email = (new TemplatedEmail())
                    ->from(new Address($this->mailerFrom, $this->mailerFromName))
                    ->to(new Address($recipientEmail, $recipientName))
                    ->subject($subject)
                    ->htmlTemplate('emails/admin/support_ticket_created.html.twig')
                    ->context([
                        'ticket' => $ticket,
                        'firstMessage' => $firstMessage,
                        'emailBranding' => $this->emailBrandingService->buildContext($recipientName),
                    ]);

                $this->mailer->send($email);

                $emailLog
                    ->setStatus(EmailLog::STATUS_SENT)
                    ->setSentAt(new \DateTime())
                    ->setErrorMessage(null);
            } catch (\Throwable $exception) {
                $emailLog
                    ->setStatus(EmailLog::STATUS_FAILED)
                    ->setErrorMessage(mb_substr($exception->getMessage(), 0, 2000));

                $this->logger->error('Impossible de notifier un administrateur d’un nouveau ticket support.', [
                    'ticket_id' => $ticket->getId(),
                    'recipient_email' => $recipientEmail,
                    'exception' => $exception,
                ]);
            }

            $this->entityManager->persist($emailLog);
        }

        $this->entityManager->flush();
    }

    /** @return list<Customer> */
    private function findAdminRecipients(): array
    {
        /** @var list<Customer> $customers */
        $customers = $this->entityManager->getRepository(Customer::class)->findBy([], ['id' => 'ASC']);
        $admins = [];
        $seenEmails = [];

        foreach ($customers as $customer) {
            if (!in_array('ROLE_ADMIN', $customer->getRoles(), true)) {
                continue;
            }

            $email = mb_strtolower($this->getValidEmail($customer));
            if ($email === '' || isset($seenEmails[$email])) {
                continue;
            }

            $seenEmails[$email] = true;
            $admins[] = $customer;
        }

        return $admins;
    }

    private function buildPlainBody(SupportTicket $ticket, ?SupportTicketMessage $firstMessage, string $recipientName): string
    {
        $lines = [
            $this->emailBrandingService->buildOpening($recipientName),
            '',
            'Nouveau ticket support Hodina',
            '',
            sprintf('Origine : %s', $ticket->getOrigin()),
            sprintf('Sujet : %s', $ticket->getSubject()),
            sprintf('Contact : %s (%s)', $ticket->getContactName(), $ticket->getContactEmail()),
        ];

        if ($ticket->getContactPhone() !== null) {
            $lines[] = sprintf('Téléphone : %s', $ticket->getContactPhone());
        }

        $lines[] = '';

        if ($firstMessage !== null) {
            $lines[] = 'Message :';
            $lines[] = $firstMessage->getContent();
            $lines[] = '';
        }

        $lines[] = 'Action requise : EasyAdmin > Support > Tickets support.';
        $lines[] = '';

        foreach ($this->emailBrandingService->buildPlainClosingLines() as $line) {
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function getValidEmail(Customer $customer): string
    {
        $email = trim((string) $customer->getEmail());

        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false ? $email : '';
    }

    private function formatCustomerLabel(Customer $customer): string
    {
        $name = trim(sprintf('%s %s', $customer->getFirstName(), (string) $customer->getLastName()));

        return $name !== '' ? $name : ($customer->getEmail() ?: 'Admin Hodina');
    }

    private function forceMutableDateTimeFields(EmailLog $emailLog): void
    {
        $this->forceMutableDateTimeField($emailLog, 'createdAt');
        $this->forceMutableDateTimeField($emailLog, 'sentAt');
    }

    private function forceMutableDateTimeField(object $object, string $propertyName): void
    {
        if (!property_exists($object, $propertyName)) {
            return;
        }

        $property = new \ReflectionProperty($object, $propertyName);
        $property->setAccessible(true);

        $value = $property->getValue($object);

        if ($value instanceof \DateTimeImmutable) {
            $property->setValue($object, \DateTime::createFromImmutable($value));
        }
    }
}
