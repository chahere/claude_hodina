<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617162000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add delivery logistics snapshot to customer orders for audit and future analysis';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE customer_order ADD delivery_logistics_snapshot JSON DEFAULT NULL");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP delivery_logistics_snapshot');
    }
}

