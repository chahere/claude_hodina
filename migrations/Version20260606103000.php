<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606103000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align customer_order assigned courier index name after J5C delivery fields migration.';
    }

    public function up(Schema $schema): void
    {
        $oldIndexExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(1)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'customer_order'
               AND index_name = 'idx_3cf0a31e4b1e148f'"
        );

        $newIndexExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(1)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'customer_order'
               AND index_name = 'IDX_3B1CE6A3DB79766D'"
        );

        if ($oldIndexExists && !$newIndexExists) {
            $this->addSql('ALTER TABLE customer_order RENAME INDEX idx_3cf0a31e4b1e148f TO IDX_3B1CE6A3DB79766D');
        }
    }

    public function down(Schema $schema): void
    {
        $oldIndexExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(1)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'customer_order'
               AND index_name = 'idx_3cf0a31e4b1e148f'"
        );

        $newIndexExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(1)
             FROM information_schema.statistics
             WHERE table_schema = DATABASE()
               AND table_name = 'customer_order'
               AND index_name = 'IDX_3B1CE6A3DB79766D'"
        );

        if ($newIndexExists && !$oldIndexExists) {
            $this->addSql('ALTER TABLE customer_order RENAME INDEX IDX_3B1CE6A3DB79766D TO idx_3cf0a31e4b1e148f');
        }
    }
}