<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606101500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add courier assignment fields to customer orders';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order ADD assigned_courier_id INT DEFAULT NULL, ADD courier_assigned_at DATETIME DEFAULT NULL, ADD out_for_delivery_at DATETIME DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_3CF0A31E4B1E148F ON customer_order (assigned_courier_id)');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3CF0A31E4B1E148F FOREIGN KEY (assigned_courier_id) REFERENCES customer (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3CF0A31E4B1E148F');
        $this->addSql('DROP INDEX IDX_3CF0A31E4B1E148F ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP assigned_courier_id, DROP courier_assigned_at, DROP out_for_delivery_at');
    }
}
