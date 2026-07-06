<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604112000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make Hodina settings more generic and add delivered communes.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hodina_setting ADD delivered_communes LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE hodina_setting DROP delivered_communes');
    }
}
