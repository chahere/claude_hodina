<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260203160817 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order ADD submitted_at DATETIME DEFAULT NULL, ADD delivery_zone_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A395328075 FOREIGN KEY (delivery_zone_id) REFERENCES delivery_zone (id)');
        $this->addSql('CREATE INDEX IDX_3B1CE6A395328075 ON customer_order (delivery_zone_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A395328075');
        $this->addSql('DROP INDEX IDX_3B1CE6A395328075 ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP submitted_at, DROP delivery_zone_id');
    }
}
