<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260708130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5AF normalise customer.is_active (TINYINT NOT NULL sans largeur ni DEFAULT) pour correspondre au mapping Doctrine.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('customer') || !$this->columnExists('customer', 'is_active')) {
            $this->write('J5AF skipped is_active normalization: customer table or is_active column is missing.');

            return;
        }

        if ($this->isActiveColumnNormalized()) {
            $this->write('J5AF skipped is_active normalization: already TINYINT NOT NULL without default.');

            return;
        }

        $this->connection->executeStatement('ALTER TABLE customer CHANGE is_active is_active TINYINT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('customer') || !$this->columnExists('customer', 'is_active')) {
            return;
        }

        $this->connection->executeStatement('ALTER TABLE customer CHANGE is_active is_active TINYINT(1) NOT NULL DEFAULT 1');
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

    private function isActiveColumnNormalized(): bool
    {
        $columnType = $this->connection->fetchOne(
            'SELECT COLUMN_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['customer', 'is_active'],
        );
        $columnDefault = $this->connection->fetchOne(
            'SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            ['customer', 'is_active'],
        );

        return strtolower((string) $columnType) === 'tinyint' && $columnDefault === null;
    }
}
