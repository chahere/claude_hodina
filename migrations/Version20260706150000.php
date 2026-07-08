<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260706150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5AD-2: add ai_chatbot_setting table (LLM provider/model/API key, encrypted at rest).';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE ai_chatbot_setting (id INT AUTO_INCREMENT NOT NULL, provider VARCHAR(30) NOT NULL, model VARCHAR(120) NOT NULL, api_key_encrypted LONGTEXT DEFAULT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE ai_chatbot_setting');
    }
}
