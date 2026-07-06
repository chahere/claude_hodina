<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625163000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5R-A: add customer order feedback for client cancellation reasons and future reviews.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE customer_order_feedback (id INT AUTO_INCREMENT NOT NULL, customer_order_id INT NOT NULL, customer_id INT DEFAULT NULL, seller_id INT DEFAULT NULL, courier_id INT DEFAULT NULL, target_type VARCHAR(30) NOT NULL, target_key VARCHAR(80) NOT NULL, rating INT DEFAULT NULL, reason VARCHAR(80) DEFAULT NULL, comment LONGTEXT DEFAULT NULL, source VARCHAR(40) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_AABC9223A15A2E17 (customer_order_id), INDEX IDX_AABC92239395C3F3 (customer_id), INDEX IDX_AABC92238DE820D9 (seller_id), INDEX IDX_AABC9223E3D8151C (courier_id), UNIQUE INDEX uniq_customer_order_feedback_target (customer_order_id, target_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE customer_order_feedback ADD CONSTRAINT FK_ORDER_FEEDBACK_ORDER FOREIGN KEY (customer_order_id) REFERENCES customer_order (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE customer_order_feedback ADD CONSTRAINT FK_ORDER_FEEDBACK_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE customer_order_feedback ADD CONSTRAINT FK_ORDER_FEEDBACK_SELLER FOREIGN KEY (seller_id) REFERENCES seller (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE customer_order_feedback ADD CONSTRAINT FK_ORDER_FEEDBACK_COURIER FOREIGN KEY (courier_id) REFERENCES customer (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order_feedback DROP FOREIGN KEY FK_ORDER_FEEDBACK_ORDER');
        $this->addSql('ALTER TABLE customer_order_feedback DROP FOREIGN KEY FK_ORDER_FEEDBACK_CUSTOMER');
        $this->addSql('ALTER TABLE customer_order_feedback DROP FOREIGN KEY FK_ORDER_FEEDBACK_SELLER');
        $this->addSql('ALTER TABLE customer_order_feedback DROP FOREIGN KEY FK_ORDER_FEEDBACK_COURIER');
        $this->addSql('DROP TABLE customer_order_feedback');
    }
}
