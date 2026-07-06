<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622211500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5N-D-bis: create default timezone global setting for EasyAdmin.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, updated_at) SELECT 'default_timezone', 'Fuseau horaire par défaut', 'Indian/Mayotte', 'Fuseau utilisé si le navigateur ne transmet pas de fuseau horaire valide au moment de la commande. La détection automatique de la commande reste prioritaire.', 'choice', CURRENT_TIMESTAMP WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'default_timezone')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key = 'default_timezone'");
    }
}
