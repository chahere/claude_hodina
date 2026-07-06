<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5Q-A: add courier payout history and payout lines.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE courier_payout (id INT AUTO_INCREMENT NOT NULL, courier_id INT NOT NULL, period_start DATE NOT NULL, period_end DATE NOT NULL, payment_due_date DATE NOT NULL, status VARCHAR(40) NOT NULL, total_amount NUMERIC(10, 2) NOT NULL, orders_count INT NOT NULL, validated_at DATETIME DEFAULT NULL, paid_at DATETIME DEFAULT NULL, payment_method VARCHAR(120) DEFAULT NULL, payment_reference VARCHAR(180) DEFAULT NULL, admin_note LONGTEXT DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, INDEX IDX_ED212DCBE3D8151C (courier_id), UNIQUE INDEX uniq_courier_payout_period (courier_id, period_start, period_end), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql("CREATE TABLE courier_payout_line (id INT AUTO_INCREMENT NOT NULL, courier_payout_id INT NOT NULL, customer_order_id INT NOT NULL, order_reference VARCHAR(80) NOT NULL, delivered_at DATETIME NOT NULL, customer_commune VARCHAR(120) DEFAULT NULL, courier_payout_amount NUMERIC(10, 2) NOT NULL, delivery_fee_customer NUMERIC(10, 2) NOT NULL, snapshot JSON DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_92F3CBB297F8F6CD (courier_payout_id), UNIQUE INDEX uniq_courier_payout_line_order (customer_order_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE courier_payout ADD CONSTRAINT FK_8E1E3842D0C43D3B FOREIGN KEY (courier_id) REFERENCES customer (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE courier_payout_line ADD CONSTRAINT FK_97899D4530E42AB4 FOREIGN KEY (courier_payout_id) REFERENCES courier_payout (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE courier_payout_line ADD CONSTRAINT FK_97899D458D9F6D38 FOREIGN KEY (customer_order_id) REFERENCES customer_order (id) ON DELETE RESTRICT');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE courier_payout_line DROP FOREIGN KEY FK_97899D4530E42AB4');
        $this->addSql('ALTER TABLE courier_payout_line DROP FOREIGN KEY FK_97899D458D9F6D38');
        $this->addSql('ALTER TABLE courier_payout DROP FOREIGN KEY FK_8E1E3842D0C43D3B');
        $this->addSql('DROP TABLE courier_payout_line');
        $this->addSql('DROP TABLE courier_payout');
    }
}
