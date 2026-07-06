<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5E - Add product margin pricing fields and initialize global margin setting';
    }

    public function up(Schema $schema): void
    {
        if (!$this->columnExists('product', 'producer_price')) {
            $this->addSql('ALTER TABLE product ADD producer_price NUMERIC(10, 2) DEFAULT NULL');
        }

        if (!$this->columnExists('product', 'margin_rate')) {
            $this->addSql('ALTER TABLE product ADD margin_rate NUMERIC(5, 2) DEFAULT NULL');
        }

        if (!$this->columnExists('seller', 'margin_rate')) {
            $this->addSql('ALTER TABLE seller ADD margin_rate NUMERIC(5, 2) DEFAULT NULL');
        }

        if (!$this->columnExists('order_item', 'producer_unit_price')) {
            $this->addSql('ALTER TABLE order_item ADD producer_unit_price NUMERIC(10, 2) DEFAULT NULL');
        }

        if (!$this->columnExists('order_item', 'applied_margin_rate')) {
            $this->addSql('ALTER TABLE order_item ADD applied_margin_rate NUMERIC(5, 2) DEFAULT NULL');
        }

        if (!$this->columnExists('order_item', 'hodina_margin_amount')) {
            $this->addSql('ALTER TABLE order_item ADD hodina_margin_amount NUMERIC(10, 2) DEFAULT NULL');
        }

        if ($this->columnExists('product', 'producer_price') && $this->columnExists('product', 'price')) {
            $this->addSql('UPDATE product SET producer_price = price WHERE producer_price IS NULL');
        }

        $exists = (int) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM hodina_setting WHERE setting_key = 'global_margin_rate'"
        );

        if ($exists === 0) {
            $this->addSql(<<<'SQL'
INSERT INTO hodina_setting (setting_key, label, value, help, field_type, updated_at)
VALUES (
    'global_margin_rate',
    'Marge globale Hodina (%)',
    '20.00',
    'Marge utilisée si aucune marge spécifique n’est définie sur le produit ou le vendeur.',
    'text',
    CURRENT_TIMESTAMP
)
SQL);
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->columnExists('order_item', 'hodina_margin_amount')) {
            $this->addSql('ALTER TABLE order_item DROP hodina_margin_amount');
        }

        if ($this->columnExists('order_item', 'applied_margin_rate')) {
            $this->addSql('ALTER TABLE order_item DROP applied_margin_rate');
        }

        if ($this->columnExists('order_item', 'producer_unit_price')) {
            $this->addSql('ALTER TABLE order_item DROP producer_unit_price');
        }

        if ($this->columnExists('seller', 'margin_rate')) {
            $this->addSql('ALTER TABLE seller DROP margin_rate');
        }

        if ($this->columnExists('product', 'margin_rate')) {
            $this->addSql('ALTER TABLE product DROP margin_rate');
        }

        if ($this->columnExists('product', 'producer_price')) {
            $this->addSql('ALTER TABLE product DROP producer_price');
        }

        $this->addSql("DELETE FROM hodina_setting WHERE setting_key = 'global_margin_rate'");
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        return (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName]
        ) > 0;
    }
}
