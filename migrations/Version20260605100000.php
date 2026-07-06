<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260605100000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les champs de réinitialisation de mot de passe client pour le pilote SMS.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('customer')) {
            return;
        }

        if (!$this->columnExists('customer', 'reset_password_token')) {
            $this->addSql('ALTER TABLE customer ADD reset_password_token VARCHAR(128) DEFAULT NULL');
        }

        if (!$this->columnExists('customer', 'reset_password_token_expires_at')) {
            $this->addSql('ALTER TABLE customer ADD reset_password_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        }

        if (!$this->indexExists('customer', 'IDX_CUSTOMER_RESET_PASSWORD_TOKEN')) {
            $this->addSql('CREATE INDEX IDX_CUSTOMER_RESET_PASSWORD_TOKEN ON customer (reset_password_token)');
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('customer')) {
            return;
        }

        if ($this->indexExists('customer', 'IDX_CUSTOMER_RESET_PASSWORD_TOKEN')) {
            $this->addSql('DROP INDEX IDX_CUSTOMER_RESET_PASSWORD_TOKEN ON customer');
        }

        if ($this->columnExists('customer', 'reset_password_token_expires_at')) {
            $this->addSql('ALTER TABLE customer DROP reset_password_token_expires_at');
        }

        if ($this->columnExists('customer', 'reset_password_token')) {
            $this->addSql('ALTER TABLE customer DROP reset_password_token');
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
}
