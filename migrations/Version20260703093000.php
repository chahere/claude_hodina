<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260703093000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5AC secure nullable unique customer email before client profile edition';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('customer') || !$this->columnExists('customer', 'email')) {
            $this->write('J5AC skipped customer.email unique index: customer table or email column is missing.');

            return;
        }

        $this->connection->executeStatement("UPDATE customer SET email = NULL WHERE email IS NOT NULL AND TRIM(email) = ''");
        $this->connection->executeStatement('UPDATE customer SET email = LOWER(TRIM(email)) WHERE email IS NOT NULL');

        $duplicates = $this->connection->fetchAllAssociative(<<<'SQL'
SELECT LOWER(TRIM(email)) AS normalized_email, COUNT(*) AS nb, GROUP_CONCAT(id ORDER BY id) AS ids
FROM customer
WHERE email IS NOT NULL AND TRIM(email) <> ''
GROUP BY LOWER(TRIM(email))
HAVING COUNT(*) > 1
SQL);

        if ($duplicates !== []) {
            $details = array_map(
                static fn (array $row): string => sprintf('%s => %s compte(s), ids: %s', $row['normalized_email'] ?? '(vide)', $row['nb'] ?? '?', $row['ids'] ?? '?'),
                $duplicates
            );

            throw new \RuntimeException('Doublons email customer détectés avant J5AC. Corriger avant migration : ' . implode(' ; ', $details));
        }

        if (!$this->indexExists('customer', 'UNIQ_CUSTOMER_EMAIL')) {
            $this->connection->executeStatement('CREATE UNIQUE INDEX UNIQ_CUSTOMER_EMAIL ON customer (email)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('customer') && $this->indexExists('customer', 'UNIQ_CUSTOMER_EMAIL')) {
            $this->connection->executeStatement('DROP INDEX UNIQ_CUSTOMER_EMAIL ON customer');
        }
    }

    private function tableExists(string $tableName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName],
        );

        return (int) $result > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName],
        );

        return (int) $result > 0;
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
