<?php

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260622193000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5N-B - plafonne la rémunération livreur globale et par livreur';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer ADD courier_payout_cap NUMERIC(10, 2) DEFAULT NULL');
        $this->addSql(<<<'SQL'
INSERT INTO hodina_setting (setting_key, label, value, help, field_type, updated_at)
SELECT 'global_delivery_courier_payout_cap', 'Plafond rémunération livreur par commande', '20', 'Montant maximum versé à un livreur pour une commande. Un plafond spécifique peut être défini sur la fiche utilisateur livreur. Valeur vide ou 0 = pas de plafond.', 'text', CURRENT_TIMESTAMP
WHERE NOT EXISTS (SELECT 1 FROM hodina_setting WHERE setting_key = 'global_delivery_courier_payout_cap')
SQL);
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key = 'global_delivery_courier_payout_cap'");
        $this->addSql('ALTER TABLE customer DROP courier_payout_cap');
    }
}
