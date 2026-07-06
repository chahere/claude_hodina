<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629223000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5X-D add catalogue merchandising fields on Category and Product';
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('category')) {
            $this->addColumnIfMissing('category', 'display_order', 'INT DEFAULT 0 NOT NULL');
            $this->addColumnIfMissing('category', 'is_featured', 'TINYINT(1) DEFAULT 0 NOT NULL');
            $this->addColumnIfMissing('category', 'public_description', 'LONGTEXT DEFAULT NULL');

            $this->connection->executeStatement('UPDATE category SET display_order = 0 WHERE display_order IS NULL');
            $this->connection->executeStatement('UPDATE category SET is_featured = 0 WHERE is_featured IS NULL');

            $this->connection->executeStatement('ALTER TABLE category CHANGE display_order display_order INT NOT NULL');
            $this->connection->executeStatement('ALTER TABLE category CHANGE is_featured is_featured TINYINT(1) NOT NULL');
        } else {
            $this->write('J5X-D skipped category fields: category table is missing.');
        }

        if ($this->tableExists('product')) {
            $this->addColumnIfMissing('product', 'is_featured', 'TINYINT(1) DEFAULT 0 NOT NULL');
            $this->addColumnIfMissing('product', 'display_priority', 'INT DEFAULT 0 NOT NULL');

            $this->connection->executeStatement('UPDATE product SET is_featured = 0 WHERE is_featured IS NULL');
            $this->connection->executeStatement('UPDATE product SET display_priority = 0 WHERE display_priority IS NULL');

            $this->connection->executeStatement('ALTER TABLE product CHANGE is_featured is_featured TINYINT(1) NOT NULL');
            $this->connection->executeStatement('ALTER TABLE product CHANGE display_priority display_priority INT NOT NULL');
        } else {
            $this->write('J5X-D skipped product fields: product table is missing.');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('product')) {
            $this->dropColumnIfExists('product', 'display_priority');
            $this->dropColumnIfExists('product', 'is_featured');
        }

        if ($this->tableExists('category')) {
            $this->dropColumnIfExists('category', 'public_description');
            $this->dropColumnIfExists('category', 'is_featured');
            $this->dropColumnIfExists('category', 'display_order');
        }
    }

    private function addColumnIfMissing(string $tableName, string $columnName, string $definition): void
    {
        if ($this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->connection->executeStatement(sprintf('ALTER TABLE %s ADD %s %s', $tableName, $columnName, $definition));
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!$this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->connection->executeStatement(sprintf('ALTER TABLE %s DROP %s', $tableName, $columnName));
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
