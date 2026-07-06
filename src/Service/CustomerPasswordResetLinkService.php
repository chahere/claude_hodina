<?php

namespace App\Service;

use App\Entity\Customer;
use App\Entity\SmsLog;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class CustomerPasswordResetLinkService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {
    }

    public function createResetLink(Customer $customer): void
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = new \DateTimeImmutable('+45 minutes');

        $customer
            ->setResetPasswordToken($token)
            ->setResetPasswordTokenExpiresAt($expiresAt);

        $resetUrl = $this->urlGenerator->generate(
            'app_reset_password',
            ['token' => $token],
            UrlGeneratorInterface::ABSOLUTE_URL
        );

        $phone = trim($customer->getPhone());
        if ($phone !== '') {
            $smsMessage = sprintf(
                'Gégé %s, voici ton lien Hodina pour réinitialiser ton mot de passe : %s. Ce lien expire dans 45 minutes.',
                trim($customer->getFirstName()) ?: 'toi',
                $resetUrl
            );

            $smsLog = (new SmsLog())
                ->setPhone($phone)
                ->setContext('customer_password_reset_link')
                ->setRecipientType('customer')
                ->setStatus(SmsLog::STATUS_SENT)
                ->setProvider('manual_iphone_link')
                ->setSentAt(new \DateTimeImmutable())
                ->setMessage($smsMessage);

            $this->entityManager->persist($smsLog);
        }

        $this->entityManager->flush();
    }
}
