<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\CustomerOrder;
use App\Entity\EmailLog;
use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class OrderEmailService
{
    public function __construct(
        private readonly MailerInterface $mailer,
        private readonly Connection $connection,
        private readonly LoggerInterface $logger,
        private readonly EmailBrandingService $emailBrandingService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly DeliveryFeeReasonFormatter $deliveryFeeReasonFormatter,
        private readonly string $mailerFrom = 'contact@hodina.fr',
        private readonly string $mailerFromName = 'Hodina',
    ) {
    }

    /**
     * Envoie le récapitulatif de commande côté client.
     *
     * J5H-A : cet envoi est volontairement best-effort.
     * La commande est déjà créée avant l'appel à ce service ; aucun incident e-mail
     * ou journalisation e-mail ne doit donc casser le checkout.
     *
     * Le retour est l'identifiant email_log quand la journalisation a pu être créée.
     */
    public function sendOrderCreatedToCustomer(CustomerOrder $order, bool $attachedToExistingAccount = false): ?int
    {
        $customer = $order->getCustomer();
        $recipientEmail = trim((string) $customer?->getEmail());
        $orderReference = $order->getOrderReference() ?: '#'.$order->getId();
        $subject = $this->emailBrandingService->brandSubject(sprintf('Commande Hodina %s - en validation', $orderReference));
        $recipientName = $this->formatRecipientName($customer);
        $passwordSetupUrl = $attachedToExistingAccount ? null : $this->buildPasswordSetupUrl($customer);
        $items = $this->buildItemsContext($order);
        $deliveryFeeReason = $this->deliveryFeeReasonFormatter->formatFromOrder($order);
        $body = $this->buildPlainOrderCreatedBody($order, $items, $orderReference, $passwordSetupUrl, $attachedToExistingAccount, $deliveryFeeReason);
        $emailSenderSettings = $this->emailBrandingService->getSenderSettings($this->mailerFrom, $this->mailerFromName);
        $orderCreatedCopyEmail = $this->normalizeCopyEmail($emailSenderSettings->orderCreatedCopyEmail(), $recipientEmail);

        $emailLogId = $this->insertEmailLog(
            $order->getId(),
            $customer?->getId(),
            $recipientEmail,
            $subject,
            $body,
            $emailSenderSettings
        );

        if ($recipientEmail === '') {
            $this->markEmailLogFailed($emailLogId, 'Adresse e-mail client absente.');

            return $emailLogId;
        }

        try {
            $email = (new TemplatedEmail())
                ->from($emailSenderSettings->fromAddress())
                ->to($recipientEmail)
                ->subject($subject)
                ->htmlTemplate('emails/order_created.html.twig')
                ->context([
                    'order' => $order,
                    'items' => $items,
                    'customer' => $customer,
                    'orderReference' => $orderReference,
                    'passwordSetupUrl' => $passwordSetupUrl,
                    'attachedToExistingAccount' => $attachedToExistingAccount,
                    'deliveryFeeReason' => $deliveryFeeReason,
                    'emailBranding' => $this->emailBrandingService->buildContext($recipientName),
                    'noReplyNotice' => $this->emailBrandingService->getNoReplyNotice(),
                ]);

            $replyToAddress = $emailSenderSettings->replyToAddress();
            if ($replyToAddress instanceof Address) {
                $email->replyTo($replyToAddress);
            }

            if ($orderCreatedCopyEmail !== null) {
                $email->bcc($orderCreatedCopyEmail);
            }

            $this->mailer->send($email);

            $this->markEmailLogSent($emailLogId);

            if ($orderCreatedCopyEmail !== null) {
                $copyLogId = $this->insertEmailLog(
                    $order->getId(),
                    null,
                    $orderCreatedCopyEmail,
                    $subject,
                    $body,
                    $emailSenderSettings
                );
                $this->markEmailLogSent($copyLogId);
            }

            return $emailLogId;
        } catch (\Throwable $exception) {
            $this->markEmailLogFailed($emailLogId, $exception->getMessage());

            return $emailLogId;
        }
    }

    /**
     * Prépare un snapshot simple des articles pour le template e-mail.
     *
     * Important : au checkout, les OrderItem sont persistés avec setCustomerOrder(),
     * mais la collection inverse CustomerOrder::items n'est pas forcément alimentée
     * en mémoire avant l'appel au service. Comme l'e-mail est ensuite rendu par
     * Messenger, on lit les lignes depuis la base après le flush pour obtenir un
     * snapshot fiable et indépendant des collections Doctrine lazy/détachées.
     *
     * @return array<int, array{productName: string, quantity: int, unitPrice: float, lineTotal: float}>
     */
    private function buildItemsContext(CustomerOrder $order): array
    {
        $orderId = $order->getId();

        if ($orderId === null) {
            return [];
        }

        try {
            /** @var list<array{product_name: string|null, quantity: int|string, unit_price: string, line_total: string}> $rows */
            $rows = $this->connection->fetchAllAssociative(
                <<<'SQL'
                SELECT
                    COALESCE(p.name, 'Produit') AS product_name,
                    oi.quantity,
                    oi.unit_price,
                    oi.line_total
                FROM order_item oi
                LEFT JOIN product p ON p.id = oi.product_id
                WHERE oi.customer_order_id = :orderId
                ORDER BY oi.id ASC
                SQL,
                ['orderId' => $orderId]
            );
        } catch (\Throwable $exception) {
            $this->logger->error('J5H-A : impossible de charger les lignes de commande pour l’e-mail.', [
                'order_id' => $orderId,
                'exception' => $exception,
            ]);

            return [];
        }

        $items = [];

        foreach ($rows as $row) {
            $items[] = [
                'productName' => (string) ($row['product_name'] ?: 'Produit'),
                'quantity' => (int) $row['quantity'],
                'unitPrice' => (float) $row['unit_price'],
                'lineTotal' => (float) $row['line_total'],
            ];
        }

        return $items;
    }

    /**
     * @param array<int, array{productName: string, quantity: int, unitPrice: float, lineTotal: float}> $items
     */
    private function buildPlainOrderCreatedBody(CustomerOrder $order, array $items, string $orderReference, ?string $passwordSetupUrl, bool $attachedToExistingAccount = false, ?string $deliveryFeeReason = null): string
    {
        $lines = [
            'Bonjour,',
            '',
            'Nous avons bien reçu ta commande Hodina.',
            '',
            sprintf('Commande : %s', $orderReference),
            sprintf('Total : %s €', number_format((float) $order->getTotal(), 2, ',', ' ')),
            sprintf('Frais de livraison : %s €', number_format((float) $order->getDeliveryFee(), 2, ',', ' ')),
        ];

        if ($deliveryFeeReason !== null) {
            $lines[] = $deliveryFeeReason;
        }

        $deliveryPointSummary = $order->getDeliveryPointSummary();
        if ($deliveryPointSummary !== null) {
            $lines[] = sprintf('Point de remise : %s', $deliveryPointSummary);

            $timeWindowSummary = $order->getDeliveryPointTimeWindowSummary();
            if ($timeWindowSummary !== null) {
                $lines[] = sprintf('Plage de remise : %s', $timeWindowSummary);
            }

            $customerInstructions = trim((string) $order->getDeliveryPointCustomerInstructions());
            if ($customerInstructions !== '') {
                $lines[] = sprintf('Précision client : %s', $customerInstructions);
            }
        } else {
            $deliveryCommune = trim((string) $order->getDeliveryAddressCommune());
            if ($deliveryCommune !== '') {
                $lines[] = sprintf('Commune de livraison : %s', $deliveryCommune);
            }
        }

        if ($items !== []) {
            $lines[] = '';
            $lines[] = 'Articles :';
            foreach ($items as $item) {
                $lines[] = sprintf(
                    '- %s x%d : %s €',
                    $item['productName'],
                    $item['quantity'],
                    number_format((float) $item['lineTotal'], 2, ',', ' ')
                );
            }
        }

        if ($passwordSetupUrl !== null) {
            $lines[] = '';
            $lines[] = 'Ton espace client Hodina est prêt.';
            $lines[] = 'Crée ton mot de passe avec ce lien sécurisé :';
            $lines[] = $passwordSetupUrl;
        }

        if ($attachedToExistingAccount) {
            $lines[] = '';
            $lines[] = 'Cette commande a été rattachée à ton espace client Hodina.';
            $lines[] = 'Tu pourras la retrouver dans ton espace client avec tes autres commandes.';
        }

        $lines[] = '';
        $lines[] = 'Le paiement se fera à la livraison.';
        $lines[] = '';
        $lines[] = $this->emailBrandingService->getNoReplyNotice();
        $lines[] = '';
        $lines[] = 'Merci,';
        $lines[] = 'L’équipe Hodina';

        return implode("\n", $lines);
    }

    private function formatRecipientName(?\App\Entity\Customer $customer): string
    {
        if (!$customer instanceof \App\Entity\Customer) {
            return '';
        }

        return trim((string) $customer->getFirstName());
    }

    private function buildPasswordSetupUrl(?\App\Entity\Customer $customer): ?string
    {
        if (!$customer instanceof \App\Entity\Customer) {
            return null;
        }

        $token = trim((string) $customer->getResetPasswordToken());
        $expiresAt = $customer->getResetPasswordTokenExpiresAt();

        if ($token === '' || !$expiresAt instanceof \DateTimeImmutable || $expiresAt < new \DateTimeImmutable()) {
            return null;
        }

        try {
            return $this->urlGenerator->generate('app_reset_password', [
                'token' => $token,
            ], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (\Throwable $exception) {
            $this->logger->error('J5T-A : impossible de générer le lien de création de mot de passe.', [
                'customer_id' => $customer->getId(),
                'exception' => $exception,
            ]);

            return null;
        }
    }

    private function insertEmailLog(
        ?int $orderId,
        ?int $customerId,
        string $recipientEmail,
        string $subject,
        string $body,
        EmailSenderSettings $emailSenderSettings
    ): ?int {
        try {
            $this->connection->insert('email_log', [
                'customer_order_id' => $orderId,
                'customer_id' => $customerId,
                'recipient_email' => $recipientEmail,
                'from_email' => $emailSenderSettings->senderEmail(),
                'from_name' => $emailSenderSettings->senderName(),
                'reply_to_email' => $emailSenderSettings->replyToEmail(),
                'reply_to_name' => $emailSenderSettings->replyToName(),
                'subject' => $subject,
                'template_key' => 'emails/order_created.html.twig',
                'body' => $body,
                'event_key' => EmailLog::EVENT_ORDER_CREATED,
                'status' => EmailLog::STATUS_PENDING,
                'error_message' => null,
                'sent_at' => null,
                'created_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ]);

            return (int) $this->connection->lastInsertId();
        } catch (\Throwable $exception) {
            $this->logger->error('J5H-A : impossible de créer le journal e-mail de commande.', [
                'order_id' => $orderId,
                'customer_id' => $customerId,
                'recipient_email' => $recipientEmail,
                'exception' => $exception,
            ]);

            return null;
        }
    }

    private function normalizeCopyEmail(?string $copyEmail, string $recipientEmail): ?string
    {
        $copyEmail = mb_strtolower(trim((string) $copyEmail));

        if ($copyEmail === '' || filter_var($copyEmail, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        if ($copyEmail === mb_strtolower(trim($recipientEmail))) {
            return null;
        }

        return $copyEmail;
    }

    private function markEmailLogSent(?int $emailLogId): void
    {
        if ($emailLogId === null) {
            return;
        }

        try {
            $this->connection->update('email_log', [
                'status' => EmailLog::STATUS_SENT,
                'error_message' => null,
                'sent_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
            ], [
                'id' => $emailLogId,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('J5H-A : impossible de passer le journal e-mail en SENT.', [
                'email_log_id' => $emailLogId,
                'exception' => $exception,
            ]);
        }
    }

    private function markEmailLogFailed(?int $emailLogId, string $errorMessage): void
    {
        if ($emailLogId === null) {
            return;
        }

        try {
            $this->connection->update('email_log', [
                'status' => EmailLog::STATUS_FAILED,
                'error_message' => mb_substr($errorMessage, 0, 2000),
                'sent_at' => null,
            ], [
                'id' => $emailLogId,
            ]);
        } catch (\Throwable $exception) {
            $this->logger->error('J5H-A : impossible de passer le journal e-mail en FAILED.', [
                'email_log_id' => $emailLogId,
                'exception' => $exception,
            ]);
        }
    }
}
