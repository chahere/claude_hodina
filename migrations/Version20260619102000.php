<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260619102000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5K: ajoute les coordonnees GPS facultatives sur les adresses de livraison et leur snapshot commande.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE address ADD gps_latitude NUMERIC(10, 7) DEFAULT NULL, ADD gps_longitude NUMERIC(10, 7) DEFAULT NULL, ADD gps_accuracy_meters NUMERIC(8, 2) DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order ADD delivery_address_gps_latitude NUMERIC(10, 7) DEFAULT NULL, ADD delivery_address_gps_longitude NUMERIC(10, 7) DEFAULT NULL, ADD delivery_address_gps_accuracy_meters NUMERIC(8, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP delivery_address_gps_latitude, DROP delivery_address_gps_longitude, DROP delivery_address_gps_accuracy_meters');
        $this->addSql('ALTER TABLE address DROP gps_latitude, DROP gps_longitude, DROP gps_accuracy_meters');
    }
}
