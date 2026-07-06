<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625214500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5S-A: add delivery points, time windows and product delivery point constraints.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE delivery_point (id INT AUTO_INCREMENT NOT NULL, delivery_commune_id INT NOT NULL, name VARCHAR(160) NOT NULL, code VARCHAR(80) NOT NULL, type VARCHAR(30) NOT NULL, is_active TINYINT(1) NOT NULL, line1 VARCHAR(255) NOT NULL, line2 VARCHAR(255) DEFAULT NULL, postal_code VARCHAR(10) DEFAULT NULL, commune_name VARCHAR(120) NOT NULL, public_instructions LONGTEXT DEFAULT NULL, courier_instructions LONGTEXT DEFAULT NULL, gps_latitude NUMERIC(10, 7) DEFAULT NULL, gps_longitude NUMERIC(10, 7) DEFAULT NULL, gps_accuracy_meters INT DEFAULT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_DELIVERY_POINT_CODE (code), INDEX IDX_DELIVERY_POINT_COMMUNE (delivery_commune_id), INDEX IDX_DELIVERY_POINT_TYPE_ACTIVE (type, is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE delivery_point_time_window (id INT AUTO_INCREMENT NOT NULL, delivery_point_id INT NOT NULL, label VARCHAR(120) DEFAULT NULL, weekday INT DEFAULT NULL, start_time TIME NOT NULL, end_time TIME NOT NULL, is_active TINYINT(1) NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_DELIVERY_POINT_TIME_WINDOW_POINT (delivery_point_id), INDEX IDX_DELIVERY_POINT_TIME_WINDOW_DAY_ACTIVE (weekday, is_active), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE product_delivery_point (id INT AUTO_INCREMENT NOT NULL, product_id INT NOT NULL, delivery_point_id INT NOT NULL, is_active TINYINT(1) NOT NULL, sort_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_PRODUCT_DELIVERY_POINT_PRODUCT (product_id), INDEX IDX_PRODUCT_DELIVERY_POINT_POINT (delivery_point_id), UNIQUE INDEX UNIQ_PRODUCT_DELIVERY_POINT (product_id, delivery_point_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE delivery_point ADD CONSTRAINT FK_DELIVERY_POINT_COMMUNE FOREIGN KEY (delivery_commune_id) REFERENCES delivery_commune (id) ON DELETE RESTRICT');
        $this->addSql('ALTER TABLE delivery_point_time_window ADD CONSTRAINT FK_DELIVERY_POINT_TIME_WINDOW_POINT FOREIGN KEY (delivery_point_id) REFERENCES delivery_point (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_delivery_point ADD CONSTRAINT FK_PRODUCT_DELIVERY_POINT_PRODUCT FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE product_delivery_point ADD CONSTRAINT FK_PRODUCT_DELIVERY_POINT_POINT FOREIGN KEY (delivery_point_id) REFERENCES delivery_point (id) ON DELETE CASCADE');

        $this->addSql("ALTER TABLE product ADD delivery_mode VARCHAR(40) NOT NULL DEFAULT 'STANDARD'");
        $this->addSql("ALTER TABLE product CHANGE delivery_mode delivery_mode VARCHAR(40) NOT NULL");

        $this->seedInitialDeliveryPoints();
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE product_delivery_point DROP FOREIGN KEY FK_PRODUCT_DELIVERY_POINT_PRODUCT');
        $this->addSql('ALTER TABLE product_delivery_point DROP FOREIGN KEY FK_PRODUCT_DELIVERY_POINT_POINT');
        $this->addSql('ALTER TABLE delivery_point_time_window DROP FOREIGN KEY FK_DELIVERY_POINT_TIME_WINDOW_POINT');
        $this->addSql('ALTER TABLE delivery_point DROP FOREIGN KEY FK_DELIVERY_POINT_COMMUNE');
        $this->addSql('DROP TABLE product_delivery_point');
        $this->addSql('DROP TABLE delivery_point_time_window');
        $this->addSql('DROP TABLE delivery_point');
        $this->addSql('ALTER TABLE product DROP delivery_mode');
    }

    private function seedInitialDeliveryPoints(): void
    {
        $this->addSql("INSERT INTO delivery_point (delivery_commune_id, name, code, type, is_active, line1, line2, postal_code, commune_name, public_instructions, courier_instructions, gps_latitude, gps_longitude, gps_accuracy_meters, sort_order, created_at, updated_at) SELECT dc.id, 'Accueil barge Petite-Terre', 'BARGE_PETITE_TERRE', 'BARGE', 1, 'Accueil de la barge de Petite-Terre', NULL, '97615', 'Dzaoudzi', 'Remise au niveau de l’accueil de la barge côté Petite-Terre. Reste joignable au téléphone au moment du créneau.', 'Point de remise client : accueil barge Petite-Terre. Appeler le client en arrivant si besoin.', NULL, NULL, NULL, 10, NOW(), NULL FROM delivery_commune dc WHERE (dc.slug = 'dzaoudzi' OR dc.name = 'Dzaoudzi') AND NOT EXISTS (SELECT 1 FROM delivery_point WHERE code = 'BARGE_PETITE_TERRE') ORDER BY dc.id ASC LIMIT 1");
        $this->addSql("INSERT INTO delivery_point (delivery_commune_id, name, code, type, is_active, line1, line2, postal_code, commune_name, public_instructions, courier_instructions, gps_latitude, gps_longitude, gps_accuracy_meters, sort_order, created_at, updated_at) SELECT dc.id, 'Accueil passager aéroport Pamandzi', 'AEROPORT_PAMANDZI_PASSAGERS', 'AIRPORT', 1, 'Accueil passager de l’aéroport de Pamandzi', NULL, '97615', 'Pamandzi', 'Remise à l’accueil passager de l’aéroport de Pamandzi. Reste joignable au téléphone au moment du créneau.', 'Point de remise client : accueil passager aéroport Pamandzi. Appeler le client en arrivant si besoin.', NULL, NULL, NULL, 20, NOW(), NULL FROM delivery_commune dc WHERE (dc.slug = 'pamandzi' OR dc.name = 'Pamandzi') AND NOT EXISTS (SELECT 1 FROM delivery_point WHERE code = 'AEROPORT_PAMANDZI_PASSAGERS') ORDER BY dc.id ASC LIMIT 1");

        foreach (['BARGE_PETITE_TERRE', 'AEROPORT_PAMANDZI_PASSAGERS'] as $pointCode) {
            $this->addSql(sprintf("INSERT INTO delivery_point_time_window (delivery_point_id, label, weekday, start_time, end_time, is_active, sort_order, created_at, updated_at) SELECT dp.id, 'Matin', NULL, '08:00:00', '12:00:00', 1, 10, NOW(), NULL FROM delivery_point dp WHERE dp.code = '%s' AND NOT EXISTS (SELECT 1 FROM delivery_point_time_window tw WHERE tw.delivery_point_id = dp.id AND tw.label = 'Matin' AND tw.start_time = '08:00:00' AND tw.end_time = '12:00:00')", $pointCode));
            $this->addSql(sprintf("INSERT INTO delivery_point_time_window (delivery_point_id, label, weekday, start_time, end_time, is_active, sort_order, created_at, updated_at) SELECT dp.id, 'Après-midi', NULL, '14:00:00', '18:00:00', 1, 20, NOW(), NULL FROM delivery_point dp WHERE dp.code = '%s' AND NOT EXISTS (SELECT 1 FROM delivery_point_time_window tw WHERE tw.delivery_point_id = dp.id AND tw.label = 'Après-midi' AND tw.start_time = '14:00:00' AND tw.end_time = '18:00:00')", $pointCode));
        }
    }
}
