<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607225500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5G support - split billing and delivery addresses and add AUTRE delivery zone';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('address') && !$this->columnExists('address', 'type')) {
            $this->addSql("ALTER TABLE address ADD type VARCHAR(20) NOT NULL DEFAULT 'DELIVERY'");
        }

        if ($this->tableExists('address') && $this->columnExists('address', 'type')) {
            $this->addSql("UPDATE address SET type = 'DELIVERY' WHERE type IS NULL OR type = ''");
        }

        if ($this->tableExists('delivery_zone') && !$this->deliveryZoneExists('AUTRE')) {
            $this->addSql(<<<'SQL'
INSERT INTO delivery_zone (code, name, is_active, created_at)
VALUES ('AUTRE', 'Autre', 1, NOW())
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('address') && $this->columnExists('address', 'type')) {
            $this->addSql('ALTER TABLE address DROP type');
        }

        if ($this->tableExists('delivery_zone') && $this->deliveryZoneExists('AUTRE')) {
            $this->addSql(<<<'SQL'
DELETE dz
FROM delivery_zone dz
LEFT JOIN address a ON a.delivery_zone_id = dz.id
WHERE dz.code = 'AUTRE' AND a.id IS NULL
SQL);
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

    private function deliveryZoneExists(string $code): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM delivery_zone WHERE code = ?',
            [$code]
        ) > 0;
    }
}
