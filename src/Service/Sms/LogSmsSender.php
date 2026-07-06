<?php

namespace App\Service\Sms;

use Psr\Log\LoggerInterface;

final class LogSmsSender implements SmsSenderInterface
{
    public function __construct(private readonly LoggerInterface $logger)
    {
    }

    public function send(string $to, string $message): SmsSendResult
    {
        $this->logger->info('SMS Hodina simulé', [
            'to' => $to,
            'message' => $message,
        ]);

        return SmsSendResult::success('log');
    }
}
