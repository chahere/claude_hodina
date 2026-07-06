<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621143500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5M-C2: relie les vendeurs a un compte client et a une adresse existante de point de retrait.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->columnExists('seller', 'customer_account_id')) {
            $this->addSql('ALTER TABLE seller ADD customer_account_id INT DEFAULT NULL');
        }

        if (!$this->columnExists('seller', 'pickup_address_id')) {
            $this->addSql('ALTER TABLE seller ADD pickup_address_id INT DEFAULT NULL');
        }

        if ($this->columnExists('seller', 'customer_account_id') && !$this->indexExists('seller', 'IDX_FB1AD3FC66A25B38')) {
            $this->addSql('CREATE INDEX IDX_FB1AD3FC66A25B38 ON seller (customer_account_id)');
        }

        if ($this->columnExists('seller', 'pickup_address_id') && !$this->indexExists('seller', 'IDX_FB1AD3FCA72D874B')) {
            $this->addSql('CREATE INDEX IDX_FB1AD3FCA72D874B ON seller (pickup_address_id)');
        }

        if ($this->columnExists('seller', 'customer_account_id') && !$this->foreignKeyExists('seller', 'FK_FB1AD3FC66A25B38')) {
            $this->addSql('ALTER TABLE seller ADD CONSTRAINT FK_FB1AD3FC66A25B38 FOREIGN KEY (customer_account_id) REFERENCES customer (id) ON DELETE SET NULL');
        }

        if ($this->columnExists('seller', 'pickup_address_id') && !$this->foreignKeyExists('seller', 'FK_FB1AD3FCA72D874B')) {
            $this->addSql('ALTER TABLE seller ADD CONSTRAINT FK_FB1AD3FCA72D874B FOREIGN KEY (pickup_address_id) REFERENCES address (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->foreignKeyExists('seller', 'FK_FB1AD3FCA72D874B')) {
            $this->addSql('ALTER TABLE seller DROP FOREIGN KEY FK_FB1AD3FCA72D874B');
        }

        if ($this->foreignKeyExists('seller', 'FK_FB1AD3FC66A25B38')) {
            $this->addSql('ALTER TABLE seller DROP FOREIGN KEY FK_FB1AD3FC66A25B38');
        }

        if ($this->indexExists('seller', 'IDX_FB1AD3FCA72D874B')) {
            $this->addSql('DROP INDEX IDX_FB1AD3FCA72D874B ON seller');
        }

        if ($this->indexExists('seller', 'IDX_FB1AD3FC66A25B38')) {
            $this->addSql('DROP INDEX IDX_FB1AD3FC66A25B38 ON seller');
        }

        if ($this->columnExists('seller', 'pickup_address_id')) {
            $this->addSql('ALTER TABLE seller DROP pickup_address_id');
        }

        if ($this->columnExists('seller', 'customer_account_id')) {
            $this->addSql('ALTER TABLE seller DROP customer_account_id');
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName]
        ) > 0;
    }

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$tableName, $foreignKeyName, 'FOREIGN KEY']
        ) > 0;
    }
}
