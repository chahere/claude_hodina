<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add configurable Hodina order references and settings.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE hodina_setting (id INT AUTO_INCREMENT NOT NULL, order_reference_prefix VARCHAR(30) NOT NULL, updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql("INSERT INTO hodina_setting (order_reference_prefix, updated_at) VALUES ('hodina', NOW())");
        $this->addSql('ALTER TABLE customer_order ADD order_reference VARCHAR(80) DEFAULT NULL, ADD daily_order_number INT DEFAULT NULL, ADD order_reference_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_5A6C5E95724E52BD ON customer_order (order_reference)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE hodina_setting');
        $this->addSql('DROP INDEX UNIQ_5A6C5E95724E52BD ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP order_reference, DROP daily_order_number, DROP order_reference_date');
    }
}
