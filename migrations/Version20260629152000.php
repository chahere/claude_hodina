<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629152000 extends AbstractMigration
{
    /** @var array<string, array{label: string, description: string, weekdays: list<int>|null, active: int}> */
    private const DELIVERY_SCHEDULES = [
        'PT_LOCAL' => [
            'label' => 'Petite-Terre',
            'description' => 'Passages Hodina le lundi et le jeudi.',
            'weekdays' => [1, 4],
            'active' => 1,
        ],
        'MAMOUDZOU_LOCAL' => [
            'label' => 'Mamoudzou',
            'description' => 'Passages Hodina le mercredi et le samedi.',
            'weekdays' => [3, 6],
            'active' => 1,
        ],
        'SUD_LOCAL' => [
            'label' => 'Grande-Terre Sud',
            'description' => 'Passages Hodina le mercredi et le samedi.',
            'weekdays' => [3, 6],
            'active' => 1,
        ],
        'NORD_LOCAL' => [
            'label' => 'Grande-Terre Nord',
            'description' => 'Passages Hodina le mardi et le vendredi.',
            'weekdays' => [2, 5],
            'active' => 1,
        ],
        'CENTRE_LOCAL' => [
            'label' => 'Grande-Terre Centre',
            'description' => 'Passages Hodina le mardi et le vendredi.',
            'weekdays' => [2, 5],
            'active' => 1,
        ],
        'GT_LOCAL' => [
            'label' => 'Grande-Terre fallback',
            'description' => 'Fallback technique historique. Les communes Grande-Terre doivent utiliser Mamoudzou, Nord, Centre ou Sud.',
            'weekdays' => null,
            'active' => 0,
        ],
    ];

    public function getDescription(): string
    {
        return 'J5X-B add configurable delivery schedules on DeliveryPricingZone';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('delivery_pricing_zone')) {
            $this->write('J5X-B skipped: delivery_pricing_zone table is missing.');
            return;
        }

        $this->addColumnIfMissing('delivery_pricing_zone', 'public_label', 'VARCHAR(120) DEFAULT NULL');
        $this->addColumnIfMissing('delivery_pricing_zone', 'public_description', 'LONGTEXT DEFAULT NULL');
        $this->addColumnIfMissing('delivery_pricing_zone', 'delivery_weekdays', 'JSON DEFAULT NULL');
        $this->addColumnIfMissing('delivery_pricing_zone', 'cutoff_time', 'TIME DEFAULT NULL');
        $this->addColumnIfMissing('delivery_pricing_zone', 'cutoff_days_before', 'INT DEFAULT 1 NOT NULL');
        $this->addColumnIfMissing('delivery_pricing_zone', 'is_delivery_schedule_active', 'TINYINT(1) DEFAULT 1 NOT NULL');

        foreach (self::DELIVERY_SCHEDULES as $code => $schedule) {
            $affectedRows = $this->connection->executeStatement(<<<'SQL'
UPDATE delivery_pricing_zone
SET
    public_label = ?,
    public_description = ?,
    delivery_weekdays = ?,
    cutoff_time = '10:00:00',
    cutoff_days_before = 1,
    is_delivery_schedule_active = ?,
    internal_note = CASE
        WHEN internal_note LIKE '%J5X-B%' THEN internal_note
        WHEN internal_note IS NULL OR internal_note = '' THEN ?
        ELSE CONCAT(internal_note, CHAR(10), ?)
    END,
    updated_at = NOW()
WHERE code = ?
SQL, [
                $schedule['label'],
                $schedule['description'],
                $schedule['weekdays'] !== null ? json_encode($schedule['weekdays'], JSON_THROW_ON_ERROR) : null,
                $schedule['active'],
                'J5X-B : calendrier de livraison paramétrable par secteur, cutoff 10h J-1.',
                'J5X-B : calendrier de livraison paramétrable par secteur, cutoff 10h J-1.',
                $code,
            ]);

            if ($affectedRows === 0) {
                $this->write(sprintf('J5X-B warning: delivery pricing zone %s was not found.', $code));
            }
        }
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('delivery_pricing_zone')) {
            return;
        }

        $this->dropColumnIfExists('delivery_pricing_zone', 'is_delivery_schedule_active');
        $this->dropColumnIfExists('delivery_pricing_zone', 'cutoff_days_before');
        $this->dropColumnIfExists('delivery_pricing_zone', 'cutoff_time');
        $this->dropColumnIfExists('delivery_pricing_zone', 'delivery_weekdays');
        $this->dropColumnIfExists('delivery_pricing_zone', 'public_description');
        $this->dropColumnIfExists('delivery_pricing_zone', 'public_label');
    }

    private function addColumnIfMissing(string $tableName, string $columnName, string $definition): void
    {
        if ($this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->connection->executeStatement(sprintf('ALTER TABLE %s ADD %s %s', $tableName, $columnName, $definition));
    }

    private function dropColumnIfExists(string $tableName, string $columnName): void
    {
        if (!$this->columnExists($tableName, $columnName)) {
            return;
        }

        $this->connection->executeStatement(sprintf('ALTER TABLE %s DROP %s', $tableName, $columnName));
    }

    private function tableExists(string $tableName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName],
        );

        return (int) $result > 0;
    }

    private function columnExists(string $tableName, string $columnName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, $columnName],
        );

        return (int) $result > 0;
    }
}
