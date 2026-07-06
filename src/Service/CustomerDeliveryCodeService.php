<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\EmailLog;
use App\Entity\SmsLog;
use App\Service\Sms\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class CustomerDeliveryCodeService
{
    public const RESULT_CODE_SENT = 'CODE_SENT';
    public const RESULT_VALIDATED = 'VALIDATED';

    private const CIPHER = 'aes-256-gcm';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SmsService $smsService,
        private readonly MailerInterface $mailer,
        private readonly OrderReferenceGenerator $orderReferenceGenerator,
        private readonly EmailBrandingService $emailBrandingService,
        #[Autowire('%kernel.secret%')]
        private readonly string $appSecret,
        private readonly string $mailerFrom = 'contact@hodina.fr',
        private readonly string $mailerFromName = 'Hodina',
    ) {
    }

    /**
     * Génère le code de réception client au passage en livraison et l'envoie.
     * Le code est stocké chiffré pour permettre le renvoi du même code jusqu'à
     * validation finale, sans le conserver en clair en base.
     *
     * @return array{status: string, message: string}
     */
    public function generateAndSendForStartedDelivery(CustomerOrder $order, Customer $courier): array
    {
        if ($order->getStatus() !== CustomerOrder::STATUS_OUT_FOR_DELIVERY
            || $order->getAssignedCourier()?->getId() !== $courier->getId()
        ) {
            throw new \DomainException('Le code client ne peut être généré qu’une fois la commande en livraison par le livreur assigné.');
        }

        if (!$order->hasPendingDeliveryValidationCode()) {
            $code = $this->generateCode();
            $order->prepareDeliveryValidationCode($this->encryptCode($code));
            $this->entityManager->flush();
        } else {
            $code = $this->decryptCode((string) $order->getDeliveryValidationCodeEncrypted());
        }

        $this->sendExistingCode($order, $code);

        return [
            'status' => self::RESULT_CODE_SENT,
            'message' => sprintf('Commande %s en livraison. Code de réception envoyé au client par les canaux disponibles.', $this->getOrderReference($order)),
        ];
    }

    /**
     * Si aucun code n'est saisi, renvoie le même code au client.
     * Si un code est saisi, vérifie le code et autorise la livraison.
     *
     * @return array{status: string, message: string}
     */
    public function validateOrResendCode(CustomerOrder $order, Customer $courier, ?string $submittedCode): array
    {
        if ($order->getStatus() !== CustomerOrder::STATUS_OUT_FOR_DELIVERY
            || $order->getAssignedCourier()?->getId() !== $courier->getId()
        ) {
            throw new \DomainException('Seul le livreur assigné peut valider la réception de cette commande.');
        }

        $submittedCode = $this->normalizeCode($submittedCode);

        if (!$order->hasPendingDeliveryValidationCode()) {
            $code = $this->generateCode();
            $order->prepareDeliveryValidationCode($this->encryptCode($code));
            $this->entityManager->flush();
        } else {
            $code = $this->decryptCode((string) $order->getDeliveryValidationCodeEncrypted());
        }

        if ($submittedCode === '') {
            $this->sendExistingCode($order, $code);

            return [
                'status' => self::RESULT_CODE_SENT,
                'message' => sprintf('Code de réception renvoyé au client pour la commande %s.', $this->getOrderReference($order)),
            ];
        }

        if (!hash_equals($this->normalizeCode($code), $submittedCode)) {
            $order->incrementDeliveryValidationCodeFailedAttempt();
            $this->entityManager->flush();

            throw new \DomainException('Code client incorrect. La commande n’est pas marquée comme livrée.');
        }

        return [
            'status' => self::RESULT_VALIDATED,
            'message' => sprintf('Code client validé pour la commande %s.', $this->getOrderReference($order)),
        ];
    }

    public function markValidatedAndClearCode(CustomerOrder $order): void
    {
        $order->markDeliveryValidationCodeValidated();
        $this->entityManager->flush();
    }

    private function sendExistingCode(CustomerOrder $order, string $code): void
    {
        $smsLog = $this->sendSmsCode($order, $code);
        $emailLog = $this->sendEmailCode($order, $code);

        $order->registerDeliveryValidationCodeDispatch($smsLog?->getId(), $emailLog?->getId());
        $this->entityManager->flush();

        if ($this->isFailedOrEmpty($smsLog) && $this->isFailedOrEmpty($emailLog)) {
            throw new \DomainException('Code client généré, mais aucun SMS/e-mail n’a pu être envoyé. Vérifie les coordonnées client avant de finaliser la livraison.');
        }
    }

    private function sendSmsCode(CustomerOrder $order, string $code): SmsLog
    {
        $customer = $order->getCustomer();
        $phone = $this->getCustomerPhone($customer);
        $orderReference = $this->getOrderReference($order);
        $message = sprintf(
            'Hodina - code de réception pour la commande %s : %s. Donne ce code au livreur uniquement quand la commande est bien remise.',
            $orderReference,
            $code
        );

        return $this->smsService->sendForOrder(
            $order,
            $phone,
            $message,
            'customer_delivery_code',
            'customer'
        );
    }

    private function sendEmailCode(CustomerOrder $order, string $code): EmailLog
    {
        $customer = $order->getCustomer();
        $recipientEmail = $this->getCustomerEmail($customer);
        $orderReference = $this->getOrderReference($order);
        $subject = $this->emailBrandingService->brandSubject(sprintf('Code réception Hodina %s', $orderReference));
        $recipientName = $this->getCustomerFirstNameForEmail($customer);
        $body = $this->buildPlainEmailBody($customer, $orderReference, $code);
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
            ->setTemplateKey('emails/customer_delivery_code.html.twig')
            ->setEventKey(EmailLog::EVENT_CUSTOMER_DELIVERY_CODE)
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
                ->htmlTemplate('emails/customer_delivery_code.html.twig')
                ->context([
                    'order' => $order,
                    'customer' => $customer,
                    'code' => $code,
                    'orderReference' => $orderReference,
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
                ->setErrorMessage($exception->getMessage());
        }

        $this->entityManager->persist($emailLog);
        $this->entityManager->flush();

        return $emailLog;
    }

    private function buildPlainEmailBody(Customer $customer, string $orderReference, string $code): string
    {
        $firstName = trim((string) $customer->getFirstName());

        return implode("\n", [
            $this->emailBrandingService->buildOpening($firstName),
            '',
            sprintf('Votre commande Hodina %s est en cours de livraison.', $orderReference),
            sprintf('Code réception : %s', $code),
            '',
            'Donnez ce code au livreur uniquement lorsque la commande est bien remise.',
            'Ce code permet à Hodina de confirmer la réception de votre commande.',
            '',
            $this->emailBrandingService->getNoReplyNotice(),
            '',
            ...$this->emailBrandingService->buildPlainClosingLines(),
        ]);
    }

    private function getCustomerFirstNameForEmail(Customer $customer): string
    {
        return trim((string) $customer->getFirstName());
    }

    private function generateCode(): string
    {
        return (string) random_int(100000, 999999);
    }

    private function encryptCode(string $code): string
    {
        $key = $this->getEncryptionKey();
        $iv = random_bytes(12);
        $tag = '';
        $ciphertext = openssl_encrypt($code, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);

        if ($ciphertext === false) {
            throw new \RuntimeException('Impossible de chiffrer le code de réception client.');
        }

        $payload = [
            'v' => 1,
            'alg' => self::CIPHER,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'data' => base64_encode($ciphertext),
        ];

        return base64_encode(json_encode($payload, JSON_THROW_ON_ERROR));
    }

    private function decryptCode(string $encryptedCode): string
    {
        $decodedPayload = base64_decode($encryptedCode, true);
        if ($decodedPayload === false) {
            throw new \DomainException('Code de réception client illisible.');
        }

        try {
            $payload = json_decode($decodedPayload, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            throw new \DomainException('Code de réception client invalide.');
        }

        if (!is_array($payload) || ($payload['alg'] ?? '') !== self::CIPHER) {
            throw new \DomainException('Code de réception client invalide.');
        }

        $iv = base64_decode((string) ($payload['iv'] ?? ''), true);
        $tag = base64_decode((string) ($payload['tag'] ?? ''), true);
        $ciphertext = base64_decode((string) ($payload['data'] ?? ''), true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            throw new \DomainException('Code de réception client incomplet.');
        }

        $code = openssl_decrypt($ciphertext, self::CIPHER, $this->getEncryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($code === false) {
            throw new \DomainException('Code de réception client impossible à déchiffrer.');
        }

        return $code;
    }

    private function getEncryptionKey(): string
    {
        if (trim($this->appSecret) === '') {
            throw new \RuntimeException('APP_SECRET manquant : impossible de chiffrer les codes de réception client.');
        }

        return hash('sha256', $this->appSecret, true);
    }

    private function getOrderReference(CustomerOrder $order): string
    {
        return $this->orderReferenceGenerator->ensureReference($order);
    }

    private function isFailedOrEmpty(null|SmsLog|EmailLog $log): bool
    {
        if (!$log instanceof SmsLog && !$log instanceof EmailLog) {
            return true;
        }

        return $log->getStatus() !== SmsLog::STATUS_SENT
            && $log->getStatus() !== EmailLog::STATUS_SENT;
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

    private function normalizeCode(?string $code): string
    {
        $code = $code !== null ? trim((string) $code) : '';
        $code = preg_replace('/\s+/', '', $code) ?? $code;

        return mb_strtoupper($code);
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
