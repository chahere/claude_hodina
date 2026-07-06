<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260626151000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5U-A: configure order email sender in EasyAdmin and log sender metadata.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE email_log ADD from_email VARCHAR(180) DEFAULT NULL, ADD from_name VARCHAR(120) DEFAULT NULL, ADD reply_to_email VARCHAR(180) DEFAULT NULL, ADD reply_to_name VARCHAR(120) DEFAULT NULL');

        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) VALUES
            ('email_sender_name', 'Nom expéditeur des e-mails commande', 'Hodina', 'Nom affiché comme expéditeur pour les e-mails de commande, statut, collecte vendeur et code de réception.', 'text', 'email_branding', 'Branding e-mail', 10, 1, 0, NOW()),
            ('email_sender_email', 'Adresse expéditeur des e-mails commande', 'commande@hodina.fr', 'Adresse From utilisée pour les e-mails de commande, statut, collecte vendeur et code de réception. À garder sur le domaine hodina.fr pour la délivrabilité.', 'email', 'email_branding', 'Branding e-mail', 11, 1, 0, NOW()),
            ('email_reply_to_name', 'Nom réponse e-mails commande', 'Service commande Hodina', 'Nom utilisé dans Reply-To. Même si une adresse de réponse existe, les modèles rappellent au client de ne pas répondre directement.', 'text', 'email_branding', 'Branding e-mail', 12, 1, 0, NOW()),
            ('email_reply_to_email', 'Adresse réponse e-mails commande', 'commande@hodina.fr', 'Adresse Reply-To technique pour les clients mail. Les modèles affichent tout de même la mention de ne pas répondre directement.', 'email', 'email_branding', 'Branding e-mail', 13, 1, 0, NOW()),
            ('email_order_created_copy_email', 'Copie interne création commande', 'commande@hodina.fr', 'Adresse Hodina mise en copie cachée des e-mails ORDER_CREATED envoyés au client.', 'email', 'email_branding', 'Branding e-mail', 14, 1, 0, NOW())
            ON DUPLICATE KEY UPDATE
                label = VALUES(label),
                help = VALUES(help),
                field_type = VALUES(field_type),
                group_key = VALUES(group_key),
                group_label = VALUES(group_label),
                sort_order = VALUES(sort_order),
                is_editable = VALUES(is_editable),
                is_sensitive = VALUES(is_sensitive),
                updated_at = NOW()");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key IN ('email_sender_name', 'email_sender_email', 'email_reply_to_name', 'email_reply_to_email', 'email_order_created_copy_email')");
        $this->addSql('ALTER TABLE email_log DROP from_email, DROP from_name, DROP reply_to_email, DROP reply_to_name');
    }
}
