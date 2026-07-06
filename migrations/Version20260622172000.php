<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622172000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5N-A: ajoute la validation de collecte vendeur par code et le snapshot de collecte commande.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->columnExists('seller', 'collection_validation_code')) {
            $this->addSql('ALTER TABLE seller ADD collection_validation_code VARCHAR(20) DEFAULT NULL');
        }

        if (!$this->columnExists('customer_order', 'seller_collection_snapshot')) {
            $this->addSql('ALTER TABLE customer_order ADD seller_collection_snapshot JSON DEFAULT NULL');
        }

        if (!$this->columnExists('email_log', 'body')) {
            $this->addSql('ALTER TABLE email_log ADD body LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->columnExists('email_log', 'body')) {
            $this->addSql('ALTER TABLE email_log DROP body');
        }

        if ($this->columnExists('customer_order', 'seller_collection_snapshot')) {
            $this->addSql('ALTER TABLE customer_order DROP seller_collection_snapshot');
        }

        if ($this->columnExists('seller', 'collection_validation_code')) {
            $this->addSql('ALTER TABLE seller DROP collection_validation_code');
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }
}
