<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260617143000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5G-B4 - Add global commune crossing cost settings';
    }

    public function up(Schema $schema): void
    {
        $this->insertSettingIfMissing(
            'global_commune_crossing_customer_fee',
            'Coût global traversée de commune client (€)',
            '0.00',
            'Supplément client appliqué par défaut à chaque liaison terrestre LAND si aucun supplément spécifique n’est renseigné. En cas de barge, le total reste : forfait local de la commune livrée + coût des liaisons LAND avant/après barge + coût fixe de la liaison BARGE.',
            'text'
        );

        $this->insertSettingIfMissing(
            'global_commune_crossing_courier_payout',
            'Supplément global livreur traversée de commune (€)',
            '0.00',
            'Supplément livreur appliqué par défaut à chaque liaison terrestre LAND si aucun supplément spécifique n’est renseigné. En cas de barge, le total livreur additionne aussi les liaisons LAND avant/après barge et le supplément BARGE spécifique.',
            'text'
        );
    }

    public function down(Schema $schema): void
    {
        $this->addSql("DELETE FROM hodina_setting WHERE setting_key IN ('global_commune_crossing_customer_fee', 'global_commune_crossing_courier_payout')");
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
