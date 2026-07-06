<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626194000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5V-A: add product minimum order lead time in hours.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product ADD minimum_order_lead_time_hours INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product DROP minimum_order_lead_time_hours');
    }
}
