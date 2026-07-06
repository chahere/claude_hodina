<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260602152729 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer ADD billing_address_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer ADD CONSTRAINT FK_81398E0979D0C0E4 FOREIGN KEY (billing_address_id) REFERENCES address (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_81398E0979D0C0E4 ON customer (billing_address_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer DROP FOREIGN KEY FK_81398E0979D0C0E4');
        $this->addSql('DROP INDEX IDX_81398E0979D0C0E4 ON customer');
        $this->addSql('ALTER TABLE customer DROP billing_address_id');
    }
}
