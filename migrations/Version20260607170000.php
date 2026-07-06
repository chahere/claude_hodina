<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5F-A - Add delivery pricing zones, delivery communes and seller logistics commune';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('delivery_pricing_zone')) {
            $this->addSql(<<<'SQL'
CREATE TABLE delivery_pricing_zone (
    id INT AUTO_INCREMENT NOT NULL,
    name VARCHAR(120) NOT NULL,
    code VARCHAR(40) NOT NULL,
    customer_delivery_fee NUMERIC(10, 2) NOT NULL,
    courier_payout NUMERIC(10, 2) NOT NULL,
    is_active TINYINT(1) NOT NULL,
    internal_note LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    UNIQUE INDEX UNIQ_8CDA77C377153098 (code),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if (!$this->tableExists('delivery_commune')) {
            $this->addSql(<<<'SQL'
CREATE TABLE delivery_commune (
    id INT AUTO_INCREMENT NOT NULL,
    local_pricing_zone_id INT NOT NULL,
    barge_pricing_zone_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    territory VARCHAR(2) NOT NULL,
    is_active TINYINT(1) NOT NULL,
    internal_note LONGTEXT DEFAULT NULL,
    created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)',
    updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)',
    INDEX IDX_71AE94C563072109 (local_pricing_zone_id),
    INDEX IDX_71AE94C52581CDE5 (barge_pricing_zone_id),
    UNIQUE INDEX UNIQ_71AE94C55E237E06 (name),
    PRIMARY KEY(id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if (!$this->tableExists('delivery_commune_neighbor')) {
            $this->addSql(<<<'SQL'
CREATE TABLE delivery_commune_neighbor (
    commune_id INT NOT NULL,
    neighbor_id INT NOT NULL,
    INDEX IDX_95A10D61131A344E (commune_id),
    INDEX IDX_95A10D6177FD8A6B (neighbor_id),
    PRIMARY KEY(commune_id, neighbor_id)
) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB
SQL);
        }

        if ($this->tableExists('delivery_commune') && !$this->foreignKeyExists('delivery_commune', 'FK_71AE94C563072109')) {
            $this->addSql('ALTER TABLE delivery_commune ADD CONSTRAINT FK_71AE94C563072109 FOREIGN KEY (local_pricing_zone_id) REFERENCES delivery_pricing_zone (id)');
        }

        if ($this->tableExists('delivery_commune') && !$this->foreignKeyExists('delivery_commune', 'FK_71AE94C52581CDE5')) {
            $this->addSql('ALTER TABLE delivery_commune ADD CONSTRAINT FK_71AE94C52581CDE5 FOREIGN KEY (barge_pricing_zone_id) REFERENCES delivery_pricing_zone (id)');
        }

        if ($this->tableExists('delivery_commune_neighbor') && !$this->foreignKeyExists('delivery_commune_neighbor', 'FK_95A10D61131A344E')) {
            $this->addSql('ALTER TABLE delivery_commune_neighbor ADD CONSTRAINT FK_95A10D61131A344E FOREIGN KEY (commune_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
        }

        if ($this->tableExists('delivery_commune_neighbor') && !$this->foreignKeyExists('delivery_commune_neighbor', 'FK_95A10D6177FD8A6B')) {
            $this->addSql('ALTER TABLE delivery_commune_neighbor ADD CONSTRAINT FK_95A10D6177FD8A6B FOREIGN KEY (neighbor_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
        }

        if (!$this->columnExists('seller', 'delivery_commune_id')) {
            $this->addSql('ALTER TABLE seller ADD delivery_commune_id INT DEFAULT NULL');
        }

        if ($this->columnExists('seller', 'delivery_commune_id') && !$this->indexExists('seller', 'IDX_97E64353353CFB33')) {
            $this->addSql('CREATE INDEX IDX_97E64353353CFB33 ON seller (delivery_commune_id)');
        }

        if ($this->columnExists('seller', 'delivery_commune_id') && !$this->foreignKeyExists('seller', 'FK_97E64353353CFB33')) {
            $this->addSql('ALTER TABLE seller ADD CONSTRAINT FK_97E64353353CFB33 FOREIGN KEY (delivery_commune_id) REFERENCES delivery_commune (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->foreignKeyExists('seller', 'FK_97E64353353CFB33')) {
            $this->addSql('ALTER TABLE seller DROP FOREIGN KEY FK_97E64353353CFB33');
        }

        if ($this->indexExists('seller', 'IDX_97E64353353CFB33')) {
            $this->addSql('DROP INDEX IDX_97E64353353CFB33 ON seller');
        }

        if ($this->columnExists('seller', 'delivery_commune_id')) {
            $this->addSql('ALTER TABLE seller DROP delivery_commune_id');
        }

        if ($this->foreignKeyExists('delivery_commune_neighbor', 'FK_95A10D61131A344E')) {
            $this->addSql('ALTER TABLE delivery_commune_neighbor DROP FOREIGN KEY FK_95A10D61131A344E');
        }

        if ($this->foreignKeyExists('delivery_commune_neighbor', 'FK_95A10D6177FD8A6B')) {
            $this->addSql('ALTER TABLE delivery_commune_neighbor DROP FOREIGN KEY FK_95A10D6177FD8A6B');
        }

        if ($this->tableExists('delivery_commune_neighbor')) {
            $this->addSql('DROP TABLE delivery_commune_neighbor');
        }

        if ($this->foreignKeyExists('delivery_commune', 'FK_71AE94C563072109')) {
            $this->addSql('ALTER TABLE delivery_commune DROP FOREIGN KEY FK_71AE94C563072109');
        }

        if ($this->foreignKeyExists('delivery_commune', 'FK_71AE94C52581CDE5')) {
            $this->addSql('ALTER TABLE delivery_commune DROP FOREIGN KEY FK_71AE94C52581CDE5');
        }

        if ($this->tableExists('delivery_commune')) {
            $this->addSql('DROP TABLE delivery_commune');
        }

        if ($this->tableExists('delivery_pricing_zone')) {
            $this->addSql('DROP TABLE delivery_pricing_zone');
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
