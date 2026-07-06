<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5X-C add product delivery promise fields for sector schedule and appointment products';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('product')) {
            $this->write('J5X-C skipped: product table is missing.');
            return;
        }

        $this->addColumnIfMissing('product', 'delivery_promise_mode', "VARCHAR(40) DEFAULT 'SECTOR_SCHEDULE' NOT NULL");
        $this->addColumnIfMissing('product', 'delivery_promise_title', 'VARCHAR(160) DEFAULT NULL');
        $this->addColumnIfMissing('product', 'delivery_promise_description', 'LONGTEXT DEFAULT NULL');
        $this->addColumnIfMissing('product', 'appointment_delivery_weekdays', 'JSON DEFAULT NULL');
        $this->addColumnIfMissing('product', 'appointment_time_window_start', 'TIME DEFAULT NULL');
        $this->addColumnIfMissing('product', 'appointment_time_window_end', 'TIME DEFAULT NULL');
        $this->addColumnIfMissing('product', 'appointment_cutoff_time', 'TIME DEFAULT NULL');
        $this->addColumnIfMissing('product', 'appointment_cutoff_days_before', 'INT DEFAULT 1 NOT NULL');

        $this->connection->executeStatement(<<<'SQL'
UPDATE product
SET delivery_promise_mode = 'SECTOR_SCHEDULE'
WHERE delivery_promise_mode IS NULL OR delivery_promise_mode = ''
SQL);

        $this->connection->executeStatement(<<<'SQL'
UPDATE product
SET appointment_cutoff_days_before = 1
WHERE appointment_cutoff_days_before IS NULL
SQL);

        // Doctrine mapping does not declare SQL DEFAULT values for these fields.
        // Normalize the schema after seeding safe values, otherwise schema:validate
        // keeps reporting a diff on fresh installs and deployments.
        $this->connection->executeStatement(
            'ALTER TABLE product CHANGE delivery_promise_mode delivery_promise_mode VARCHAR(40) NOT NULL'
        );
        $this->connection->executeStatement(
            'ALTER TABLE product CHANGE appointment_cutoff_days_before appointment_cutoff_days_before INT NOT NULL'
        );
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('product')) {
            return;
        }

        $this->dropColumnIfExists('product', 'appointment_cutoff_days_before');
        $this->dropColumnIfExists('product', 'appointment_cutoff_time');
        $this->dropColumnIfExists('product', 'appointment_time_window_end');
        $this->dropColumnIfExists('product', 'appointment_time_window_start');
        $this->dropColumnIfExists('product', 'appointment_delivery_weekdays');
        $this->dropColumnIfExists('product', 'delivery_promise_description');
        $this->dropColumnIfExists('product', 'delivery_promise_title');
        $this->dropColumnIfExists('product', 'delivery_promise_mode');
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
