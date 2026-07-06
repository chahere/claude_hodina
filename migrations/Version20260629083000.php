<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629083000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5W-A seed Grande-Terre local delivery pricing zones by sector while reusing PT_LOCAL for Petite-Terre';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('delivery_commune') || !$this->tableExists('delivery_pricing_zone')) {
            $this->write('J5W-A skipped: required logistics tables are missing.');
            return;
        }

        $this->seedGrandeTerreSectorPricingZones();
        $this->assignCommunesToSectorPricingZones();
        $this->removeObsoletePetiteTerreDuplicateZoneIfSafe();
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('delivery_commune') || !$this->tableExists('delivery_pricing_zone')) {
            return;
        }

        // Rollback minimal et non destructif : on rattache les communes à leurs
        // zones tarifaires historiques par territoire. Les zones J5W-A créées
        // restent en base afin de ne pas supprimer une donnée qui aurait pu être
        // ajustée manuellement dans EasyAdmin après migration.
        foreach (['GT' => 'GT_LOCAL', 'PT' => 'PT_LOCAL'] as $territory => $legacyCode) {
            $this->connection->executeStatement(<<<'SQL'
UPDATE delivery_commune c
INNER JOIN delivery_pricing_zone z ON z.code = ?
SET
    c.local_pricing_zone_id = z.id,
    c.updated_at = NOW()
WHERE c.territory = ?
SQL, [$legacyCode, $territory]);
        }
    }

    private function seedGrandeTerreSectorPricingZones(): void
    {
        foreach ($this->sectorPricingZones() as $zone) {
            $source = $this->connection->fetchAssociative(
                'SELECT customer_delivery_fee, courier_payout FROM delivery_pricing_zone WHERE code = ?',
                [$zone['sourceCode']],
            );

            if ($source === false) {
                $this->write(sprintf(
                    'J5W-A skipped pricing zone %s: source zone %s missing.',
                    $zone['code'],
                    $zone['sourceCode'],
                ));
                continue;
            }

            $this->connection->executeStatement(<<<'SQL'
INSERT INTO delivery_pricing_zone
    (name, code, customer_delivery_fee, courier_payout, is_active, internal_note, created_at, updated_at)
VALUES
    (?, ?, ?, ?, 1, ?, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_active = VALUES(is_active),
    internal_note = CASE
        WHEN internal_note LIKE '%J5W-A%' THEN internal_note
        WHEN internal_note IS NULL OR internal_note = '' THEN VALUES(internal_note)
        ELSE CONCAT(internal_note, '\n', VALUES(internal_note))
    END,
    updated_at = NOW()
SQL, [
                $zone['name'],
                $zone['code'],
                $source['customer_delivery_fee'],
                $source['courier_payout'],
                $zone['note'],
            ]);
        }
    }

    private function assignCommunesToSectorPricingZones(): void
    {
        foreach ($this->communePricingZoneMap() as $slug => $pricingZoneCode) {
            $affectedRows = $this->connection->executeStatement(<<<'SQL'
UPDATE delivery_commune c
INNER JOIN delivery_pricing_zone z ON z.code = ?
SET
    c.local_pricing_zone_id = z.id,
    c.internal_note = CASE
        WHEN c.internal_note LIKE '%J5W-A%' THEN c.internal_note
        WHEN c.internal_note IS NULL OR c.internal_note = '' THEN ?
        ELSE CONCAT(c.internal_note, '\n', ?)
    END,
    c.updated_at = NOW()
WHERE c.slug = ?
SQL, [
                $pricingZoneCode,
                sprintf('J5W-A : rattachement à la zone tarifaire locale %s sans modifier le territoire PT/GT.', $pricingZoneCode),
                sprintf('J5W-A : rattachement à la zone tarifaire locale %s sans modifier le territoire PT/GT.', $pricingZoneCode),
                $slug,
            ]);

            if ($affectedRows === 0) {
                $this->write(sprintf(
                    'J5W-A warning: no DeliveryCommune found for slug %s while assigning %s.',
                    $slug,
                    $pricingZoneCode,
                ));
            }
        }
    }

    private function removeObsoletePetiteTerreDuplicateZoneIfSafe(): void
    {
        $duplicateZoneId = $this->connection->fetchOne(
            'SELECT id FROM delivery_pricing_zone WHERE code = ? LIMIT 1',
            ['PETITE_TERRE_LOCAL'],
        );

        if ($duplicateZoneId === false) {
            return;
        }

        $references = (int) $this->connection->fetchOne(<<<'SQL'
SELECT COUNT(*)
FROM delivery_commune
WHERE local_pricing_zone_id = ? OR barge_pricing_zone_id = ?
SQL, [$duplicateZoneId, $duplicateZoneId]);

        if ($references > 0) {
            $this->write('J5W-A warning: PETITE_TERRE_LOCAL still has DeliveryCommune references and was not deleted.');
            return;
        }

        $this->connection->executeStatement(
            'DELETE FROM delivery_pricing_zone WHERE code = ?',
            ['PETITE_TERRE_LOCAL'],
        );
    }

    /** @return list<array{name: string, code: string, sourceCode: string, note: string}> */
    private function sectorPricingZones(): array
    {
        return [
            [
                'name' => 'Mamoudzou local',
                'code' => 'MAMOUDZOU_LOCAL',
                'sourceCode' => 'GT_LOCAL',
                'note' => 'J5W-A : zone tarifaire locale Grande-Terre par secteur. Créée depuis GT_LOCAL pour ne pas changer les tarifs au déploiement.',
            ],
            [
                'name' => 'Nord local',
                'code' => 'NORD_LOCAL',
                'sourceCode' => 'GT_LOCAL',
                'note' => 'J5W-A : zone tarifaire locale Grande-Terre par secteur. Créée depuis GT_LOCAL pour ne pas changer les tarifs au déploiement.',
            ],
            [
                'name' => 'Centre local',
                'code' => 'CENTRE_LOCAL',
                'sourceCode' => 'GT_LOCAL',
                'note' => 'J5W-A : zone tarifaire locale Grande-Terre par secteur. Créée depuis GT_LOCAL pour ne pas changer les tarifs au déploiement.',
            ],
            [
                'name' => 'Sud local',
                'code' => 'SUD_LOCAL',
                'sourceCode' => 'GT_LOCAL',
                'note' => 'J5W-A : zone tarifaire locale Grande-Terre par secteur. Créée depuis GT_LOCAL pour ne pas changer les tarifs au déploiement.',
            ],
        ];
    }

    /** @return array<string, string> slug => delivery_pricing_zone.code */
    private function communePricingZoneMap(): array
    {
        return [
            'mamoudzou' => 'MAMOUDZOU_LOCAL',

            'acoua' => 'NORD_LOCAL',
            'bandraboua' => 'NORD_LOCAL',
            'koungou' => 'NORD_LOCAL',
            'm-tsangamouji' => 'NORD_LOCAL',
            'mtsamboro' => 'NORD_LOCAL',

            'chiconi' => 'CENTRE_LOCAL',
            'ouangani' => 'CENTRE_LOCAL',
            'sada' => 'CENTRE_LOCAL',
            'tsingoni' => 'CENTRE_LOCAL',

            'bandrele' => 'SUD_LOCAL',
            'boueni' => 'SUD_LOCAL',
            'chirongui' => 'SUD_LOCAL',
            'dembeni' => 'SUD_LOCAL',
            'kani-keli' => 'SUD_LOCAL',

            // Petite-Terre conserve la zone tarifaire historique existante.
            // On évite le doublon PETITE_TERRE_LOCAL / PT_LOCAL dans EasyAdmin.
            'dzaoudzi' => 'PT_LOCAL',
            'labattoir' => 'PT_LOCAL',
            'pamandzi' => 'PT_LOCAL',
        ];
    }

    private function tableExists(string $tableName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName],
        );

        return (int) $result > 0;
    }
}
