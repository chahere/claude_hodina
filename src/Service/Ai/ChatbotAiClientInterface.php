<?php

declare(strict_types=1);

namespace App\Service\Ai;

use App\Dto\ChatbotAiReply;
use App\Dto\ChatbotAiRequest;

interface ChatbotAiClientInterface
{
    public function getProviderKey(): string;

    public function generateReply(ChatbotAiRequest $request): ChatbotAiReply;
}
