<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615225836 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5G-E0: add immutable order address snapshots and allow deleting customer address book entries safely.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order ADD delivery_address_label VARCHAR(60) DEFAULT NULL, ADD delivery_address_line1 VARCHAR(180) DEFAULT NULL, ADD delivery_address_line2 VARCHAR(180) DEFAULT NULL, ADD delivery_address_postal_code VARCHAR(20) DEFAULT NULL, ADD delivery_address_commune VARCHAR(120) DEFAULT NULL, ADD delivery_address_zone_code VARCHAR(40) DEFAULT NULL, ADD delivery_address_zone_name VARCHAR(120) DEFAULT NULL, ADD delivery_address_notes LONGTEXT DEFAULT NULL, ADD billing_address_label VARCHAR(60) DEFAULT NULL, ADD billing_address_line1 VARCHAR(180) DEFAULT NULL, ADD billing_address_line2 VARCHAR(180) DEFAULT NULL, ADD billing_address_postal_code VARCHAR(20) DEFAULT NULL, ADD billing_address_commune VARCHAR(120) DEFAULT NULL, ADD billing_address_zone_code VARCHAR(40) DEFAULT NULL, ADD billing_address_zone_name VARCHAR(120) DEFAULT NULL, ADD billing_address_notes LONGTEXT DEFAULT NULL');

        $this->addSql(<<<'SQL'
            UPDATE customer_order co
            LEFT JOIN address a ON a.id = co.delivery_address_id
            LEFT JOIN delivery_zone dz ON dz.id = a.delivery_zone_id
            SET
                co.delivery_address_label = a.label,
                co.delivery_address_line1 = a.line1,
                co.delivery_address_line2 = a.line2,
                co.delivery_address_postal_code = a.postal_code,
                co.delivery_address_commune = a.commune,
                co.delivery_address_zone_code = dz.code,
                co.delivery_address_zone_name = dz.name,
                co.delivery_address_notes = a.notes
            WHERE co.delivery_address_id IS NOT NULL
        SQL);

        $this->addSql(<<<'SQL'
            UPDATE customer_order co
            LEFT JOIN customer c ON c.id = co.customer_id
            LEFT JOIN address a ON a.id = c.billing_address_id
            LEFT JOIN delivery_zone dz ON dz.id = a.delivery_zone_id
            SET
                co.billing_address_label = a.label,
                co.billing_address_line1 = a.line1,
                co.billing_address_line2 = a.line2,
                co.billing_address_postal_code = a.postal_code,
                co.billing_address_commune = a.commune,
                co.billing_address_zone_code = dz.code,
                co.billing_address_zone_name = dz.name,
                co.billing_address_notes = a.notes
            WHERE c.billing_address_id IS NOT NULL
        SQL);

        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A3EBF23851');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A3EBF23851 FOREIGN KEY (delivery_address_id) REFERENCES address (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A3EBF23851');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A3EBF23851 FOREIGN KEY (delivery_address_id) REFERENCES address (id)');
        $this->addSql('ALTER TABLE customer_order DROP delivery_address_label, DROP delivery_address_line1, DROP delivery_address_line2, DROP delivery_address_postal_code, DROP delivery_address_commune, DROP delivery_address_zone_code, DROP delivery_address_zone_name, DROP delivery_address_notes, DROP billing_address_label, DROP billing_address_line1, DROP billing_address_line2, DROP billing_address_postal_code, DROP billing_address_commune, DROP billing_address_zone_code, DROP billing_address_zone_name, DROP billing_address_notes');
    }
}
