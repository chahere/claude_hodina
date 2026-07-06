<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Normalisation résiduelle du schéma sur base neuve.
 *
 * Version20260604095052 (rendue défensive) était historiquement exécutée
 * APRÈS Version20260604100000 malgré son timestamp antérieur : en prod ses
 * normalisations ont bien eu lieu, mais sur une base reconstruite depuis
 * zéro dans l'ordre des timestamps, ses gardes la neutralisent et personne
 * ne réapplique ces trois ajustements ensuite. Cette migration les applique
 * en fin de chaîne, uniquement si nécessaire (no-op en prod).
 */
final class Version20260706210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Normalize legacy schema leftovers on fresh bootstrap: strip DC2Type comments (customer_order.order_reference_date, hodina_setting.updated_at) and rename the order_reference unique index to its ORM-expected name.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if ($this->columnHasDoctrineTypeComment('customer_order', 'order_reference_date')) {
            $this->addSql('ALTER TABLE customer_order CHANGE order_reference_date order_reference_date DATETIME DEFAULT NULL');
        }

        if ($this->indexExists('customer_order', 'uniq_5a6c5e95724e52bd')) {
            $this->addSql('ALTER TABLE customer_order RENAME INDEX uniq_5a6c5e95724e52bd TO UNIQ_3B1CE6A3122432EB');
        }

        if ($this->columnHasDoctrineTypeComment('hodina_setting', 'updated_at')) {
            $this->addSql('ALTER TABLE hodina_setting CHANGE updated_at updated_at DATETIME NOT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->columnExists('hodina_setting', 'updated_at') && !$this->columnHasDoctrineTypeComment('hodina_setting', 'updated_at')) {
            $this->addSql("ALTER TABLE hodina_setting CHANGE updated_at updated_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }

        if ($this->indexExists('customer_order', 'uniq_3b1ce6a3122432eb')) {
            $this->addSql('ALTER TABLE customer_order RENAME INDEX uniq_3b1ce6a3122432eb TO UNIQ_5A6C5E95724E52BD');
        }

        if ($this->columnExists('customer_order', 'order_reference_date') && !$this->columnHasDoctrineTypeComment('customer_order', 'order_reference_date')) {
            $this->addSql("ALTER TABLE customer_order CHANGE order_reference_date order_reference_date DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName],
        );

        return (int) $result > 0;
    }

    private function columnHasDoctrineTypeComment(string $tableName, string $columnName): bool
    {
        $comment = $this->connection->fetchOne(
            'SELECT COLUMN_COMMENT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName],
        );

        return is_string($comment) && str_contains($comment, '(DC2Type:');
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
