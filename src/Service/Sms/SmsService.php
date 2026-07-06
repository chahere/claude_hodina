<?php

namespace App\Service\Sms;

use App\Entity\CustomerOrder;
use App\Entity\SmsLog;
use Doctrine\ORM\EntityManagerInterface;

final class SmsService
{
    public function __construct(
        private readonly SmsSenderInterface $sender,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function sendForOrder(
        CustomerOrder $order,
        string $phone,
        string $message,
        string $context,
        string $recipientType = 'customer'
    ): SmsLog {
        $smsLog = (new SmsLog())
            ->setCustomerOrder($order)
            ->setPhone($this->normalizePhone($phone))
            ->setRecipientType($recipientType)
            ->setContext($context)
            ->setMessage($this->normalizeMessage($message));

        if ($smsLog->getPhone() === '') {
            $smsLog
                ->setStatus(SmsLog::STATUS_FAILED)
                ->setProvider('none')
                ->setErrorMessage('Numéro de téléphone manquant.');

            $this->entityManager->persist($smsLog);
            $this->entityManager->flush();

            return $smsLog;
        }

        if ($smsLog->getMessage() === '') {
            $smsLog
                ->setStatus(SmsLog::STATUS_FAILED)
                ->setProvider('none')
                ->setErrorMessage('Message SMS vide.');

            $this->entityManager->persist($smsLog);
            $this->entityManager->flush();

            return $smsLog;
        }

        try {
            $result = $this->sender->send($smsLog->getPhone(), $smsLog->getMessage());

            $smsLog
                ->setProvider($result->getProvider())
                ->setProviderMessageId($result->getProviderMessageId())
                ->setStatus($result->isSuccess() ? SmsLog::STATUS_SENT : SmsLog::STATUS_FAILED)
                ->setErrorMessage($result->getErrorMessage())
                ->setSentAt($result->isSuccess() ? new \DateTimeImmutable() : null);
        } catch (\Throwable $throwable) {
            $smsLog
                ->setStatus(SmsLog::STATUS_FAILED)
                ->setProvider('exception')
                ->setErrorMessage($throwable->getMessage());
        }

        $this->entityManager->persist($smsLog);
        $this->entityManager->flush();

        return $smsLog;
    }

    private function normalizePhone(string $phone): string
    {
        return trim($phone);
    }

    private function normalizeMessage(string $message): string
    {
        return trim(preg_replace('/\s+/', ' ', $message) ?? $message);
    }
}
