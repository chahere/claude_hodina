<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\AiChatbotSetting;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class AiChatbotSettingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AiChatbotSetting::class);
    }

    public function getOrCreateSingleton(): AiChatbotSetting
    {
        $setting = $this->findOneBy([], ['id' => 'ASC']);

        if ($setting instanceof AiChatbotSetting) {
            return $setting;
        }

        $setting = new AiChatbotSetting();
        $this->getEntityManager()->persist($setting);
        $this->getEntityManager()->flush();

        return $setting;
    }
}
