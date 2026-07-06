<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607213000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5G-B2 - Enrich delivery communes and add editable logistics connections';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('delivery_commune')) {
            if (!$this->columnExists('delivery_commune', 'slug')) {
                $this->addSql('ALTER TABLE delivery_commune ADD slug VARCHAR(160) DEFAULT NULL');
            }

            if (!$this->columnExists('delivery_commune', 'postal_code')) {
                $this->addSql('ALTER TABLE delivery_commune ADD postal_code VARCHAR(10) DEFAULT NULL');
            }

            if (!$this->columnExists('delivery_commune', 'insee_code')) {
                $this->addSql('ALTER TABLE delivery_commune ADD insee_code VARCHAR(10) DEFAULT NULL');
            }

            if (!$this->columnExists('delivery_commune', 'parent_insee_code')) {
                $this->addSql('ALTER TABLE delivery_commune ADD parent_insee_code VARCHAR(10) DEFAULT NULL');
            }

            if (!$this->columnExists('delivery_commune', 'is_logistics_point')) {
                $this->addSql('ALTER TABLE delivery_commune ADD is_logistics_point TINYINT(1) NOT NULL DEFAULT 1');
            }

            if (!$this->indexExists('delivery_commune', 'UNIQ_E8FC6E30989D9B62')) {
                $this->addSql('CREATE UNIQUE INDEX UNIQ_E8FC6E30989D9B62 ON delivery_commune (slug)');
            }

            if (!$this->indexExists('delivery_commune', 'IDX_E8FC6E30EA98E376')) {
                $this->addSql('CREATE INDEX IDX_E8FC6E30EA98E376 ON delivery_commune (postal_code)');
            }

            if (!$this->indexExists('delivery_commune', 'IDX_E8FC6E3015A3C1BC')) {
                $this->addSql('CREATE INDEX IDX_E8FC6E3015A3C1BC ON delivery_commune (insee_code)');
            }
        }

        if (!$this->tableExists('delivery_commune_connection')) {
            $this->addSql(<<<'SQL'
CREATE TABLE delivery_commune_connection (
    id INT AUTO_INCREMENT NOT NULL,
    from_commune_id INT NOT NULL,
    to_commune_id INT NOT NULL,
    link_type VARCHAR(20) NOT NULL,
    is_bidirectional TINYINT(1) NOT NULL,
    hop_count INT NOT NULL,
    customer_extra_fee NUMERIC(10, 2) DEFAULT NULL,
    courier_extra_payout NUMERIC(10, 2) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL,
    internal_note LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME DEFAULT NULL,
    INDEX IDX_8D0FF29B38BE8975 (from_commune_id),
    INDEX IDX_8D0FF29B3429AD0F (to_commune_id),
    UNIQUE INDEX UNIQ_DELIVERY_COMMUNE_CONNECTION_DIRECTION (from_commune_id, to_commune_id, link_type),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if ($this->tableExists('delivery_commune_connection')) {
            if (!$this->foreignKeyExists('delivery_commune_connection', 'FK_8D0FF29B38BE8975')) {
                $this->addSql('ALTER TABLE delivery_commune_connection ADD CONSTRAINT FK_8D0FF29B38BE8975 FOREIGN KEY (from_commune_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
            }

            if (!$this->foreignKeyExists('delivery_commune_connection', 'FK_8D0FF29B3429AD0F')) {
                $this->addSql('ALTER TABLE delivery_commune_connection ADD CONSTRAINT FK_8D0FF29B3429AD0F FOREIGN KEY (to_commune_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('delivery_commune_connection')) {
            if ($this->foreignKeyExists('delivery_commune_connection', 'FK_8D0FF29B38BE8975')) {
                $this->addSql('ALTER TABLE delivery_commune_connection DROP FOREIGN KEY FK_8D0FF29B38BE8975');
            }

            if ($this->foreignKeyExists('delivery_commune_connection', 'FK_8D0FF29B3429AD0F')) {
                $this->addSql('ALTER TABLE delivery_commune_connection DROP FOREIGN KEY FK_8D0FF29B3429AD0F');
            }

            $this->addSql('DROP TABLE delivery_commune_connection');
        }

        if ($this->tableExists('delivery_commune')) {
            if ($this->indexExists('delivery_commune', 'IDX_E8FC6E3015A3C1BC')) {
                $this->addSql('DROP INDEX IDX_E8FC6E3015A3C1BC ON delivery_commune');
            }

            if ($this->indexExists('delivery_commune', 'IDX_E8FC6E30EA98E376')) {
                $this->addSql('DROP INDEX IDX_E8FC6E30EA98E376 ON delivery_commune');
            }

            if ($this->indexExists('delivery_commune', 'UNIQ_E8FC6E30989D9B62')) {
                $this->addSql('DROP INDEX UNIQ_E8FC6E30989D9B62 ON delivery_commune');
            }

            if ($this->columnExists('delivery_commune', 'is_logistics_point')) {
                $this->addSql('ALTER TABLE delivery_commune DROP is_logistics_point');
            }

            if ($this->columnExists('delivery_commune', 'parent_insee_code')) {
                $this->addSql('ALTER TABLE delivery_commune DROP parent_insee_code');
            }

            if ($this->columnExists('delivery_commune', 'insee_code')) {
                $this->addSql('ALTER TABLE delivery_commune DROP insee_code');
            }

            if ($this->columnExists('delivery_commune', 'postal_code')) {
                $this->addSql('ALTER TABLE delivery_commune DROP postal_code');
            }

            if ($this->columnExists('delivery_commune', 'slug')) {
                $this->addSql('ALTER TABLE delivery_commune DROP slug');
            }
        }
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        ) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
            [$tableName, $indexName]
        ) > 0;
    }

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$tableName, $foreignKeyName, 'FOREIGN KEY']
        ) > 0;
    }
}
