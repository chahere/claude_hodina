<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\Mime\Address;

final class EmailSenderSettings
{
    public function __construct(
        private readonly string $senderEmail,
        private readonly string $senderName,
        private readonly ?string $replyToEmail,
        private readonly ?string $replyToName,
        private readonly ?string $orderCreatedCopyEmail,
    ) {
    }

    public function senderEmail(): string
    {
        return $this->senderEmail;
    }

    public function senderName(): string
    {
        return $this->senderName;
    }

    public function fromAddress(): Address
    {
        return new Address($this->senderEmail, $this->senderName);
    }

    public function replyToEmail(): ?string
    {
        return $this->replyToEmail;
    }

    public function replyToName(): ?string
    {
        return $this->replyToName;
    }

    public function replyToAddress(): ?Address
    {
        if ($this->replyToEmail === null) {
            return null;
        }

        return new Address($this->replyToEmail, $this->replyToName ?? '');
    }

    public function orderCreatedCopyEmail(): ?string
    {
        return $this->orderCreatedCopyEmail;
    }
}
