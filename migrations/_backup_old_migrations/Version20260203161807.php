<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260203161807 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ensure customer_order.delivery_zone_id column + FK + index exist (idempotent)';
    }

    public function up(Schema $schema): void
    {
        // 1) Column
        $this->addSql("SET @col_exists := (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customer_order'
              AND COLUMN_NAME = 'delivery_zone_id'
        )");

        $this->addSql("SET @sql := IF(@col_exists = 0,
            'ALTER TABLE customer_order ADD delivery_zone_id INT DEFAULT NULL',
            'SELECT 1'
        )");

        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql("DEALLOCATE PREPARE stmt");

        // 2) Index
        $this->addSql("SET @idx_exists := (
            SELECT COUNT(*)
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customer_order'
              AND INDEX_NAME = 'IDX_3B1CE6A395328075'
        )");

        $this->addSql("SET @sql := IF(@idx_exists = 0,
            'CREATE INDEX IDX_3B1CE6A395328075 ON customer_order (delivery_zone_id)',
            'SELECT 1'
        )");

        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql("DEALLOCATE PREPARE stmt");

        // 3) FK
        $this->addSql("SET @fk_exists := (
            SELECT COUNT(*)
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customer_order'
              AND CONSTRAINT_NAME = 'FK_3B1CE6A395328075'
        )");

        $this->addSql("SET @sql := IF(@fk_exists = 0,
            'ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A395328075 FOREIGN KEY (delivery_zone_id) REFERENCES delivery_zone (id)',
            'SELECT 1'
        )");

        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql("DEALLOCATE PREPARE stmt");
    }

    public function down(Schema $schema): void
    {
        // In dev, we keep down minimal and safe.
        // Only drop FK/index/column if they exist.

        $this->addSql("SET @fk_exists := (
            SELECT COUNT(*)
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customer_order'
              AND CONSTRAINT_NAME = 'FK_3B1CE6A395328075'
        )");
        $this->addSql("SET @sql := IF(@fk_exists = 1,
            'ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A395328075',
            'SELECT 1'
        )");
        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql("DEALLOCATE PREPARE stmt");

        $this->addSql("SET @idx_exists := (
            SELECT COUNT(*)
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customer_order'
              AND INDEX_NAME = 'IDX_3B1CE6A395328075'
        )");
        $this->addSql("SET @sql := IF(@idx_exists = 1,
            'DROP INDEX IDX_3B1CE6A395328075 ON customer_order',
            'SELECT 1'
        )");
        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql("DEALLOCATE PREPARE stmt");

        $this->addSql("SET @col_exists := (
            SELECT COUNT(*)
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'customer_order'
              AND COLUMN_NAME = 'delivery_zone_id'
        )");
        $this->addSql("SET @sql := IF(@col_exists = 1,
            'ALTER TABLE customer_order DROP delivery_zone_id',
            'SELECT 1'
        )");
        $this->addSql("PREPARE stmt FROM @sql");
        $this->addSql("EXECUTE stmt");
        $this->addSql("DEALLOCATE PREPARE stmt");
    }
}
