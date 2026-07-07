<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260707040000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5AE: seed the support_messenger_url setting (widget assistant escalation link, empty by default).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql(<<<'SQL'
INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at)
SELECT 'support_messenger_url', 'Lien Messenger support', NULL,
    'URL Messenger affichée dans le widget Assistant Hodina après une escalade (bouton « Continuer sur Messenger »). Laisser vide pour masquer le bouton. Simple lien public : aucun jeton Meta ici, l’intégration Messenger API réelle viendra plus tard.',
    'url', 'technical', 'Technique / maintenance', 910, 1, 0, NOW()
WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'support_messenger_url')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key = 'support_messenger_url'");
    }
}
