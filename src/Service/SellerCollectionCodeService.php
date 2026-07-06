<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\CustomerOrder;
use App\Entity\EmailLog;
use App\Entity\Seller;
use App\Entity\SmsLog;
use App\Service\Sms\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

final class SellerCollectionCodeService
{
    public const RESULT_CODE_SENT = 'CODE_SENT';
    public const RESULT_COLLECTED = 'COLLECTED';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly SmsService $smsService,
        private readonly MailerInterface $mailer,
        private readonly OrderReferenceGenerator $orderReferenceGenerator,
        private readonly EmailBrandingService $emailBrandingService,
        private readonly string $mailerFrom = 'contact@hodina.fr',
        private readonly string $mailerFromName = 'Hodina',
    ) {
    }

    /**
     * @return array{status: string, message: string}
     */
    public function validateOrSendCode(
        CustomerOrder $order,
        Seller $seller,
        Customer $courier,
        ?string $submittedCode,
        ?string $note = null
    ): array {
        if ($order->getStatus() !== CustomerOrder::STATUS_PICKED_UP
            || $order->getAssignedCourier()?->getId() !== $courier->getId()
        ) {
            throw new \DomainException('Tu dois prendre en charge la commande avant de valider une collecte vendeur.');
        }

        if (!$order->containsSeller($seller)) {
            throw new \DomainException('Ce vendeur ne fait pas partie de cette commande.');
        }

        if ($order->isSellerCollected($seller)) {
            throw new \DomainException('Cette collecte vendeur est déjà validée.');
        }

        $submittedCode = $this->normalizeCode($submittedCode);
        $defaultCode = $this->normalizeCode($seller->getCollectionValidationCode());

        if ($defaultCode !== '') {
            if ($submittedCode === '') {
                throw new \DomainException('Demande le code de collecte au vendeur, puis saisis-le pour valider le retrait.');
            }

            if (!hash_equals($defaultCode, $submittedCode)) {
                $order->incrementSellerCollectionFailedAttempt($seller);
                $this->entityManager->flush();

                throw new \DomainException('Code vendeur incorrect. La collecte n’est pas validée.');
            }

            $order->markSellerCollected($seller, $courier, $note, 'DEFAULT_SELLER_CODE');
            $this->entityManager->flush();

            return [
                'status' => self::RESULT_COLLECTED,
                'message' => sprintf('Collecte validée pour %s avec le code vendeur configuré.', $seller->getCourierDisplayName()),
            ];
        }

        if ($submittedCode === '') {
            $this->generateAndSendCode($order, $seller, $courier);

            return [
                'status' => self::RESULT_CODE_SENT,
                'message' => sprintf('Code de collecte envoyé à %s. Demande au vendeur de te le communiquer, puis saisis-le pour valider la collecte.', $seller->getCourierDisplayName()),
            ];
        }

        $entry = $order->getSellerCollectionEntry($seller);
        $codeHash = is_array($entry) ? trim((string) ($entry['codeHash'] ?? '')) : '';

        if ($codeHash === '') {
            throw new \DomainException('Aucun code ponctuel n’a encore été envoyé à ce vendeur. Relance la validation sans saisir de code pour envoyer un code.');
        }

        if (!hash_equals($codeHash, $this->hashCode($submittedCode))) {
            $order->incrementSellerCollectionFailedAttempt($seller);
            $this->entityManager->flush();

            throw new \DomainException('Code vendeur incorrect. La collecte n’est pas validée.');
        }

        $order->markSellerCollected($seller, $courier, $note, 'GENERATED_SELLER_CODE');
        $this->entityManager->flush();

        return [
            'status' => self::RESULT_COLLECTED,
            'message' => sprintf('Collecte validée pour %s avec le code envoyé au vendeur.', $seller->getCourierDisplayName()),
        ];
    }

    private function generateAndSendCode(CustomerOrder $order, Seller $seller, Customer $courier): void
    {
        $code = (string) random_int(100000, 999999);
        $order->prepareSellerCollectionCode($seller, $this->hashCode($code), $courier);
        $this->entityManager->flush();

        $smsLog = $this->sendSmsCode($order, $seller, $code);
        $emailLog = $this->sendEmailCode($order, $seller, $code);

        $order->updateSellerCollectionCodeLogs($seller, $smsLog?->getId(), $emailLog?->getId());
        $this->entityManager->flush();

        if ($this->isFailedOrEmpty($smsLog) && $this->isFailedOrEmpty($emailLog)) {
            throw new \DomainException('Code généré, mais aucun SMS/e-mail vendeur n’a pu être envoyé. Configure un code de collecte par défaut ou les coordonnées du vendeur.');
        }
    }

    private function sendSmsCode(CustomerOrder $order, Seller $seller, string $code): ?SmsLog
    {
        $phone = $this->getSellerPhone($seller);
        $orderReference = $this->orderReferenceGenerator->ensureReference($order);
        $message = sprintf(
            'Hodina - code collecte vendeur pour la commande %s : %s. Donne ce code au livreur uniquement quand les produits sont remis.',
            $orderReference,
            $code
        );

        return $this->smsService->sendForOrder(
            $order,
            $phone,
            $message,
            'seller_collection_code',
            'seller'
        );
    }

    private function sendEmailCode(CustomerOrder $order, Seller $seller, string $code): EmailLog
    {
        $recipientEmail = $this->getSellerEmail($seller);
        $orderReference = $this->orderReferenceGenerator->ensureReference($order);
        $subject = $this->emailBrandingService->brandSubject(sprintf('Code collecte Hodina %s', $orderReference));
        $recipientName = trim($seller->getCourierDisplayName());
        $body = $this->buildPlainEmailBody($seller, $orderReference, $code);
        $emailSenderSettings = $this->emailBrandingService->getSenderSettings($this->mailerFrom, $this->mailerFromName);

        $emailLog = (new EmailLog())
            ->setCustomerOrder($order)
            ->setCustomer($seller->getCustomerAccount())
            ->setRecipientEmail($recipientEmail)
            ->setFromEmail($emailSenderSettings->senderEmail())
            ->setFromName($emailSenderSettings->senderName())
            ->setReplyToEmail($emailSenderSettings->replyToEmail())
            ->setReplyToName($emailSenderSettings->replyToName())
            ->setSubject($subject)
            ->setBody($body)
            ->setTemplateKey('emails/seller_collection_code.html.twig')
            ->setEventKey(EmailLog::EVENT_SELLER_COLLECTION_CODE)
            ->setStatus(EmailLog::STATUS_PENDING);

        // EmailLog est actuellement mappé avec des colonnes Doctrine `datetime`
        // mutables, alors que son constructeur initialise `createdAt` avec un
        // DateTimeImmutable. Pour ne pas modifier toute l'entité dans ce correctif
        // ciblé J5N-A, on normalise les champs date du log avant persistance.
        $this->forceMutableDateTimeFields($emailLog);

        if ($recipientEmail === '') {
            $emailLog
                ->setStatus(EmailLog::STATUS_FAILED)
                ->setErrorMessage('Adresse e-mail vendeur manquante.');

            $this->entityManager->persist($emailLog);
            $this->entityManager->flush();

            return $emailLog;
        }

        try {
            $email = (new TemplatedEmail())
                ->from($emailSenderSettings->fromAddress())
                ->to($recipientEmail)
                ->subject($subject)
                ->htmlTemplate('emails/seller_collection_code.html.twig')
                ->context([
                    'order' => $order,
                    'seller' => $seller,
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

    private function buildPlainEmailBody(Seller $seller, string $orderReference, string $code): string
    {
        $sellerLabel = trim($seller->getCourierDisplayName());

        return implode("\n", [
            $this->emailBrandingService->buildOpening($sellerLabel),
            '',
            sprintf('Un livreur Hodina est venu récupérer les produits de la commande %s.', $orderReference),
            sprintf('Code collecte : %s', $code),
            '',
            'Donne ce code au livreur uniquement quand les produits sont remis.',
            'Ce code permet à Hodina de confirmer que la collecte vendeur a bien eu lieu.',
            '',
            $this->emailBrandingService->getNoReplyNotice(),
            '',
            ...$this->emailBrandingService->buildPlainClosingLines(),
        ]);
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

    private function isFailedOrEmpty(null|SmsLog|EmailLog $log): bool
    {
        if (!$log instanceof SmsLog && !$log instanceof EmailLog) {
            return true;
        }

        return $log->getStatus() !== SmsLog::STATUS_SENT
            && $log->getStatus() !== EmailLog::STATUS_SENT;
    }

    private function getSellerPhone(Seller $seller): string
    {
        foreach ([$seller->getPhone(), $seller->getCustomerAccount()?->getPhone()] as $phone) {
            $phone = trim((string) $phone);

            if ($this->isUsableSellerPhone($phone)) {
                return $phone;
            }
        }

        return '';
    }

    private function getSellerEmail(Seller $seller): string
    {
        foreach ([$seller->getEmail(), $seller->getCustomerAccount()?->getEmail()] as $email) {
            $email = trim((string) $email);

            if ($this->isUsableSellerEmail($email)) {
                return $email;
            }
        }

        return '';
    }

    private function isUsableSellerPhone(string $phone): bool
    {
        if ($phone === '') {
            return false;
        }

        // Téléphone temporaire utilisé pour créer certains comptes vendeurs legacy.
        // Il ne doit jamais recevoir un code de collecte.
        if (preg_replace('/\D+/', '', $phone) === '0000000000') {
            return false;
        }

        return true;
    }

    private function isUsableSellerEmail(string $email): bool
    {
        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return false;
        }

        $normalizedEmail = mb_strtolower($email);
        $platformEmails = array_unique(array_filter([
            'contact@hodina.fr',
            mb_strtolower(trim($this->mailerFrom)),
        ]));

        // contact@hodina.fr est l'expéditeur Hodina, pas un destinataire vendeur.
        // Si un vendeur legacy n'a pas de customer_id ou utilise ce placeholder,
        // on logue un échec au lieu d'envoyer le code à Hodina.
        return !in_array($normalizedEmail, $platformEmails, true);
    }

    private function normalizeCode(?string $code): string
    {
        $code = $code !== null ? trim((string) $code) : '';
        $code = preg_replace('/\s+/', '', $code) ?? $code;

        return mb_strtoupper($code);
    }

    private function hashCode(string $code): string
    {
        return hash('sha256', $this->normalizeCode($code));
    }
}
