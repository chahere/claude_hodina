<?php

declare(strict_types=1);

namespace App\Dto;

final class ChatbotAiRequest
{
    /**
     * @param list<array{role: string, content: string}> $history
     */
    public function __construct(
        public readonly string $systemPrompt,
        public readonly array $history,
        public readonly string $userMessage,
        public readonly string $model,
        public readonly ?string $apiKey,
    ) {
    }
}
