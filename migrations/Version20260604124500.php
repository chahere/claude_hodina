<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604124500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Transforme les réglages Hodina en liste clé/valeur, avec une ligne par paramètre.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('hodina_setting')) {
            return;
        }

        $legacyPrefix = 'hodina';
        $legacyCommunes = null;

        if ($this->columnExists('hodina_setting', 'order_reference_prefix')) {
            $legacy = $this->connection->fetchAssociative('SELECT order_reference_prefix, delivered_communes FROM hodina_setting ORDER BY id ASC LIMIT 1');
            if ($legacy !== false) {
                $legacyPrefix = trim((string) ($legacy['order_reference_prefix'] ?? 'hodina')) ?: 'hodina';
                $legacyCommunes = $legacy['delivered_communes'] ?? null;
            }
        }

        $this->addColumnIfMissing('hodina_setting', 'setting_key', 'VARCHAR(80) DEFAULT NULL');
        $this->addColumnIfMissing('hodina_setting', 'label', 'VARCHAR(120) DEFAULT NULL');
        $this->addColumnIfMissing('hodina_setting', 'value', 'LONGTEXT DEFAULT NULL');
        $this->addColumnIfMissing('hodina_setting', 'help', 'LONGTEXT DEFAULT NULL');
        $this->addColumnIfMissing('hodina_setting', 'field_type', "VARCHAR(30) DEFAULT 'text'");

        $this->connection->executeStatement('DELETE FROM hodina_setting');

        $now = (new \DateTimeImmutable())->format('Y-m-d H:i:s');

        $this->connection->insert('hodina_setting', [
            'setting_key' => 'order_reference_prefix',
            'label' => 'Préfixe des numéros de commande',
            'value' => $legacyPrefix,
            'help' => 'Préfixe utilisé pour générer les numéros visibles par le client. Exemple : hodina donne hodina202606041.',
            'field_type' => 'text',
            'updated_at' => $now,
        ]);

        $this->connection->insert('hodina_setting', [
            'setting_key' => 'delivered_communes',
            'label' => 'Communes livrées',
            'value' => $legacyCommunes,
            'help' => 'Liste des communes livrées pendant le pilote. Tu peux saisir une commune par ligne.',
            'field_type' => 'textarea',
            'updated_at' => $now,
        ]);

        if (!$this->indexExists('hodina_setting', 'UNIQ_HODINA_SETTING_KEY')) {
            $this->addSql('CREATE UNIQUE INDEX UNIQ_HODINA_SETTING_KEY ON hodina_setting (setting_key)');
        }

        if ($this->columnExists('hodina_setting', 'order_reference_prefix')) {
            $this->addSql('ALTER TABLE hodina_setting DROP COLUMN order_reference_prefix');
        }

        if ($this->columnExists('hodina_setting', 'delivered_communes')) {
            $this->addSql('ALTER TABLE hodina_setting DROP COLUMN delivered_communes');
        }

        $this->addSql("ALTER TABLE hodina_setting MODIFY setting_key VARCHAR(80) NOT NULL, MODIFY label VARCHAR(120) NOT NULL, MODIFY field_type VARCHAR(30) NOT NULL");
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('hodina_setting')) {
            return;
        }

        $prefix = $this->connection->fetchOne("SELECT value FROM hodina_setting WHERE setting_key = 'order_reference_prefix'") ?: 'hodina';
        $communes = $this->connection->fetchOne("SELECT value FROM hodina_setting WHERE setting_key = 'delivered_communes'") ?: null;

        $this->addColumnIfMissing('hodina_setting', 'order_reference_prefix', "VARCHAR(30) DEFAULT 'hodina' NOT NULL");
        $this->addColumnIfMissing('hodina_setting', 'delivered_communes', 'LONGTEXT DEFAULT NULL');

        $this->connection->executeStatement('DELETE FROM hodina_setting');
        $this->connection->insert('hodina_setting', [
            'order_reference_prefix' => $prefix,
            'delivered_communes' => $communes,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        if ($this->indexExists('hodina_setting', 'UNIQ_HODINA_SETTING_KEY')) {
            $this->addSql('DROP INDEX UNIQ_HODINA_SETTING_KEY ON hodina_setting');
        }

        foreach (['setting_key', 'label', 'value', 'help', 'field_type'] as $column) {
            if ($this->columnExists('hodina_setting', $column)) {
                $this->addSql(sprintf('ALTER TABLE hodina_setting DROP COLUMN %s', $column));
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

    private function addColumnIfMissing(string $tableName, string $columnName, string $definition): void
    {
        if (!$this->columnExists($tableName, $columnName)) {
            $this->addSql(sprintf('ALTER TABLE %s ADD %s %s', $tableName, $columnName, $definition));
        }
    }
}
