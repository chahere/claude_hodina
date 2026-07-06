<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CourierPayout;
use App\Entity\Customer;
use App\Entity\EmailLog;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class CourierPayoutAdminNotificationService
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

    /**
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, due: \DateTimeImmutable, label: string} $period
     * @param array{created: int, updated: int, skippedOrders: int, lines: int, payouts: list<CourierPayout>} $generationResult
     * @return array{sent: int, failed: int, skipped: int, warnings: list<string>}
     */
    public function notifyAdmins(array $period, array $generationResult, \DateTimeImmutable $executedAt, bool $autoDue): array
    {
        $admins = $this->findAdminRecipients();
        $sent = 0;
        $failed = 0;
        $skipped = 0;
        $warnings = [];

        if ($admins === []) {
            return [
                'sent' => 0,
                'failed' => 0,
                'skipped' => 0,
                'warnings' => ['Aucun administrateur avec une adresse e-mail valide n’a été trouvé.'],
            ];
        }

        $subject = $this->emailBrandingService->brandSubject(sprintf('Hodina — Récap paiements livreurs à valider (%s)', $period['label']));
        $summary = $this->buildSummary($generationResult);

        foreach ($admins as $admin) {
            $recipientEmail = $this->getValidEmail($admin);
            if ($recipientEmail === '') {
                ++$skipped;
                continue;
            }

            $recipientName = $this->formatCustomerLabel($admin);

            $emailLog = (new EmailLog())
                ->setCustomer(null)
                ->setCustomerOrder(null)
                ->setRecipientEmail($recipientEmail)
                ->setSubject($subject)
                ->setTemplateKey('emails/admin/courier_payout_recap.html.twig')
                ->setEventKey(EmailLog::EVENT_COURIER_PAYOUT_RECAP)
                ->setBody($this->buildPlainBody($period, $generationResult, $summary, $executedAt, $autoDue, $recipientName))
                ->setStatus(EmailLog::STATUS_PENDING);

            $this->forceMutableDateTimeFields($emailLog);

            try {
                $email = (new TemplatedEmail())
                    ->from(new Address($this->mailerFrom, $this->mailerFromName))
                    ->to(new Address($recipientEmail, $recipientName))
                    ->subject($subject)
                    ->htmlTemplate('emails/admin/courier_payout_recap.html.twig')
                    ->context([
                        'period' => $period,
                        'result' => $generationResult,
                        'summary' => $summary,
                        'executedAt' => $executedAt,
                        'autoDue' => $autoDue,
                        'emailBranding' => $this->emailBrandingService->buildContext($recipientName),
                    ]);

                $this->mailer->send($email);

                $emailLog
                    ->setStatus(EmailLog::STATUS_SENT)
                    ->setSentAt(new \DateTime())
                    ->setErrorMessage(null);
                ++$sent;
            } catch (\Throwable $exception) {
                $emailLog
                    ->setStatus(EmailLog::STATUS_FAILED)
                    ->setErrorMessage(mb_substr($exception->getMessage(), 0, 2000));
                ++$failed;

                $this->logger->error('J5Q-C : impossible d’envoyer le récap paiements livreurs à un admin.', [
                    'recipient_email' => $recipientEmail,
                    'exception' => $exception,
                ]);
            }

            $this->entityManager->persist($emailLog);
        }

        $this->entityManager->flush();

        return [
            'sent' => $sent,
            'failed' => $failed,
            'skipped' => $skipped,
            'warnings' => $warnings,
        ];
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

    /**
     * @param array{created: int, updated: int, skippedOrders: int, lines: int, payouts: list<CourierPayout>} $generationResult
     * @return array{totalAmount: float, payoutsCount: int, ordersCount: int}
     */
    private function buildSummary(array $generationResult): array
    {
        $total = 0.0;
        $ordersCount = 0;

        foreach ($generationResult['payouts'] as $payout) {
            $total += (float) $payout->getTotalAmount();
            $ordersCount += $payout->getOrdersCount();
        }

        return [
            'totalAmount' => round($total, 2),
            'payoutsCount' => count($generationResult['payouts']),
            'ordersCount' => $ordersCount,
        ];
    }

    /**
     * @param array{start: \DateTimeImmutable, end: \DateTimeImmutable, due: \DateTimeImmutable, label: string} $period
     * @param array{created: int, updated: int, skippedOrders: int, lines: int, payouts: list<CourierPayout>} $generationResult
     * @param array{totalAmount: float, payoutsCount: int, ordersCount: int} $summary
     */
    private function buildPlainBody(array $period, array $generationResult, array $summary, \DateTimeImmutable $executedAt, bool $autoDue, string $recipientName): string
    {
        $lines = [
            $this->emailBrandingService->buildOpening($recipientName),
            '',
            'Récapitulatif Hodina — paiements livreurs à contrôler',
            '',
            sprintf('Période : %s', $period['label']),
            sprintf('Paiement prévu : %s', $period['due']->format('d/m/Y')),
            sprintf('Généré le : %s', $executedAt->format('d/m/Y H:i')),
            sprintf('Mode : %s', $autoDue ? 'cron auto-due' : 'manuel'),
            '',
            sprintf('Paiements créés : %d', $generationResult['created']),
            sprintf('Paiements complétés : %d', $generationResult['updated']),
            sprintf('Lignes rattachées : %d', $generationResult['lines']),
            sprintf('Commandes ignorées : %d', $generationResult['skippedOrders']),
            sprintf('Total à contrôler : %s €', number_format($summary['totalAmount'], 2, ',', ' ')),
            '',
        ];

        if ($generationResult['payouts'] === []) {
            $lines[] = 'Aucun nouveau paiement livreur à générer pour cette période.';
        } else {
            $lines[] = 'Détail par livreur :';
            foreach ($generationResult['payouts'] as $payout) {
                $lines[] = sprintf(
                    '- %s : %s € — %d commande(s)',
                    $payout->getCourierLabel(),
                    number_format((float) $payout->getTotalAmount(), 2, ',', ' '),
                    $payout->getOrdersCount()
                );
            }
        }

        $lines[] = '';
        $lines[] = 'Action requise : EasyAdmin > Livreurs > Rémunérations livreurs.';
        $lines[] = 'Le cron ne valide pas et ne paie pas automatiquement.';
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
