<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add customer.is_active and customer.anonymized_at for account anonymization.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('customer')) {
            $this->write('Skipped: customer table is missing.');

            return;
        }

        if (!$this->columnExists('customer', 'is_active')) {
            $this->connection->executeStatement('ALTER TABLE customer ADD is_active TINYINT(1) NOT NULL DEFAULT 1');
        }

        if (!$this->columnExists('customer', 'anonymized_at')) {
            $this->connection->executeStatement('ALTER TABLE customer ADD anonymized_at DATETIME DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('customer')) {
            return;
        }

        if ($this->columnExists('customer', 'anonymized_at')) {
            $this->connection->executeStatement('ALTER TABLE customer DROP anonymized_at');
        }

        if ($this->columnExists('customer', 'is_active')) {
            $this->connection->executeStatement('ALTER TABLE customer DROP is_active');
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
}
