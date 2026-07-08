<?php

declare(strict_types=1);

namespace App\Dto;

final class ChatbotAiReply
{
    private function __construct(
        public readonly bool $successful,
        public readonly string $replyText,
        public readonly ?string $errorReason,
    ) {
    }

    public static function success(string $replyText): self
    {
        return new self(true, $replyText, null);
    }

    public static function failure(string $errorReason): self
    {
        return new self(false, '', $errorReason);
    }
}
