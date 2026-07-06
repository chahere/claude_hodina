<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604115858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE hodina_setting ADD setting_key VARCHAR(80) NOT NULL, ADD label VARCHAR(120) NOT NULL, ADD help LONGTEXT DEFAULT NULL, CHANGE delivered_communes value LONGTEXT DEFAULT NULL, CHANGE order_reference_prefix field_type VARCHAR(30) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_HODINA_SETTING_KEY ON hodina_setting (setting_key)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_HODINA_SETTING_KEY ON hodina_setting');
        $this->addSql('ALTER TABLE hodina_setting ADD delivered_communes LONGTEXT DEFAULT NULL, DROP setting_key, DROP label, DROP value, DROP help, CHANGE field_type order_reference_prefix VARCHAR(30) NOT NULL');
    }
}
