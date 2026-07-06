<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626013000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5S-B: snapshot selected delivery point, time window and customer instructions on orders.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE customer_order ADD delivery_point_id INT DEFAULT NULL, ADD delivery_point_name VARCHAR(160) DEFAULT NULL, ADD delivery_point_code VARCHAR(80) DEFAULT NULL, ADD delivery_point_type VARCHAR(30) DEFAULT NULL, ADD delivery_point_address_line1 VARCHAR(255) DEFAULT NULL, ADD delivery_point_address_line2 VARCHAR(255) DEFAULT NULL, ADD delivery_point_postal_code VARCHAR(10) DEFAULT NULL, ADD delivery_point_commune VARCHAR(120) DEFAULT NULL, ADD delivery_point_public_instructions LONGTEXT DEFAULT NULL, ADD delivery_point_courier_instructions LONGTEXT DEFAULT NULL, ADD delivery_point_customer_instructions LONGTEXT DEFAULT NULL, ADD delivery_point_gps_latitude NUMERIC(10, 7) DEFAULT NULL, ADD delivery_point_gps_longitude NUMERIC(10, 7) DEFAULT NULL, ADD delivery_point_gps_accuracy_meters INT DEFAULT NULL, ADD delivery_point_time_window_label VARCHAR(160) DEFAULT NULL, ADD delivery_point_time_window_weekday INT DEFAULT NULL, ADD delivery_point_start_time TIME DEFAULT NULL, ADD delivery_point_end_time TIME DEFAULT NULL");
        $this->addSql('CREATE INDEX IDX_3B1CE6A3A1492FCE ON customer_order (delivery_point_id)');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A3A1492FCE FOREIGN KEY (delivery_point_id) REFERENCES delivery_point (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A3A1492FCE');
        $this->addSql('DROP INDEX IDX_3B1CE6A3A1492FCE ON customer_order');
        $this->addSql('ALTER TABLE customer_order DROP delivery_point_id, DROP delivery_point_name, DROP delivery_point_code, DROP delivery_point_type, DROP delivery_point_address_line1, DROP delivery_point_address_line2, DROP delivery_point_postal_code, DROP delivery_point_commune, DROP delivery_point_public_instructions, DROP delivery_point_courier_instructions, DROP delivery_point_customer_instructions, DROP delivery_point_gps_latitude, DROP delivery_point_gps_longitude, DROP delivery_point_gps_accuracy_meters, DROP delivery_point_time_window_label, DROP delivery_point_time_window_weekday, DROP delivery_point_start_time, DROP delivery_point_end_time');
    }
}
