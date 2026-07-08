<?php

namespace App\Twig;

use App\Service\ChatbotFeatureSettingsService;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

final class ChatbotFeatureExtension extends AbstractExtension
{
    public function __construct(private readonly ChatbotFeatureSettingsService $chatbotFeatureSettingsService)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('hodina_chatbot_enabled', [$this->chatbotFeatureSettingsService, 'isEnabled']),
        ];
    }
}
