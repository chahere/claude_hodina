<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260623210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5O-A: add encrypted customer delivery validation code fields on customer_order.';
    }

    public function up(Schema $schema): void
    {
        // Les champs de dates sont volontairement en DATETIME mutable, sans commentaire
        // (DC2Type:datetime_immutable), pour rester alignés avec le mapping CustomerOrder.
        // Les compteurs sont ajoutés avec DEFAULT 0 pour initialiser les commandes existantes,
        // puis normalisés sans DEFAULT pour que doctrine:schema:validate reste synchronisé.
        $this->addSql('ALTER TABLE customer_order ADD delivery_validation_code_encrypted LONGTEXT DEFAULT NULL, ADD delivery_validation_code_sent_at DATETIME DEFAULT NULL, ADD delivery_validation_code_validated_at DATETIME DEFAULT NULL, ADD delivery_validation_code_send_count INT DEFAULT 0 NOT NULL, ADD delivery_validation_code_failed_attempts INT DEFAULT 0 NOT NULL, ADD delivery_validation_sms_log_id INT DEFAULT NULL, ADD delivery_validation_email_log_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE customer_order CHANGE delivery_validation_code_sent_at delivery_validation_code_sent_at DATETIME DEFAULT NULL, CHANGE delivery_validation_code_validated_at delivery_validation_code_validated_at DATETIME DEFAULT NULL, CHANGE delivery_validation_code_send_count delivery_validation_code_send_count INT NOT NULL, CHANGE delivery_validation_code_failed_attempts delivery_validation_code_failed_attempts INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP delivery_validation_code_encrypted, DROP delivery_validation_code_sent_at, DROP delivery_validation_code_validated_at, DROP delivery_validation_code_send_count, DROP delivery_validation_code_failed_attempts, DROP delivery_validation_sms_log_id, DROP delivery_validation_email_log_id');
    }
}
