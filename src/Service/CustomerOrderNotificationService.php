<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\EmailLog;
use App\Entity\SmsLog;
use App\Service\Sms\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class CustomerOrderNotificationService
{
    private const SMS_CONTEXT_SELLER_COLLECTIONS_COMPLETED = 'customer_order_seller_collections_completed';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SmsService $smsService,
        private readonly MailerInterface $mailer,
        private readonly OrderReferenceGenerator $orderReferenceGenerator,
        private readonly LoggerInterface $logger,
        private readonly EmailBrandingService $emailBrandingService,
        private readonly string $mailerFrom = 'contact@hodina.fr',
        private readonly string $mailerFromName = 'Hodina',
    ) {
    }

    /**
     * Envoie l'e-mail client correspondant à une transition de statut déjà notifiée par SMS.
     *
     * Important anti-spam :
     * - aucun e-mail générique n'est envoyé pour OUT_FOR_DELIVERY ici ; le mail de code
     *   réception client J5O contient déjà cette information ;
     * - un event_key déjà journalisé en PENDING ou SENT n'est pas renvoyé automatiquement.
     */
    public function sendStatusEmailToCustomer(CustomerOrder $order, string $status): ?EmailLog
    {
        if ($status === CustomerOrder::STATUS_OUT_FOR_DELIVERY) {
            return null;
        }

        $notification = $this->buildStatusNotification($order, $status);
        if ($notification === null) {
            return null;
        }

        return $this->sendCustomerEmailOnce(
            $order,
            $notification['eventKey'],
            $notification['subject'],
            $notification['title'],
            $notification['message'],
            $notification['details']
        );
    }

    /**
     * Informe le client une seule fois quand toutes les collectes vendeurs de la commande sont validées.
     * Ce n'est pas un statut global CustomerOrder, mais c'est une étape terrain utile pour le client.
     *
     * @return array{smsLog: SmsLog|null, emailLog: EmailLog|null}
     */
    public function notifySellerCollectionsCompleted(CustomerOrder $order): array
    {
        $orderReference = $this->orderReferenceGenerator->ensureReference($order);
        $firstName = $this->getCustomerFirstName($order->getCustomer());
        $message = sprintf(
            'Gégé %s, Hodina – les produits de ta commande %s ont été collectés auprès des vendeurs. Le livreur peut maintenant démarrer la livraison.',
            $firstName,
            $orderReference
        );

        $smsLog = $this->sendCustomerSmsOnce(
            $order,
            self::SMS_CONTEXT_SELLER_COLLECTIONS_COMPLETED,
            $message
        );

        $emailLog = $this->sendCustomerEmailOnce(
            $order,
            EmailLog::EVENT_ORDER_SELLER_COLLECTIONS_COMPLETED,
            sprintf('Produits collectés pour la commande Hodina %s', $orderReference),
            'Produits collectés',
            sprintf('Les produits de ta commande %s ont été récupérés auprès des vendeurs.', $orderReference),
            'Le livreur peut maintenant démarrer la livraison client.'
        );

        return [
            'smsLog' => $smsLog,
            'emailLog' => $emailLog,
        ];
    }

    /**
     * @return array{eventKey: string, subject: string, title: string, message: string, details: string}|null
     */
    private function buildStatusNotification(CustomerOrder $order, string $status): ?array
    {
        $orderReference = $this->orderReferenceGenerator->ensureReference($order);

        return match ($status) {
            CustomerOrder::STATUS_CONFIRMED => [
                'eventKey' => EmailLog::EVENT_ORDER_STATUS_CONFIRMED,
                'subject' => sprintf('Commande Hodina %s validée', $orderReference),
                'title' => 'Commande validée',
                'message' => sprintf('Ta commande %s est validée.', $orderReference),
                'details' => 'L’équipe Hodina va maintenant organiser la préparation des produits.',
            ],
            CustomerOrder::STATUS_PREPARING => [
                'eventKey' => EmailLog::EVENT_ORDER_STATUS_PREPARING,
                'subject' => sprintf('Commande Hodina %s en préparation', $orderReference),
                'title' => 'Commande en préparation',
                'message' => sprintf('Ta commande %s est en cours de préparation.', $orderReference),
                'details' => 'Nous te prévenons dès que la commande est prête pour la prise en charge livreur.',
            ],
            CustomerOrder::STATUS_READY_FOR_PICKUP => [
                'eventKey' => EmailLog::EVENT_ORDER_STATUS_READY_FOR_PICKUP,
                'subject' => sprintf('Commande Hodina %s prête pour le livreur', $orderReference),
                'title' => 'Commande prête pour le livreur',
                'message' => sprintf('Ta commande %s est prête.', $orderReference),
                'details' => 'La prise en charge par un livreur Hodina va être organisée.',
            ],
            CustomerOrder::STATUS_PICKED_UP => [
                'eventKey' => EmailLog::EVENT_ORDER_STATUS_PICKED_UP,
                'subject' => sprintf('Commande Hodina %s prise en charge', $orderReference),
                'title' => 'Commande prise en charge',
                'message' => sprintf('Ta commande %s est prise en charge par notre livreur.', $orderReference),
                'details' => 'Nous te prévenons au départ en livraison. Le code de réception sera envoyé quand le livreur démarrera la livraison client.',
            ],
            CustomerOrder::STATUS_DELIVERED => [
                'eventKey' => EmailLog::EVENT_ORDER_STATUS_DELIVERED,
                'subject' => sprintf('Commande Hodina %s livrée', $orderReference),
                'title' => 'Commande livrée',
                'message' => sprintf('Ta commande %s a été livrée.', $orderReference),
                'details' => 'Merci pour ta confiance. Nous espérons que tes produits te plairont.',
            ],
            CustomerOrder::STATUS_CANCELED => [
                'eventKey' => EmailLog::EVENT_ORDER_STATUS_CANCELED,
                'subject' => sprintf('Commande Hodina %s annulée', $orderReference),
                'title' => 'Commande annulée',
                'message' => sprintf('Ta commande %s ne peut pas être validée pour le moment.', $orderReference),
                'details' => 'L’équipe Hodina peut te recontacter si une précision est nécessaire.',
            ],
            default => null,
        };
    }

    private function sendCustomerSmsOnce(CustomerOrder $order, string $context, string $message): ?SmsLog
    {
        if ($this->hasSmsAlreadyLogged($order, $context)) {
            return null;
        }

        return $this->smsService->sendForOrder(
            $order,
            $this->getCustomerPhone($order->getCustomer()),
            $message,
            $context,
            'customer'
        );
    }

    private function sendCustomerEmailOnce(
        CustomerOrder $order,
        string $eventKey,
        string $subject,
        string $title,
        string $message,
        string $details
    ): ?EmailLog {
        if ($this->hasEmailAlreadyLogged($order, $eventKey)) {
            return null;
        }

        $customer = $order->getCustomer();
        $recipientEmail = $this->getCustomerEmail($customer);
        $orderReference = $this->orderReferenceGenerator->ensureReference($order);
        $recipientName = $this->getCustomerFirstNameForEmail($customer);
        $subject = $this->emailBrandingService->brandSubject($subject);
        $body = $this->buildPlainEmailBody($customer, $orderReference, $title, $message, $details, $order);
        $emailSenderSettings = $this->emailBrandingService->getSenderSettings($this->mailerFrom, $this->mailerFromName);

        $emailLog = (new EmailLog())
            ->setCustomerOrder($order)
            ->setCustomer($customer)
            ->setRecipientEmail($recipientEmail)
            ->setFromEmail($emailSenderSettings->senderEmail())
            ->setFromName($emailSenderSettings->senderName())
            ->setReplyToEmail($emailSenderSettings->replyToEmail())
            ->setReplyToName($emailSenderSettings->replyToName())
            ->setSubject($subject)
            ->setBody($body)
            ->setTemplateKey('emails/order_status_update.html.twig')
            ->setEventKey($eventKey)
            ->setStatus(EmailLog::STATUS_PENDING);

        $this->forceMutableDateTimeFields($emailLog);

        if ($recipientEmail === '') {
            $emailLog
                ->setStatus(EmailLog::STATUS_FAILED)
                ->setErrorMessage('Adresse e-mail client manquante ou invalide.');

            $this->entityManager->persist($emailLog);
            $this->entityManager->flush();

            return $emailLog;
        }

        try {
            $email = (new TemplatedEmail())
                ->from($emailSenderSettings->fromAddress())
                ->to($recipientEmail)
                ->subject($subject)
                ->htmlTemplate('emails/order_status_update.html.twig')
                ->context([
                    'order' => $order,
                    'customer' => $customer,
                    'orderReference' => $orderReference,
                    'title' => $title,
                    'message' => $message,
                    'details' => $details,
                    'emailBranding' => $this->emailBrandingService->buildContext($recipientName),
                    'noReplyNotice' => $this->emailBrandingService->getNoReplyNotice(),
                ]);

            $replyToAddress = $emailSenderSettings->replyToAddress();
            if ($replyToAddress instanceof Address) {
                $email->replyTo($replyToAddress);
            }

            $this->mailer->send($email);

            $emailLog
                ->setStatus(EmailLog::STATUS_SENT)
                ->setSentAt(new \DateTime())
                ->setErrorMessage(null);
        } catch (\Throwable $exception) {
            $emailLog
                ->setStatus(EmailLog::STATUS_FAILED)
                ->setErrorMessage(mb_substr($exception->getMessage(), 0, 2000));

            $this->logger->error('Impossible d’envoyer l’e-mail de notification client.', [
                'order_id' => $order->getId(),
                'event_key' => $eventKey,
                'recipient_email' => $recipientEmail,
                'exception' => $exception,
            ]);
        }

        $this->entityManager->persist($emailLog);
        $this->entityManager->flush();

        return $emailLog;
    }

    private function hasEmailAlreadyLogged(CustomerOrder $order, string $eventKey): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(emailLog.id)')
            ->from(EmailLog::class, 'emailLog')
            ->andWhere('emailLog.customerOrder = :order')
            ->andWhere('emailLog.eventKey = :eventKey')
            ->andWhere('emailLog.status IN (:statuses)')
            ->setParameter('order', $order)
            ->setParameter('eventKey', $eventKey)
            ->setParameter('statuses', [EmailLog::STATUS_PENDING, EmailLog::STATUS_SENT])
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    private function hasSmsAlreadyLogged(CustomerOrder $order, string $context): bool
    {
        $count = $this->entityManager->createQueryBuilder()
            ->select('COUNT(smsLog.id)')
            ->from(SmsLog::class, 'smsLog')
            ->andWhere('smsLog.customerOrder = :order')
            ->andWhere('smsLog.context = :context')
            ->andWhere('smsLog.status IN (:statuses)')
            ->setParameter('order', $order)
            ->setParameter('context', $context)
            ->setParameter('statuses', [SmsLog::STATUS_PENDING, SmsLog::STATUS_SENT])
            ->getQuery()
            ->getSingleScalarResult();

        return (int) $count > 0;
    }

    private function buildPlainEmailBody(
        Customer $customer,
        string $orderReference,
        string $title,
        string $message,
        string $details,
        CustomerOrder $order
    ): string {
        $firstName = trim((string) $customer->getFirstName());
        $lines = [
            $this->emailBrandingService->buildOpening($firstName),
            '',
            $title,
            '',
            $message,
        ];

        if ($details !== '') {
            $lines[] = $details;
        }

        $lines[] = '';
        $lines[] = sprintf('Commande : %s', $orderReference);
        $lines[] = sprintf('Total : %s €', number_format((float) $order->getTotal(), 2, ',', ' '));

        $commune = trim((string) $order->getDeliveryAddressCommune());
        if ($commune !== '') {
            $lines[] = sprintf('Commune de livraison : %s', $commune);
        }

        $lines[] = '';
        $lines[] = $this->emailBrandingService->getNoReplyNotice();
        $lines[] = '';
        foreach ($this->emailBrandingService->buildPlainClosingLines() as $line) {
            $lines[] = $line;
        }

        return implode("\n", $lines);
    }

    private function getCustomerFirstNameForEmail(Customer $customer): string
    {
        return trim((string) $customer->getFirstName());
    }

    private function getCustomerFirstName(Customer $customer): string
    {
        $firstName = trim((string) $customer->getFirstName());

        return $firstName !== '' ? $firstName : 'client';
    }

    private function getCustomerPhone(Customer $customer): string
    {
        $phone = trim((string) $customer->getPhone());

        if ($phone === '' || preg_replace('/\D+/', '', $phone) === '0000000000') {
            return '';
        }

        return $phone;
    }

    private function getCustomerEmail(Customer $customer): string
    {
        $email = trim((string) $customer->getEmail());

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return '';
        }

        $normalizedEmail = mb_strtolower($email);
        $platformEmails = array_unique(array_filter([
            'contact@hodina.fr',
            mb_strtolower(trim($this->mailerFrom)),
        ]));

        return in_array($normalizedEmail, $platformEmails, true) ? '' : $email;
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
