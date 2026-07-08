<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\HodinaSetting;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Interrupteur global du chatbot IA, piloté depuis EasyAdmin (réglage
 * HodinaSetting, groupe Technique) plutôt que par variable .env, pour rester
 * cohérent avec la convention déjà utilisée par les autres interrupteurs de
 * fonctionnalité du projet (SalesOpeningService, CourierPayoutSettingsService...).
 */
final class ChatbotFeatureSettingsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function isEnabled(): bool
    {
        $setting = $this->entityManager->getRepository(HodinaSetting::class)
            ->findOneBy(['settingKey' => HodinaSetting::KEY_AI_CHATBOT_ENABLED]);

        return $setting instanceof HodinaSetting && $setting->getBooleanValue();
    }
}
