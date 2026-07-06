<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260604095052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order CHANGE order_reference_date order_reference_date DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order RENAME INDEX uniq_5a6c5e95724e52bd TO UNIQ_3B1CE6A3122432EB');
        $this->addSql('ALTER TABLE hodina_setting CHANGE updated_at updated_at DATETIME NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order CHANGE order_reference_date order_reference_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE customer_order RENAME INDEX uniq_3b1ce6a3122432eb TO UNIQ_5A6C5E95724E52BD');
        $this->addSql('ALTER TABLE hodina_setting CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }
}
