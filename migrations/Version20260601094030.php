<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260601094030 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE address (id INT AUTO_INCREMENT NOT NULL, label VARCHAR(60) DEFAULT NULL, line1 VARCHAR(180) NOT NULL, line2 VARCHAR(180) DEFAULT NULL, commune VARCHAR(120) NOT NULL, notes LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, customer_id INT NOT NULL, delivery_zone_id INT NOT NULL, INDEX IDX_D4E6F819395C3F3 (customer_id), INDEX IDX_D4E6F8195328075 (delivery_zone_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE category (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(120) NOT NULL, slug VARCHAR(255) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(100) NOT NULL, last_name VARCHAR(100) DEFAULT NULL, phone VARCHAR(30) NOT NULL, email VARCHAR(180) DEFAULT NULL, created_at DATETIME NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_order (id INT AUTO_INCREMENT NOT NULL, status VARCHAR(40) NOT NULL, payment_status VARCHAR(40) NOT NULL, subtotal NUMERIC(10, 2) NOT NULL, delivery_fee NUMERIC(10, 2) NOT NULL, total NUMERIC(10, 2) NOT NULL, created_at DATETIME NOT NULL, submitted_at DATETIME DEFAULT NULL, customer_id INT NOT NULL, delivery_address_id INT DEFAULT NULL, delivery_zone_id INT DEFAULT NULL, INDEX IDX_3B1CE6A39395C3F3 (customer_id), INDEX IDX_3B1CE6A3EBF23851 (delivery_address_id), INDEX IDX_3B1CE6A395328075 (delivery_zone_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE customer_signup (id INT AUTO_INCREMENT NOT NULL, first_name VARCHAR(80) NOT NULL, last_name VARCHAR(80) NOT NULL, email VARCHAR(180) NOT NULL, phone VARCHAR(30) NOT NULL, address VARCHAR(255) NOT NULL, zone VARCHAR(8) NOT NULL, cart_snapshot JSON NOT NULL, created_at DATETIME NOT NULL, status VARCHAR(30) NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE delivery_zone (id INT AUTO_INCREMENT NOT NULL, code VARCHAR(10) NOT NULL, name VARCHAR(100) NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_7177089D77153098 (code), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE order_item (id INT AUTO_INCREMENT NOT NULL, quantity INT NOT NULL, unit_price NUMERIC(10, 2) NOT NULL, line_total NUMERIC(10, 2) NOT NULL, customer_order_id INT NOT NULL, product_id INT NOT NULL, seller_id INT NOT NULL, INDEX IDX_52EA1F09A15A2E17 (customer_order_id), INDEX IDX_52EA1F094584665A (product_id), INDEX IDX_52EA1F098DE820D9 (seller_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product (id INT AUTO_INCREMENT NOT NULL, slug VARCHAR(255) NOT NULL, name VARCHAR(160) NOT NULL, description LONGTEXT DEFAULT NULL, price NUMERIC(10, 2) NOT NULL, unit VARCHAR(30) DEFAULT NULL, stock_qty INT DEFAULT NULL, is_unlimited_stock TINYINT NOT NULL, is_preorder TINYINT NOT NULL, manufacturing_days INT DEFAULT NULL, delivery_days INT NOT NULL, is_active TINYINT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, seller_id INT NOT NULL, category_id INT NOT NULL, UNIQUE INDEX UNIQ_D34A04AD989D9B62 (slug), INDEX IDX_D34A04AD8DE820D9 (seller_id), INDEX IDX_D34A04AD12469DE2 (category_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE product_image (id INT AUTO_INCREMENT NOT NULL, path VARCHAR(255) NOT NULL, position INT NOT NULL, created_at DATETIME NOT NULL, product_id INT NOT NULL, INDEX IDX_64617F034584665A (product_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE seller (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(150) NOT NULL, contact_name VARCHAR(120) DEFAULT NULL, phone VARCHAR(30) DEFAULT NULL, email VARCHAR(180) DEFAULT NULL, commune VARCHAR(120) DEFAULT NULL, is_active TINYINT NOT NULL, last_product_update_at DATETIME DEFAULT NULL, last_stock_update_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, delivery_zone_id INT NOT NULL, INDEX IDX_FB1AD3FC95328075 (delivery_zone_id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE sms_log (id INT AUTO_INCREMENT NOT NULL, phone VARCHAR(30) NOT NULL, message LONGTEXT NOT NULL, context VARCHAR(40) NOT NULL, created_at DATETIME NOT NULL, PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('CREATE TABLE messenger_messages (id BIGINT AUTO_INCREMENT NOT NULL, body LONGTEXT NOT NULL, headers LONGTEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at DATETIME NOT NULL, available_at DATETIME NOT NULL, delivered_at DATETIME DEFAULT NULL, INDEX IDX_75EA56E0FB7336F0E3BD61CE16BA31DBBF396750 (queue_name, available_at, delivered_at, id), PRIMARY KEY (id)) DEFAULT CHARACTER SET utf8mb4');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F819395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE address ADD CONSTRAINT FK_D4E6F8195328075 FOREIGN KEY (delivery_zone_id) REFERENCES delivery_zone (id)');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A39395C3F3 FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A3EBF23851 FOREIGN KEY (delivery_address_id) REFERENCES address (id)');
        $this->addSql('ALTER TABLE customer_order ADD CONSTRAINT FK_3B1CE6A395328075 FOREIGN KEY (delivery_zone_id) REFERENCES delivery_zone (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F09A15A2E17 FOREIGN KEY (customer_order_id) REFERENCES customer_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F094584665A FOREIGN KEY (product_id) REFERENCES product (id)');
        $this->addSql('ALTER TABLE order_item ADD CONSTRAINT FK_52EA1F098DE820D9 FOREIGN KEY (seller_id) REFERENCES seller (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD8DE820D9 FOREIGN KEY (seller_id) REFERENCES seller (id)');
        $this->addSql('ALTER TABLE product ADD CONSTRAINT FK_D34A04AD12469DE2 FOREIGN KEY (category_id) REFERENCES category (id)');
        $this->addSql('ALTER TABLE product_image ADD CONSTRAINT FK_64617F034584665A FOREIGN KEY (product_id) REFERENCES product (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE seller ADD CONSTRAINT FK_FB1AD3FC95328075 FOREIGN KEY (delivery_zone_id) REFERENCES delivery_zone (id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F819395C3F3');
        $this->addSql('ALTER TABLE address DROP FOREIGN KEY FK_D4E6F8195328075');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A39395C3F3');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A3EBF23851');
        $this->addSql('ALTER TABLE customer_order DROP FOREIGN KEY FK_3B1CE6A395328075');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F09A15A2E17');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F094584665A');
        $this->addSql('ALTER TABLE order_item DROP FOREIGN KEY FK_52EA1F098DE820D9');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD8DE820D9');
        $this->addSql('ALTER TABLE product DROP FOREIGN KEY FK_D34A04AD12469DE2');
        $this->addSql('ALTER TABLE product_image DROP FOREIGN KEY FK_64617F034584665A');
        $this->addSql('ALTER TABLE seller DROP FOREIGN KEY FK_FB1AD3FC95328075');
        $this->addSql('DROP TABLE address');
        $this->addSql('DROP TABLE category');
        $this->addSql('DROP TABLE customer');
        $this->addSql('DROP TABLE customer_order');
        $this->addSql('DROP TABLE customer_signup');
        $this->addSql('DROP TABLE delivery_zone');
        $this->addSql('DROP TABLE order_item');
        $this->addSql('DROP TABLE product');
        $this->addSql('DROP TABLE product_image');
        $this->addSql('DROP TABLE seller');
        $this->addSql('DROP TABLE sms_log');
        $this->addSql('DROP TABLE messenger_messages');
    }
}
