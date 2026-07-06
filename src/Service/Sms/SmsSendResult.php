<?php

namespace App\Service\Sms;

final class SmsSendResult
{
    public function __construct(
        private readonly bool $success,
        private readonly string $provider = 'log',
        private readonly ?string $providerMessageId = null,
        private readonly ?string $errorMessage = null,
    ) {
    }

    public static function success(string $provider = 'log', ?string $providerMessageId = null): self
    {
        return new self(true, $provider, $providerMessageId);
    }

    public static function failure(string $errorMessage, string $provider = 'log'): self
    {
        return new self(false, $provider, null, $errorMessage);
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getProvider(): string
    {
        return $this->provider;
    }

    public function getProviderMessageId(): ?string
    {
        return $this->providerMessageId;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
}
