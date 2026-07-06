<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613110000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5I/J5J: abonnés ouverture et réglages génériques du mode commerce';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE launch_subscriber (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, source VARCHAR(80) NOT NULL, ip_address VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(255) DEFAULT NULL, created_at DATETIME NOT NULL, UNIQUE INDEX UNIQ_LAUNCH_SUBSCRIBER_EMAIL (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $settings = [
            ['commerce_mode', 'Mode commerce', 'preopening', 'Mode général du portail : open = commandes ouvertes, preopening = préouverture, maintenance = mise à jour production, closed = fermeture manuelle.', 'choice'],
            ['commerce_reopens_at', 'Date de réactivation des commandes', '', 'Date et heure à laquelle les commandes publiques seront réactivées. Exemple : 2026-06-30 18:00.', 'text'],
            ['commerce_cart_locked', 'Bloquer panier et commandes publiques', '1', 'Bloque côté serveur l’ajout au panier et la validation de commande pour le public.', 'boolean'],
            ['commerce_allow_testers', 'Autoriser les testeurs pendant le blocage', '1', 'Autorise les comptes avec le rôle ROLE_COMMERCE_TESTER, ainsi que les administrateurs, à utiliser le portail normalement malgré le blocage public.', 'boolean'],
            ['commerce_banner_title', 'Titre bannière commerce', 'Votre marché en ligne de produits locaux arrive bientôt', 'Titre affiché dans la bannière de préouverture ou de maintenance commerciale.', 'text'],
            ['commerce_banner_message', 'Message bannière commerce', 'Le catalogue est accessible, mais la prise de commande sera possible à la date officielle. Laisse nous ton e-mail pour être informé de l’ouverture.', 'Texte affiché sous le compte à rebours ou dans la bannière de blocage commercial.', 'textarea'],
            ['commerce_banner_button_label', 'Bouton capture e-mail commerce', 'Me faire signe à l’ouverture', 'Libellé du bouton de capture e-mail affiché dans la bannière.', 'text'],
            ['commerce_email_capture_enabled', 'Capture e-mail commerce active', '1', 'Active ou désactive le formulaire e-mail dans la bannière.', 'boolean'],
            ['commerce_success_message', 'Message succès inscription commerce', 'Merci, ton e-mail est bien enregistré. On te préviendra dès que les commandes seront disponibles.', 'Message affiché après capture e-mail réussie.', 'textarea'],
        ];

        foreach ($settings as [$key, $label, $value, $help, $fieldType]) {
            $this->addSql(
                'INSERT INTO hodina_setting (setting_key, label, value, help, field_type, updated_at) SELECT ?, ?, ?, ?, ?, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = ?)',
                [$key, $label, $value, $help, $fieldType, $key]
            );
        }
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE launch_subscriber');
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key IN ('commerce_mode', 'commerce_reopens_at', 'commerce_cart_locked', 'commerce_allow_testers', 'commerce_banner_title', 'commerce_banner_message', 'commerce_banner_button_label', 'commerce_email_capture_enabled', 'commerce_success_message')");
    }
}
