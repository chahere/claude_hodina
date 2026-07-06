<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Prépare les logs SMS pour les envois manuels depuis les commandes admin.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('sms_log')) {
            return;
        }

        $this->addColumnIfMissing('sms_log', 'customer_order_id', 'INT DEFAULT NULL');
        $this->addColumnIfMissing('sms_log', 'recipient_type', "VARCHAR(30) DEFAULT 'customer' NOT NULL");
        $this->addColumnIfMissing('sms_log', 'status', "VARCHAR(20) DEFAULT 'SENT' NOT NULL");
        $this->addColumnIfMissing('sms_log', 'provider', 'VARCHAR(40) DEFAULT NULL');
        $this->addColumnIfMissing('sms_log', 'provider_message_id', 'VARCHAR(120) DEFAULT NULL');
        $this->addColumnIfMissing('sms_log', 'error_message', 'LONGTEXT DEFAULT NULL');
        $this->addColumnIfMissing('sms_log', 'sent_at', 'DATETIME DEFAULT NULL');

        if (!$this->indexExists('sms_log', 'IDX_SMS_LOG_CUSTOMER_ORDER')) {
            $this->addSql('CREATE INDEX IDX_SMS_LOG_CUSTOMER_ORDER ON sms_log (customer_order_id)');
        }

        if (!$this->foreignKeyExists('sms_log', 'FK_SMS_LOG_CUSTOMER_ORDER')) {
            $this->addSql('ALTER TABLE sms_log ADD CONSTRAINT FK_SMS_LOG_CUSTOMER_ORDER FOREIGN KEY (customer_order_id) REFERENCES customer_order (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('sms_log')) {
            return;
        }

        if ($this->foreignKeyExists('sms_log', 'FK_SMS_LOG_CUSTOMER_ORDER')) {
            $this->addSql('ALTER TABLE sms_log DROP FOREIGN KEY FK_SMS_LOG_CUSTOMER_ORDER');
        }

        if ($this->indexExists('sms_log', 'IDX_SMS_LOG_CUSTOMER_ORDER')) {
            $this->addSql('DROP INDEX IDX_SMS_LOG_CUSTOMER_ORDER ON sms_log');
        }

        foreach (['customer_order_id', 'recipient_type', 'status', 'provider', 'provider_message_id', 'error_message', 'sent_at'] as $column) {
            if ($this->columnExists('sms_log', $column)) {
                $this->addSql(sprintf('ALTER TABLE sms_log DROP COLUMN %s', $column));
            }
        }
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName]
        ) > 0;
    }

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$tableName, $foreignKeyName, 'FOREIGN KEY']
        ) > 0;
    }

    private function addColumnIfMissing(string $tableName, string $columnName, string $definition): void
    {
        if (!$this->columnExists($tableName, $columnName)) {
            $this->addSql(sprintf('ALTER TABLE %s ADD %s %s', $tableName, $columnName, $definition));
        }
    }
}
