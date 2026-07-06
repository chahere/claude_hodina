<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619170000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5K v8: add customer default delivery address.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer ADD delivery_address_id INT DEFAULT NULL');
        $this->addSql('CREATE INDEX IDX_81398E09EBF23851 ON customer (delivery_address_id)');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E09BBE8B5F9 FOREIGN KEY (delivery_address_id) REFERENCES address (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E09BBE8B5F9');
        $this->addSql('DROP INDEX IDX_81398E09EBF23851 ON customer');
        $this->addSql('ALTER TABLE customer DROP delivery_address_id');
    }
}
