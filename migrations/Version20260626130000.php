<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5S-B-bis: add requested appointment date and time for delivery point orders.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order ADD delivery_point_scheduled_date DATE DEFAULT NULL, ADD delivery_point_scheduled_time TIME DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP delivery_point_scheduled_date, DROP delivery_point_scheduled_time');
    }
}
