<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260621215500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5M-C3-ter: ajoute le nom de structure optionnel sur les vendeurs.';
    }

    public function up(Schema $schema): void
    {
        if (!$this->columnExists('seller', 'business_name')) {
            $this->addSql('ALTER TABLE seller ADD business_name VARCHAR(150) DEFAULT NULL');
            $this->addSql('UPDATE seller SET business_name = NULLIF(TRIM(name), \'\') WHERE business_name IS NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->columnExists('seller', 'business_name')) {
            $this->addSql('ALTER TABLE seller DROP business_name');
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
