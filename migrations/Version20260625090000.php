<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260625090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5Q-C-2: seed configurable email branding settings.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'email_branding_subject_prefix', 'Préfixe objet e-mail', '', 'Préfixe ajouté automatiquement au début de tous les objets d’e-mails. Exemple recette : [Recette]. Laisser vide en production si aucun préfixe n’est souhaité.', 'text', 'email_branding', 'Branding e-mail', 10, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'email_branding_subject_prefix')");
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'email_branding_opening_formula', 'Formule début e-mail', 'Bonjour', 'Formule utilisée au début des e-mails avant le nom du destinataire. Exemple : Bonjour, Gégé, Salam.', 'text', 'email_branding', 'Branding e-mail', 20, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'email_branding_opening_formula')");
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'email_branding_closing_formula', 'Formule fin e-mail', 'Merci,', 'Formule utilisée avant la signature. Exemple : Merci, ou À très vite. La virgule finale est ajoutée automatiquement si elle manque.', 'text', 'email_branding', 'Branding e-mail', 30, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'email_branding_closing_formula')");
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'email_branding_signature', 'Signature e-mail', 'L’équipe Hodina', 'Signature affichée en fin d’e-mail. Exemple recette : L’équipe Hodina — Recette.', 'textarea', 'email_branding', 'Branding e-mail', 40, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'email_branding_signature')");

        $this->addSql("UPDATE hodina_setting SET group_key = 'email_branding', group_label = 'Branding e-mail', sort_order = 10, field_type = 'text', is_editable = 1, is_sensitive = 0 WHERE setting_key = 'email_branding_subject_prefix'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'email_branding', group_label = 'Branding e-mail', sort_order = 20, field_type = 'text', is_editable = 1, is_sensitive = 0 WHERE setting_key = 'email_branding_opening_formula'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'email_branding', group_label = 'Branding e-mail', sort_order = 30, field_type = 'text', is_editable = 1, is_sensitive = 0 WHERE setting_key = 'email_branding_closing_formula'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'email_branding', group_label = 'Branding e-mail', sort_order = 40, field_type = 'textarea', is_editable = 1, is_sensitive = 0 WHERE setting_key = 'email_branding_signature'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key IN ('email_branding_subject_prefix', 'email_branding_opening_formula', 'email_branding_closing_formula', 'email_branding_signature')");
    }
}
