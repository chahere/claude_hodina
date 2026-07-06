<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607173000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5F-A - Align delivery logistics schema with Doctrine metadata';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if ($this->tableExists('delivery_commune')) {
            if ($this->foreignKeyExists('delivery_commune', 'FK_71AE94C563072109')) {
                $this->addSql('ALTER TABLE delivery_commune DROP FOREIGN KEY FK_71AE94C563072109');
            }

            if ($this->foreignKeyExists('delivery_commune', 'FK_71AE94C52581CDE5')) {
                $this->addSql('ALTER TABLE delivery_commune DROP FOREIGN KEY FK_71AE94C52581CDE5');
            }

            $this->addSql('ALTER TABLE delivery_commune CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');

            $this->renameIndexIfNeeded('delivery_commune', 'UNIQ_71AE94C55E237E06', 'UNIQ_E8FC6E305E237E06');
            $this->renameIndexIfNeeded('delivery_commune', 'IDX_71AE94C563072109', 'IDX_E8FC6E30AFB460D0');
            $this->renameIndexIfNeeded('delivery_commune', 'IDX_71AE94C52581CDE5', 'IDX_E8FC6E30CEE9A40D');

            if (!$this->foreignKeyExists('delivery_commune', 'FK_E8FC6E30AFB460D0')) {
                $this->addSql('ALTER TABLE delivery_commune ADD CONSTRAINT FK_E8FC6E30AFB460D0 FOREIGN KEY (local_pricing_zone_id) REFERENCES delivery_pricing_zone (id)');
            }

            if (!$this->foreignKeyExists('delivery_commune', 'FK_E8FC6E30CEE9A40D')) {
                $this->addSql('ALTER TABLE delivery_commune ADD CONSTRAINT FK_E8FC6E30CEE9A40D FOREIGN KEY (barge_pricing_zone_id) REFERENCES delivery_pricing_zone (id)');
            }
        }

        if ($this->tableExists('delivery_commune_neighbor')) {
            if ($this->foreignKeyExists('delivery_commune_neighbor', 'FK_95A10D61131A344E')) {
                $this->addSql('ALTER TABLE delivery_commune_neighbor DROP FOREIGN KEY FK_95A10D61131A344E');
            }

            if ($this->foreignKeyExists('delivery_commune_neighbor', 'FK_95A10D6177FD8A6B')) {
                $this->addSql('ALTER TABLE delivery_commune_neighbor DROP FOREIGN KEY FK_95A10D6177FD8A6B');
            }

            $this->renameIndexIfNeeded('delivery_commune_neighbor', 'IDX_95A10D61131A344E', 'IDX_8D2A2CE131A4F72');
            $this->renameIndexIfNeeded('delivery_commune_neighbor', 'IDX_95A10D6177FD8A6B', 'IDX_8D2A2CECA3465C1');

            if (!$this->foreignKeyExists('delivery_commune_neighbor', 'FK_8D2A2CE131A4F72')) {
                $this->addSql('ALTER TABLE delivery_commune_neighbor ADD CONSTRAINT FK_8D2A2CE131A4F72 FOREIGN KEY (commune_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
            }

            if (!$this->foreignKeyExists('delivery_commune_neighbor', 'FK_8D2A2CECA3465C1')) {
                $this->addSql('ALTER TABLE delivery_commune_neighbor ADD CONSTRAINT FK_8D2A2CECA3465C1 FOREIGN KEY (neighbor_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
            }
        }

        if ($this->tableExists('delivery_pricing_zone')) {
            $this->addSql('ALTER TABLE delivery_pricing_zone CHANGE created_at created_at DATETIME NOT NULL, CHANGE updated_at updated_at DATETIME DEFAULT NULL');
            $this->renameIndexIfNeeded('delivery_pricing_zone', 'UNIQ_8CDA77C377153098', 'UNIQ_147E33BD77153098');
        }

        if ($this->tableExists('seller') && $this->columnExists('seller', 'delivery_commune_id')) {
            if ($this->foreignKeyExists('seller', 'FK_97E64353353CFB33')) {
                $this->addSql('ALTER TABLE seller DROP FOREIGN KEY FK_97E64353353CFB33');
            }

            $this->renameIndexIfNeeded('seller', 'IDX_97E64353353CFB33', 'IDX_FB1AD3FCD5FD856');

            if (!$this->indexExists('seller', 'IDX_FB1AD3FCD5FD856')) {
                $this->addSql('CREATE INDEX IDX_FB1AD3FCD5FD856 ON seller (delivery_commune_id)');
            }

            if (!$this->foreignKeyExists('seller', 'FK_FB1AD3FCD5FD856')) {
                $this->addSql('ALTER TABLE seller ADD CONSTRAINT FK_FB1AD3FCD5FD856 FOREIGN KEY (delivery_commune_id) REFERENCES delivery_commune (id) ON DELETE SET NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('seller') && $this->columnExists('seller', 'delivery_commune_id')) {
            if ($this->foreignKeyExists('seller', 'FK_FB1AD3FCD5FD856')) {
                $this->addSql('ALTER TABLE seller DROP FOREIGN KEY FK_FB1AD3FCD5FD856');
            }

            $this->renameIndexIfNeeded('seller', 'IDX_FB1AD3FCD5FD856', 'IDX_97E64353353CFB33');

            if (!$this->foreignKeyExists('seller', 'FK_97E64353353CFB33')) {
                $this->addSql('ALTER TABLE seller ADD CONSTRAINT FK_97E64353353CFB33 FOREIGN KEY (delivery_commune_id) REFERENCES delivery_commune (id) ON DELETE SET NULL');
            }
        }

        if ($this->tableExists('delivery_pricing_zone')) {
            $this->renameIndexIfNeeded('delivery_pricing_zone', 'UNIQ_147E33BD77153098', 'UNIQ_8CDA77C377153098');
            $this->addSql("ALTER TABLE delivery_pricing_zone CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");
        }

        if ($this->tableExists('delivery_commune_neighbor')) {
            if ($this->foreignKeyExists('delivery_commune_neighbor', 'FK_8D2A2CE131A4F72')) {
                $this->addSql('ALTER TABLE delivery_commune_neighbor DROP FOREIGN KEY FK_8D2A2CE131A4F72');
            }

            if ($this->foreignKeyExists('delivery_commune_neighbor', 'FK_8D2A2CECA3465C1')) {
                $this->addSql('ALTER TABLE delivery_commune_neighbor DROP FOREIGN KEY FK_8D2A2CECA3465C1');
            }

            $this->renameIndexIfNeeded('delivery_commune_neighbor', 'IDX_8D2A2CE131A4F72', 'IDX_95A10D61131A344E');
            $this->renameIndexIfNeeded('delivery_commune_neighbor', 'IDX_8D2A2CECA3465C1', 'IDX_95A10D6177FD8A6B');

            if (!$this->foreignKeyExists('delivery_commune_neighbor', 'FK_95A10D61131A344E')) {
                $this->addSql('ALTER TABLE delivery_commune_neighbor ADD CONSTRAINT FK_95A10D61131A344E FOREIGN KEY (commune_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
            }

            if (!$this->foreignKeyExists('delivery_commune_neighbor', 'FK_95A10D6177FD8A6B')) {
                $this->addSql('ALTER TABLE delivery_commune_neighbor ADD CONSTRAINT FK_95A10D6177FD8A6B FOREIGN KEY (neighbor_id) REFERENCES delivery_commune (id) ON DELETE CASCADE');
            }
        }

        if ($this->tableExists('delivery_commune')) {
            if ($this->foreignKeyExists('delivery_commune', 'FK_E8FC6E30AFB460D0')) {
                $this->addSql('ALTER TABLE delivery_commune DROP FOREIGN KEY FK_E8FC6E30AFB460D0');
            }

            if ($this->foreignKeyExists('delivery_commune', 'FK_E8FC6E30CEE9A40D')) {
                $this->addSql('ALTER TABLE delivery_commune DROP FOREIGN KEY FK_E8FC6E30CEE9A40D');
            }

            $this->renameIndexIfNeeded('delivery_commune', 'UNIQ_E8FC6E305E237E06', 'UNIQ_71AE94C55E237E06');
            $this->renameIndexIfNeeded('delivery_commune', 'IDX_E8FC6E30AFB460D0', 'IDX_71AE94C563072109');
            $this->renameIndexIfNeeded('delivery_commune', 'IDX_E8FC6E30CEE9A40D', 'IDX_71AE94C52581CDE5');

            $this->addSql("ALTER TABLE delivery_commune CHANGE created_at created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE updated_at updated_at DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)'");

            if (!$this->foreignKeyExists('delivery_commune', 'FK_71AE94C563072109')) {
                $this->addSql('ALTER TABLE delivery_commune ADD CONSTRAINT FK_71AE94C563072109 FOREIGN KEY (local_pricing_zone_id) REFERENCES delivery_pricing_zone (id)');
            }

            if (!$this->foreignKeyExists('delivery_commune', 'FK_71AE94C52581CDE5')) {
                $this->addSql('ALTER TABLE delivery_commune ADD CONSTRAINT FK_71AE94C52581CDE5 FOREIGN KEY (barge_pricing_zone_id) REFERENCES delivery_pricing_zone (id)');
            }
        }
    }

    private function tableExists(string $tableName): bool
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?', [$tableName]) > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?', [$tableName, $columnName]) > 0;
    }

    private function indexExists(string $tableName, string $indexName): bool
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?', [$tableName, $indexName]) > 0;
    }

    private function foreignKeyExists(string $tableName, string $foreignKeyName): bool
    {
        return (int) $this->connection->fetchOne('SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND CONSTRAINT_NAME = ? AND CONSTRAINT_TYPE = ?', [$tableName, $foreignKeyName, 'FOREIGN KEY']) > 0;
    }

    private function renameIndexIfNeeded(string $tableName, string $oldName, string $newName): void
    {
        if ($this->indexExists($tableName, $oldName) && !$this->indexExists($tableName, $newName)) {
            $this->addSql(sprintf('ALTER TABLE %s RENAME INDEX %s TO %s', $tableName, $oldName, $newName));
        }
    }
}
