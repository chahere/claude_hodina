<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624234500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5Q-C-1: seed courier payout settings in payments group.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'courier_payouts_enabled', 'Paiements livreurs activés', '1', 'Active ou suspend tout le module de rémunération livreur. Si désactivé, les générations manuelles et cron ne créent aucun brouillon.', 'boolean', 'payments', 'Paiements', 10, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'courier_payouts_enabled')");
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'courier_payout_cron_enabled', 'Génération cron paiements livreurs', '1', 'Autorise la commande planifiée à préparer automatiquement les brouillons de rémunération livreur aux dates prévues. Ne marque jamais payé.', 'boolean', 'payments', 'Paiements', 20, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'courier_payout_cron_enabled')");
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'courier_payout_admin_recap_enabled', 'Récap admin paiements livreurs', '1', 'Autorise l’envoi du récapitulatif e-mail aux admins après une génération réelle des brouillons de rémunération livreur.', 'boolean', 'payments', 'Paiements', 30, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'courier_payout_admin_recap_enabled')");
        $this->addSql("INSERT INTO hodina_setting (setting_key, label, value, help, field_type, group_key, group_label, sort_order, is_editable, is_sensitive, updated_at) SELECT 'courier_payout_frequency', 'Fréquence paiements livreurs', 'semi_monthly', 'Fréquence de préparation des brouillons. Pour le pilote, la valeur active est semi_monthly : du 1 au 15 puis du 16 à fin de mois.', 'choice', 'payments', 'Paiements', 40, 1, 0, NOW() WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'courier_payout_frequency')");

        $this->addSql("UPDATE hodina_setting SET group_key = 'payments', group_label = 'Paiements', sort_order = 10, field_type = 'boolean', is_editable = 1, is_sensitive = 0 WHERE setting_key = 'courier_payouts_enabled'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'payments', group_label = 'Paiements', sort_order = 20, field_type = 'boolean', is_editable = 1, is_sensitive = 0 WHERE setting_key = 'courier_payout_cron_enabled'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'payments', group_label = 'Paiements', sort_order = 30, field_type = 'boolean', is_editable = 1, is_sensitive = 0 WHERE setting_key = 'courier_payout_admin_recap_enabled'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'payments', group_label = 'Paiements', sort_order = 40, field_type = 'choice', is_editable = 1, is_sensitive = 0 WHERE setting_key = 'courier_payout_frequency'");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key IN ('courier_payouts_enabled', 'courier_payout_cron_enabled', 'courier_payout_admin_recap_enabled', 'courier_payout_frequency')");
    }
}
