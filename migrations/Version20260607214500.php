<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607214500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5G-B2 schema alignment for editable commune connections model';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('delivery_commune') && $this->columnExists('delivery_commune', 'is_logistics_point')) {
            $this->addSql('ALTER TABLE delivery_commune CHANGE is_logistics_point is_logistics_point TINYINT NOT NULL');
        }

        if (
            $this->tableExists('delivery_commune_connection')
            && $this->tableExists('delivery_commune')
            && $this->columnExists('delivery_commune_connection', 'from_commune_id')
            && !$this->foreignKeyExists('delivery_commune_connection', 'FK_8D0FF29B38BE8975')
        ) {
            $this->addSql('ALTER TABLE delivery_commune_connection ADD CONSTRAINT FK_8D0FF29B38BE8975 FOREIGN KEY (from_commune_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
        }

        if (
            $this->tableExists('delivery_commune_connection')
            && $this->tableExists('delivery_commune')
            && $this->columnExists('delivery_commune_connection', 'to_commune_id')
            && !$this->foreignKeyExists('delivery_commune_connection', 'FK_8D0FF29B3429AD0F')
        ) {
            $this->addSql('ALTER TABLE delivery_commune_connection ADD CONSTRAINT FK_8D0FF29B3429AD0F FOREIGN KEY (to_commune_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('delivery_commune_connection') && $this->foreignKeyExists('delivery_commune_connection', 'FK_8D0FF29B38BE8975')) {
            $this->addSql('ALTER TABLE delivery_commune_connection DROP FOREIGN KEY FK_8D0FF29B38BE8975');
        }

        if ($this->tableExists('delivery_commune_connection') && $this->foreignKeyExists('delivery_commune_connection', 'FK_8D0FF29B3429AD0F')) {
            $this->addSql('ALTER TABLE delivery_commune_connection DROP FOREIGN KEY FK_8D0FF29B3429AD0F');
        }
    }

    private function tableExists(string $tableName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        );

        return (int) $result > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        );

        return (int) $result > 0;
    }

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?',
            [$tableName, $foreignKeyName, 'FOREIGN KEY']
        );

        return (int) $result > 0;
    }
}
