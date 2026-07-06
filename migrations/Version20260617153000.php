<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617153000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5G-B4: add global multi-seller customer delivery fee settings';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, updated_at) VALUES ('global_multi_seller_extra_customer_fee', 'Supplément client par vendeur supplémentaire (€)', '0.00', 'Supplément ajouté aux frais client pour chaque vendeur distinct au-delà du premier. Exemple : 2 vendeurs = 1 supplément ; 3 vendeurs = 2 suppléments, puis application du plafond éventuel.', 'text', CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE setting_key = setting_key");
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, updated_at) VALUES ('global_multi_seller_extra_customer_fee_cap', 'Plafond supplément client multivendeur (€)', '0.00', 'Plafond du supplément multivendeur côté client. Valeur 0 ou vide = pas de plafond spécifique. Le plafond global des frais de livraison client reste appliqué ensuite.', 'text', CURRENT_TIMESTAMP) ON DUPLICATE KEY UPDATE setting_key = setting_key");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key IN ('global_multi_seller_extra_customer_fee', 'global_multi_seller_extra_customer_fee_cap')");
    }
}
