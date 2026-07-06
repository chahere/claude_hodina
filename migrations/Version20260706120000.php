<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260706120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5AD-1: add faq_entry, chatbot_conversation, chatbot_message, support_ticket and support_ticket_message tables.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE faq_entry (id INT AUTO_INCREMENT NOT NULL, question VARCHAR(255) NOT NULL, answer LONGTEXT NOT NULL, is_active TINYINT(1) NOT NULL, display_order INT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_FAQ_ENTRY_ACTIVE_ORDER (is_active, display_order), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE chatbot_conversation (id INT AUTO_INCREMENT NOT NULL, customer_id INT NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, INDEX IDX_CHATBOT_CONVERSATION_CUSTOMER (customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE chatbot_message (id INT AUTO_INCREMENT NOT NULL, chatbot_conversation_id INT NOT NULL, role VARCHAR(20) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_CHATBOT_MESSAGE_CONVERSATION (chatbot_conversation_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE support_ticket (id INT AUTO_INCREMENT NOT NULL, customer_id INT DEFAULT NULL, chatbot_conversation_id INT DEFAULT NULL, origin VARCHAR(30) NOT NULL, status VARCHAR(20) NOT NULL, contact_name VARCHAR(150) NOT NULL, contact_email VARCHAR(180) NOT NULL, contact_phone VARCHAR(30) DEFAULT NULL, subject VARCHAR(200) NOT NULL, closed_at DATETIME DEFAULT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_SUPPORT_TICKET_CHATBOT_CONVERSATION (chatbot_conversation_id), INDEX IDX_SUPPORT_TICKET_CUSTOMER (customer_id), INDEX IDX_SUPPORT_TICKET_STATUS (status), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql("CREATE TABLE support_ticket_message (id INT AUTO_INCREMENT NOT NULL, support_ticket_id INT NOT NULL, author_customer_id INT DEFAULT NULL, sender_type VARCHAR(20) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_SUPPORT_TICKET_MESSAGE_TICKET (support_ticket_id), INDEX IDX_SUPPORT_TICKET_MESSAGE_AUTHOR (author_customer_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('ALTER TABLE chatbot_conversation ADD CONSTRAINT FK_CHATBOT_CONVERSATION_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id)');
        $this->addSql('ALTER TABLE chatbot_message ADD CONSTRAINT FK_CHATBOT_MESSAGE_CONVERSATION FOREIGN KEY (chatbot_conversation_id) REFERENCES chatbot_conversation (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_ticket ADD CONSTRAINT FK_SUPPORT_TICKET_CUSTOMER FOREIGN KEY (customer_id) REFERENCES customer (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_ticket ADD CONSTRAINT FK_SUPPORT_TICKET_CHATBOT_CONVERSATION FOREIGN KEY (chatbot_conversation_id) REFERENCES chatbot_conversation (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE support_ticket_message ADD CONSTRAINT FK_SUPPORT_TICKET_MESSAGE_TICKET FOREIGN KEY (support_ticket_id) REFERENCES support_ticket (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE support_ticket_message ADD CONSTRAINT FK_SUPPORT_TICKET_MESSAGE_AUTHOR FOREIGN KEY (author_customer_id) REFERENCES customer (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE support_ticket_message DROP FOREIGN KEY FK_SUPPORT_TICKET_MESSAGE_TICKET');
        $this->addSql('ALTER TABLE support_ticket_message DROP FOREIGN KEY FK_SUPPORT_TICKET_MESSAGE_AUTHOR');
        $this->addSql('ALTER TABLE support_ticket DROP FOREIGN KEY FK_SUPPORT_TICKET_CUSTOMER');
        $this->addSql('ALTER TABLE support_ticket DROP FOREIGN KEY FK_SUPPORT_TICKET_CHATBOT_CONVERSATION');
        $this->addSql('ALTER TABLE chatbot_message DROP FOREIGN KEY FK_CHATBOT_MESSAGE_CONVERSATION');
        $this->addSql('ALTER TABLE chatbot_conversation DROP FOREIGN KEY FK_CHATBOT_CONVERSATION_CUSTOMER');

        $this->addSql('DROP TABLE support_ticket_message');
        $this->addSql('DROP TABLE support_ticket');
        $this->addSql('DROP TABLE chatbot_message');
        $this->addSql('DROP TABLE chatbot_conversation');
        $this->addSql('DROP TABLE faq_entry');
    }
}
