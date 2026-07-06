<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260704210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5AA-A add AddressLocality and optional locality fields on Address and CustomerOrder snapshots';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('address_locality')) {
            $this->connection->executeStatement(<<<'SQL'
CREATE TABLE address_locality (
    id INT AUTO_INCREMENT NOT NULL,
    delivery_commune_id INT DEFAULT NULL,
    name VARCHAR(120) NOT NULL,
    normalized_name VARCHAR(160) NOT NULL,
    postal_code VARCHAR(20) DEFAULT NULL,
    country_code VARCHAR(2) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL,
    sort_order INT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    INDEX IDX_ADDRESS_LOCALITY_COMMUNE (delivery_commune_id),
    INDEX IDX_ADDRESS_LOCALITY_ACTIVE (is_active),
    INDEX IDX_ADDRESS_LOCALITY_POSTAL_CODE (postal_code),
    INDEX IDX_ADDRESS_LOCALITY_NORMALIZED_NAME (normalized_name),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if ($this->tableExists('delivery_commune') && !$this->foreignKeyExists('address_locality', 'FK_ADDRESS_LOCALITY_COMMUNE')) {
            $this->connection->executeStatement('ALTER TABLE address_locality ADD CONSTRAINT FK_ADDRESS_LOCALITY_COMMUNE FOREIGN KEY (delivery_commune_id) REFERENCES delivery_commune (id) ON DELETE SET NULL');
        }

        if ($this->tableExists('address')) {
            $this->addColumnIfMissing('address', 'address_locality_id', 'INT DEFAULT NULL');
            $this->addColumnIfMissing('address', 'locality_text', 'VARCHAR(120) DEFAULT NULL');

            if (!$this->indexExists('address', 'IDX_ADDRESS_LOCALITY')) {
                $this->connection->executeStatement('CREATE INDEX IDX_ADDRESS_LOCALITY ON address (address_locality_id)');
            }

            if (!$this->foreignKeyExists('address', 'FK_ADDRESS_LOCALITY')) {
                $this->connection->executeStatement('ALTER TABLE address ADD CONSTRAINT FK_ADDRESS_LOCALITY FOREIGN KEY (address_locality_id) REFERENCES address_locality (id) ON DELETE SET NULL');
            }
        }

        if ($this->tableExists('customer_order')) {
            $this->addColumnIfMissing('customer_order', 'delivery_address_locality_name', 'VARCHAR(120) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('customer_order')) {
            $this->dropColumnIfExists('customer_order', 'delivery_address_locality_name');
        }

        if ($this->tableExists('address')) {
            if ($this->foreignKeyExists('address', 'FK_ADDRESS_LOCALITY')) {
                $this->connection->executeStatement('ALTER TABLE address DROP FOREIGN KEY FK_ADDRESS_LOCALITY');
            }

            if ($this->indexExists('address', 'IDX_ADDRESS_LOCALITY')) {
                $this->connection->executeStatement('DROP INDEX IDX_ADDRESS_LOCALITY ON address');
            }

            $this->dropColumnIfExists('address', 'locality_text');
            $this->dropColumnIfExists('address', 'address_locality_id');
        }

        if ($this->tableExists('address_locality')) {
            if ($this->foreignKeyExists('address_locality', 'FK_ADDRESS_LOCALITY_COMMUNE')) {
                $this->connection->executeStatement('ALTER TABLE address_locality DROP FOREIGN KEY FK_ADDRESS_LOCALITY_COMMUNE');
            }

            $this->connection->executeStatement('DROP TABLE address_locality');
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

    private function indexExists(string $tableName, string $indexName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName],
        );

        return (int) $result > 0;
    }

    private function foreignKeyExists(string $tableName, string $constraintName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = \'FOREIGN KEY\'',
            [$tableName, $constraintName],
        );

        return (int) $result > 0;
    }
}
