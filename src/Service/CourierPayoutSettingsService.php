<?php

namespace App\Service;

use App\Entity\HodinaSetting;
use Doctrine\ORM\EntityManagerInterface;

final class CourierPayoutSettingsService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    public function isCourierPayoutEnabled(): bool
    {
        return $this->getBool(HodinaSetting::KEY_COURIER_PAYOUTS_ENABLED, true);
    }

    public function isCronGenerationEnabled(): bool
    {
        return $this->getBool(HodinaSetting::KEY_COURIER_PAYOUT_CRON_ENABLED, true);
    }

    public function isAdminRecapEnabled(): bool
    {
        return $this->getBool(HodinaSetting::KEY_COURIER_PAYOUT_ADMIN_RECAP_ENABLED, true);
    }

    public function getFrequency(): string
    {
        $frequency = $this->getText(
            HodinaSetting::KEY_COURIER_PAYOUT_FREQUENCY,
            HodinaSetting::COURIER_PAYOUT_FREQUENCY_SEMI_MONTHLY
        );

        return $frequency !== '' ? $frequency : HodinaSetting::COURIER_PAYOUT_FREQUENCY_SEMI_MONTHLY;
    }

    public function isSemiMonthlyFrequency(): bool
    {
        return $this->getFrequency() === HodinaSetting::COURIER_PAYOUT_FREQUENCY_SEMI_MONTHLY;
    }

    private function getText(string $key, string $default): string
    {
        $setting = $this->entityManager
            ->getRepository(HodinaSetting::class)
            ->findOneBy(['settingKey' => $key]);

        if (!$setting instanceof HodinaSetting) {
            return $default;
        }

        return $setting->getValueOrDefault($default);
    }

    private function getBool(string $key, bool $default): bool
    {
        $setting = $this->entityManager
            ->getRepository(HodinaSetting::class)
            ->findOneBy(['settingKey' => $key]);

        if (!$setting instanceof HodinaSetting) {
            return $default;
        }

        return $setting->getBooleanValue();
    }
}
