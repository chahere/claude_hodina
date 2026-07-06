<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613130000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5J: fusionne préouverture et maintenance en mode commerce avec ROLE_COMMERCE_TESTER, puis nettoie les anciens réglages J5I';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE IF NOT EXISTS launch_subscriber (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, source VARCHAR(80) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_LAUNCH_SUBSCRIBER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE launch_subscriber CHANGE created_at created_at DATETIME NOT NULL');

        $this->upsertSetting(
            'commerce_mode',
            'Mode commerce',
            "COALESCE((SELECT CASE WHEN old_countdown.value = '1' THEN 'preopening' ELSE 'open' END FROM hodina_setting old_countdown WHERE old_countdown.setting_key = 'is_countdown_enabled' LIMIT 1), 'preopening')",
            'Mode général du portail : open = commandes ouvertes, preopening = préouverture, maintenance = mise à jour production, closed = fermeture manuelle.',
            'choice'
        );

        $this->upsertSetting(
            'commerce_reopens_at',
            'Date de réactivation des commandes',
            "COALESCE((SELECT old_opening.value FROM hodina_setting old_opening WHERE old_opening.setting_key = 'sales_opening_at' LIMIT 1), '')",
            'Date et heure à laquelle les commandes publiques seront réactivées. Exemple : 2026-06-30 18:00.',
            'text'
        );

        $this->upsertSetting(
            'commerce_cart_locked',
            'Bloquer panier et commandes publiques',
            "COALESCE((SELECT old_locked.value FROM hodina_setting old_locked WHERE old_locked.setting_key = 'is_cart_locked_before_opening' LIMIT 1), '1')",
            'Bloque côté serveur l’ajout au panier et la validation de commande pour le public.',
            'boolean'
        );

        $this->upsertSetting(
            'commerce_allow_testers',
            'Autoriser les testeurs pendant le blocage',
            "'1'",
            'Autorise les comptes avec le rôle ROLE_COMMERCE_TESTER, ainsi que les administrateurs, à utiliser le portail normalement malgré le blocage public.',
            'boolean'
        );

        $this->upsertSetting(
            'commerce_banner_title',
            'Titre bannière commerce',
            "COALESCE((SELECT old_title.value FROM hodina_setting old_title WHERE old_title.setting_key = 'countdown_title' LIMIT 1), 'Votre marché en ligne de produits locaux arrive bientôt')",
            'Titre affiché dans la bannière de préouverture ou de maintenance commerciale.',
            'text'
        );

        $this->upsertSetting(
            'commerce_banner_message',
            'Message bannière commerce',
            "COALESCE((SELECT old_message.value FROM hodina_setting old_message WHERE old_message.setting_key = 'countdown_message' LIMIT 1), 'Le catalogue est accessible, mais la prise de commande sera possible à la date officielle. Laisse nous ton e-mail pour être informé de l’ouverture.')",
            'Texte affiché sous le compte à rebours ou dans la bannière de blocage commercial.',
            'textarea'
        );

        $this->upsertSetting(
            'commerce_banner_button_label',
            'Bouton capture e-mail commerce',
            "COALESCE((SELECT old_button.value FROM hodina_setting old_button WHERE old_button.setting_key = 'countdown_button_label' LIMIT 1), 'Me faire signe à l’ouverture')",
            'Libellé du bouton de capture e-mail affiché dans la bannière.',
            'text'
        );

        $this->upsertSetting(
            'commerce_email_capture_enabled',
            'Capture e-mail commerce active',
            "COALESCE((SELECT old_capture.value FROM hodina_setting old_capture WHERE old_capture.setting_key = 'is_email_capture_enabled' LIMIT 1), '1')",
            'Active ou désactive le formulaire e-mail dans la bannière.',
            'boolean'
        );

        $this->upsertSetting(
            'commerce_success_message',
            'Message succès inscription commerce',
            "COALESCE((SELECT old_success.value FROM hodina_setting old_success WHERE old_success.setting_key = 'countdown_success_message' LIMIT 1), 'Merci, ton e-mail est bien enregistré. On te préviendra dès que les commandes seront disponibles.')",
            'Message affiché après capture e-mail réussie.',
            'textarea'
        );

        $this->addSql("DELETE FROM hodina_setting WHERE setting_key IN ('is_countdown_enabled', 'sales_opening_at', 'countdown_title', 'countdown_message', 'countdown_button_label', 'is_email_capture_enabled', 'is_cart_locked_before_opening', 'countdown_success_message')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key IN ('commerce_mode', 'commerce_reopens_at', 'commerce_cart_locked', 'commerce_allow_testers', 'commerce_banner_title', 'commerce_banner_message', 'commerce_banner_button_label', 'commerce_email_capture_enabled', 'commerce_success_message')");
    }

    private function upsertSetting(string $key, string $label, string $valueSql, string $help, string $fieldType): void
    {
        $this->addSql(sprintf(
            "INSERT INTO hodina_setting (setting_key, label, value, help, field_type, updated_at) VALUES ('%s', '%s', %s, '%s', '%s', NOW()) ON DUPLICATE KEY UPDATE label = VALUES(label), value = VALUES(value), help = VALUES(help), field_type = VALUES(field_type), updated_at = NOW()",
            $this->escape($key),
            $this->escape($label),
            $valueSql,
            $this->escape($help),
            $this->escape($fieldType)
        ));
    }

    private function escape(string $value): string
    {
        return str_replace("'", "''", $value);
    }
}
