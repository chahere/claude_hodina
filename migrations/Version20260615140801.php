<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260615140801 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5H-A: journalisation des e-mails de commande';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE email_log (id INT AUTO_INCREMENT NOT NULL, customer_order_id INT DEFAULT NULL, customer_id INT DEFAULT NULL, recipient_email VARCHAR(180) NOT NULL, subject VARCHAR(255) NOT NULL, template_key VARCHAR(100) NOT NULL, event_key VARCHAR(100) NOT NULL, status VARCHAR(20) NOT NULL, error_message LONGTEXT DEFAULT NULL, sent_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, INDEX idx_email_log_customer_order (customer_order_id), INDEX idx_email_log_customer (customer_id), INDEX IDX_EMAIL_LOG_STATUS (status), INDEX IDX_EMAIL_LOG_EVENT_KEY (event_key), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_EMAIL_LOG_CUSTOMER_ORDER FOREIGN KEY (customer_order_id) REFERENCES customer_order (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE email_log ADD CONSTRAINT FK_EMAIL_LOG_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_log DROP FOREIGN KEY FK_EMAIL_LOG_CUSTOMER_ORDER');
        $this->addSql('ALTER TABLE email_log DROP FOREIGN KEY FK_EMAIL_LOG_CUSTOMER');
        $this->addSql('DROP TABLE email_log');
    }
}
