<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604095052 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Auto-generated migration, made defensive: originally generated before Version20260604100000 (which actually creates hodina_setting and customer_order.order_reference_date), so it must no-op on a fresh database bootstrapped from scratch.';
    }

    public function up(Schema $schema): void
    {
        if ($this->columnExists('customer_order', 'order_reference_date')) {
            $this->addSql('ALTER TABLE customer_order CHANGE order_reference_date order_reference_date DATETIME DEFAULT NULL');
        }

        if ($this->indexExists('customer_order', 'uniq_5a6c5e95724e52bd')) {
            $this->addSql('ALTER TABLE customer_order RENAME INDEX uniq_5a6c5e95724e52bd TO UNIQ_3B1CE6A3122432EB');
        }

        if ($this->tableExists('hodina_setting') && $this->columnExists('hodina_setting', 'updated_at')) {
            $this->addSql('ALTER TABLE hodina_setting CHANGE updated_at updated_at DATETIME NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('hodina_setting') && $this->columnExists('hodina_setting', 'updated_at')) {
            $this->addSql('ALTER TABLE hodina_setting CHANGE updated_at updated_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }

        if ($this->indexExists('customer_order', 'uniq_3b1ce6a3122432eb')) {
            $this->addSql('ALTER TABLE customer_order RENAME INDEX uniq_3b1ce6a3122432eb TO UNIQ_5A6C5E95724E52BD');
        }

        if ($this->columnExists('customer_order', 'order_reference_date')) {
            $this->addSql('ALTER TABLE customer_order CHANGE order_reference_date order_reference_date DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }
    }

    private function tableExists(string $tableName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName],
        );

        return (int) $result > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName],
        );

        return (int) $result > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName],
        );

        return (int) $result > 0;
    }
}
