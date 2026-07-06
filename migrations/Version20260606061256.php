<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260606061256 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX IDX_CUSTOMER_RESET_PASSWORD_TOKEN ON customer');
        $this->addSql('ALTER TABLE customer CHANGE reset_password_token_expires_at reset_password_token_expires_at DATETIME DEFAULT NULL');
        $this->addSql('ALTER TABLE sms_log CHANGE recipient_type recipient_type VARCHAR(30) NOT NULL, CHANGE status status VARCHAR(20) NOT NULL');
        $this->addSql('ALTER TABLE sms_log RENAME INDEX idx_sms_log_customer_order TO IDX_A9E43D70A15A2E17');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE customer CHANGE reset_password_token_expires_at reset_password_token_expires_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
        $this->addSql('CREATE INDEX IDX_CUSTOMER_RESET_PASSWORD_TOKEN ON customer (reset_password_token)');
        $this->addSql('ALTER TABLE sms_log CHANGE recipient_type recipient_type VARCHAR(30) DEFAULT \'customer\' NOT NULL, CHANGE status status VARCHAR(20) DEFAULT \'SENT\' NOT NULL');
        $this->addSql('ALTER TABLE sms_log RENAME INDEX idx_a9e43d70a15a2e17 TO IDX_SMS_LOG_CUSTOMER_ORDER');
    }
}
