<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\HodinaSetting;
use Doctrine\ORM\EntityManagerInterface;

final class EmailBrandingService
{
    private const DEFAULT_OPENING_FORMULA = 'Bonjour';
    private const DEFAULT_CLOSING_FORMULA = 'Merci,';
    private const DEFAULT_SIGNATURE = 'L’équipe Hodina';
    private const DEFAULT_SENDER_NAME = 'Hodina';
    private const DEFAULT_SENDER_EMAIL = 'commande@hodina.fr';
    private const DEFAULT_REPLY_TO_NAME = 'Service commande Hodina';
    private const DEFAULT_REPLY_TO_EMAIL = 'commande@hodina.fr';
    private const DEFAULT_NO_REPLY_NOTICE = 'Merci de ne pas répondre directement à cet e-mail. Pour toute question, contacte Hodina par les canaux indiqués sur le site.';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function brandSubject(string $subject): string
    {
        $subject = trim($subject);
        $prefix = $this->getSubjectPrefix();

        if ($prefix === '') {
            return $subject;
        }

        if ($subject === '') {
            return $prefix;
        }

        if (mb_strtolower(mb_substr($subject, 0, mb_strlen($prefix))) === mb_strtolower($prefix)) {
            return $subject;
        }

        return sprintf('%s %s', $prefix, $subject);
    }

    public function getSubjectPrefix(): string
    {
        return trim($this->getSettingValue(HodinaSetting::KEY_EMAIL_BRANDING_SUBJECT_PREFIX, ''));
    }

    public function buildOpening(?string $recipientName = null): string
    {
        $formula = $this->normalizeFormula($this->getSettingValue(
            HodinaSetting::KEY_EMAIL_BRANDING_OPENING_FORMULA,
            self::DEFAULT_OPENING_FORMULA
        ), self::DEFAULT_OPENING_FORMULA);

        $recipientName = trim((string) $recipientName);

        if ($recipientName === '') {
            return $formula.',';
        }

        return sprintf('%s %s,', $formula, $recipientName);
    }

    public function getClosingFormula(): string
    {
        return $this->normalizeFormula($this->getSettingValue(
            HodinaSetting::KEY_EMAIL_BRANDING_CLOSING_FORMULA,
            self::DEFAULT_CLOSING_FORMULA
        ), self::DEFAULT_CLOSING_FORMULA, keepTrailingComma: true);
    }

    public function getSignature(): string
    {
        $signature = trim($this->getSettingValue(
            HodinaSetting::KEY_EMAIL_BRANDING_SIGNATURE,
            self::DEFAULT_SIGNATURE
        ));

        return $signature !== '' ? $signature : self::DEFAULT_SIGNATURE;
    }

    /**
     * @return array{subjectPrefix: string, opening: string, closing: string, signature: string}
     */
    public function buildContext(?string $recipientName = null): array
    {
        return [
            'subjectPrefix' => $this->getSubjectPrefix(),
            'opening' => $this->buildOpening($recipientName),
            'closing' => $this->getClosingFormula(),
            'signature' => $this->getSignature(),
        ];
    }


    public function getNoReplyNotice(): string
    {
        return self::DEFAULT_NO_REPLY_NOTICE;
    }

    public function getSenderSettings(string $fallbackEmail = self::DEFAULT_SENDER_EMAIL, string $fallbackName = self::DEFAULT_SENDER_NAME): EmailSenderSettings
    {
        $senderEmail = $this->normalizeEmail($this->getSettingValue(
            HodinaSetting::KEY_EMAIL_SENDER_EMAIL,
            self::DEFAULT_SENDER_EMAIL
        ));
        if ($senderEmail === null) {
            $senderEmail = $this->normalizeEmail($fallbackEmail) ?? self::DEFAULT_SENDER_EMAIL;
        }

        $senderName = trim($this->getSettingValue(
            HodinaSetting::KEY_EMAIL_SENDER_NAME,
            $fallbackName !== '' ? $fallbackName : self::DEFAULT_SENDER_NAME
        ));
        if ($senderName === '') {
            $senderName = self::DEFAULT_SENDER_NAME;
        }

        $replyToEmail = $this->normalizeEmail($this->getSettingValue(
            HodinaSetting::KEY_EMAIL_REPLY_TO_EMAIL,
            self::DEFAULT_REPLY_TO_EMAIL
        ));
        $replyToName = trim($this->getSettingValue(
            HodinaSetting::KEY_EMAIL_REPLY_TO_NAME,
            self::DEFAULT_REPLY_TO_NAME
        ));

        $orderCreatedCopyEmail = $this->normalizeEmail($this->getSettingValue(
            HodinaSetting::KEY_EMAIL_ORDER_CREATED_COPY_EMAIL,
            self::DEFAULT_SENDER_EMAIL
        ));

        return new EmailSenderSettings(
            $senderEmail,
            $senderName,
            $replyToEmail,
            $replyToName !== '' ? $replyToName : null,
            $orderCreatedCopyEmail
        );
    }

    /** @return list<string> */
    public function buildPlainClosingLines(): array
    {
        return [$this->getClosingFormula(), $this->getSignature()];
    }

    private function getSettingValue(string $key, string $default): string
    {
        $setting = $this->entityManager->getRepository(HodinaSetting::class)->findOneBy(['settingKey' => $key]);

        if (!$setting instanceof HodinaSetting) {
            return $default;
        }

        return $setting->getValueOrDefault($default);
    }

    private function normalizeEmail(string $email): ?string
    {
        $email = mb_strtolower(trim($email));

        if ($email === '' || filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            return null;
        }

        return $email;
    }

    private function normalizeFormula(string $formula, string $default, bool $keepTrailingComma = false): string
    {
        $formula = trim($formula);

        if ($formula === '') {
            $formula = $default;
        }

        if ($keepTrailingComma) {
            return rtrim($formula, " \t\n\r\0\x0B").(str_ends_with($formula, ',') ? '' : ',');
        }

        return rtrim($formula, " \t\n\r\0\x0B,");
    }
}
