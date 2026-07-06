<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260624233000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5Q-C-1: structure Hodina settings into admin groups.';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        $this->addSql("ALTER TABLE hodina_setting ADD group_key VARCHAR(60) DEFAULT 'general' NOT NULL, ADD group_label VARCHAR(120) DEFAULT 'Général' NOT NULL, ADD sort_order INT DEFAULT 100 NOT NULL, ADD is_editable TINYINT(1) DEFAULT 1 NOT NULL, ADD is_sensitive TINYINT(1) DEFAULT 0 NOT NULL");
        $this->addSql('CREATE INDEX IDX_HODINA_SETTING_GROUP_SORT ON hodina_setting (group_key, sort_order)');

        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 10 WHERE setting_key = 'order_reference_prefix'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 20 WHERE setting_key = 'global_margin_rate'");

        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 100 WHERE setting_key = 'commerce_mode'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 110 WHERE setting_key = 'commerce_reopens_at'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 120 WHERE setting_key = 'commerce_cart_locked'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 130 WHERE setting_key = 'commerce_allow_testers'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 140 WHERE setting_key = 'commerce_banner_title'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 150 WHERE setting_key = 'commerce_banner_message'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 160 WHERE setting_key = 'commerce_banner_button_label'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 170 WHERE setting_key = 'commerce_email_capture_enabled'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'commerce', group_label = 'Commerce & commandes', sort_order = 180 WHERE setting_key = 'commerce_success_message'");

        $this->addSql("UPDATE hodina_setting SET group_key = 'logistics', group_label = 'Livraison & logistique', sort_order = 10 WHERE setting_key = 'delivered_communes'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'logistics', group_label = 'Livraison & logistique', sort_order = 100 WHERE setting_key = 'global_commune_crossing_customer_fee'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'logistics', group_label = 'Livraison & logistique', sort_order = 110 WHERE setting_key = 'global_commune_crossing_courier_payout'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'logistics', group_label = 'Livraison & logistique', sort_order = 120 WHERE setting_key = 'global_delivery_customer_fee_cap'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'logistics', group_label = 'Livraison & logistique', sort_order = 130 WHERE setting_key = 'global_delivery_courier_payout_cap'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'logistics', group_label = 'Livraison & logistique', sort_order = 140 WHERE setting_key = 'global_multi_seller_extra_customer_fee'");
        $this->addSql("UPDATE hodina_setting SET group_key = 'logistics', group_label = 'Livraison & logistique', sort_order = 150 WHERE setting_key = 'global_multi_seller_extra_customer_fee_cap'");

        $this->addSql("UPDATE hodina_setting SET group_key = 'general', group_label = 'Général', sort_order = 20 WHERE setting_key IN ('local_timezone', 'app.local_timezone', 'default_timezone', 'customer_default_timezone')");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_HODINA_SETTING_GROUP_SORT ON hodina_setting');
        $this->addSql('ALTER TABLE hodina_setting DROP group_key, DROP group_label, DROP sort_order, DROP is_editable, DROP is_sensitive');
    }
}
