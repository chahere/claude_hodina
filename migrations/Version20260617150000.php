<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617150000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5G-B4 - Add global customer delivery fee cap setting';
    }

    public function up(Schema $schema): void
    {
        $this->insertSettingIfMissing(
            'global_delivery_customer_fee_cap',
            'Plafond frais de livraison client (€)',
            '40.00',
            'Montant maximum facturé au client pour les frais de livraison. Le calcul reste : forfait local de la commune livrée + liaisons LAND + liaison BARGE éventuelle, puis Hodina applique ce plafond si le total dépasse la valeur renseignée. Mettre 0 ou vider la valeur pour désactiver le plafond.',
            'text'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key = 'global_delivery_customer_fee_cap'");
    }

    private function insertSettingIfMissing(string $key, string $label, string $value, string $help, string $fieldType): void
    {
        $exists = (int) $this->connection->fetchOne(
            'SELECT COUNT(*) FROM hodina_setting WHERE setting_key = ?',
            [$key]
        );

        if ($exists > 0) {
            return;
        }

        $this->connection->insert('hodina_setting', [
            'setting_key' => $key,
            'label' => $label,
            'value' => $value,
            'help' => $help,
            'field_type' => $fieldType,
            'updated_at' => (new \DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }
}
