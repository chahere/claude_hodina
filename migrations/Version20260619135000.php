<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619135000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5K-bis: ajoute le commentaire terrain livreur sur adresse et son snapshot commande.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address ADD courier_notes LONGTEXT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order ADD delivery_address_courier_notes LONGTEXT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP delivery_address_courier_notes');
        $this->addSql('ALTER TABLE address DROP courier_notes');
    }
}
