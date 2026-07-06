<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260706180000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5AD-3: seed the ai_chatbot_enabled feature flag in hodina_setting (default: disabled).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at)
SELECT 'ai_chatbot_enabled', 'Chatbot IA activé', '0',
    'Active ou désactive complètement le chatbot IA pour les clients connectés. Le formulaire de contact anonyme reste actif dans tous les cas. Aucun redéploiement nécessaire pour changer ce réglage.',
    'boolean', 'technical', 'Technique / maintenance', 900, 1, 0, NOW()
WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'ai_chatbot_enabled')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key = 'ai_chatbot_enabled'");
    }
}
