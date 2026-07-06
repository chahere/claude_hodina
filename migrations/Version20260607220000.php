<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260607220000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'J5G-B3 seed initial communes and logistics connections from validated Hodina source';
    }

    public function isTransactional(): bool
    {
        return false;
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('delivery_commune') || !$this->tableExists('delivery_pricing_zone')) {
            $this->write('J5G-B3 skipped: required logistics tables are missing.');
            return;
        }

        $this->seedPricingZones();
        $this->seedCommunes();

        if ($this->tableExists('delivery_commune_connection')) {
            $this->seedConnections();
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('delivery_commune_connection')) {
            $this->connection->executeStatement(
                "DELETE FROM delivery_commune_connection WHERE internal_note LIKE 'Seed J5G-B3%'"
            );
        }

        // Les communes ne sont volontairement pas supprimées en down().
        // Elles peuvent avoir été modifiées depuis EasyAdmin après le seed.
    }

    private function seedPricingZones(): void
    {
        $zones = [
            ['Petite-Terre local', 'PT_LOCAL', 'Zone locale Petite-Terre créée/confirmée pour le seed J5G-B3.'],
            ['Grande-Terre local', 'GT_LOCAL', 'Zone locale Grande-Terre créée/confirmée pour le seed J5G-B3.'],
        ];

        foreach ($zones as [$name, $code, $note]) {
            $this->connection->executeStatement(<<<'SQL'
INSERT INTO delivery_pricing_zone
    (name, code, customer_delivery_fee, courier_payout, is_active, internal_note, created_at, updated_at)
VALUES
    (?, ?, 6.00, 5.00, 1, ?, NOW(), NOW())
ON DUPLICATE KEY UPDATE
    name = VALUES(name),
    is_active = VALUES(is_active),
    internal_note = VALUES(internal_note),
    updated_at = NOW()
SQL, [$name, $code, $note]);
        }
    }

    private function seedCommunes(): void
    {
        foreach ($this->communes() as $commune) {
            $localZoneId = $this->pricingZoneId($commune['territory'] === 'PT' ? 'PT_LOCAL' : 'GT_LOCAL');
            $bargeZoneId = $this->pricingZoneId($commune['territory'] === 'PT' ? 'GT_LOCAL' : 'PT_LOCAL');

            if ($localZoneId === null || $bargeZoneId === null) {
                $this->write(sprintf('J5G-B3 skipped commune %s: missing pricing zone.', $commune['name']));
                continue;
            }

            $note = trim(sprintf(
                'Seed J5G-B3 depuis source validée communes/voisinage Hodina. Type: %s. %s',
                $commune['type'],
                $commune['note']
            ));

            $this->connection->executeStatement(<<<'SQL'
INSERT INTO delivery_commune
    (name, territory, local_pricing_zone_id, barge_pricing_zone_id, is_active, internal_note, created_at, updated_at, slug, postal_code, insee_code, parent_insee_code, is_logistics_point)
VALUES
    (?, ?, ?, ?, 1, ?, NOW(), NOW(), ?, ?, ?, ?, ?)
ON DUPLICATE KEY UPDATE
    territory = VALUES(territory),
    local_pricing_zone_id = VALUES(local_pricing_zone_id),
    barge_pricing_zone_id = VALUES(barge_pricing_zone_id),
    is_active = VALUES(is_active),
    internal_note = VALUES(internal_note),
    slug = VALUES(slug),
    postal_code = VALUES(postal_code),
    insee_code = VALUES(insee_code),
    parent_insee_code = VALUES(parent_insee_code),
    is_logistics_point = VALUES(is_logistics_point),
    updated_at = NOW()
SQL, [
                $commune['name'],
                $commune['territory'],
                $localZoneId,
                $bargeZoneId,
                $note,
                $commune['slug'],
                $commune['postalCode'],
                $commune['inseeCode'],
                $commune['parentInseeCode'],
                $commune['isLogisticsPoint'] ? 1 : 0,
            ]);
        }
    }

    private function seedConnections(): void
    {
        foreach ($this->connections() as $connection) {
            $note = trim(sprintf(
                'Seed J5G-B3 depuis source validée communes/voisinage Hodina. %s',
                $connection['note']
            ));

            $this->connection->executeStatement(<<<'SQL'
INSERT INTO delivery_commune_connection
    (from_commune_id, to_commune_id, link_type, is_bidirectional, hop_count, customer_extra_fee, courier_extra_payout, is_active, internal_note, created_at, updated_at)
SELECT
    from_c.id,
    to_c.id,
    ?,
    ?,
    ?,
    NULL,
    NULL,
    1,
    ?,
    NOW(),
    NOW()
FROM delivery_commune from_c, delivery_commune to_c
WHERE from_c.slug = ? AND to_c.slug = ?
ON DUPLICATE KEY UPDATE
    is_bidirectional = VALUES(is_bidirectional),
    hop_count = VALUES(hop_count),
    is_active = VALUES(is_active),
    internal_note = VALUES(internal_note),
    updated_at = NOW()
SQL, [
                $connection['linkType'],
                $connection['bidirectional'] ? 1 : 0,
                $connection['hopCount'],
                $note,
                $connection['from'],
                $connection['to'],
            ]);
        }
    }

    private function pricingZoneId(string $code): ?int
    {
        $id = $this->connection->fetchOne('SELECT id FROM delivery_pricing_zone WHERE code = ?', [$code]);

        return $id !== false && $id !== null ? (int) $id : null;
    }

    /** @return array<int, array<string, mixed>> */
    private function communes(): array
    {
        return [
            ['slug' => 'acoua', 'name' => 'Acoua', 'type' => 'COMMUNE', 'inseeCode' => '97601', 'parentInseeCode' => null, 'postalCode' => '97630', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'bandraboua', 'name' => 'Bandraboua', 'type' => 'COMMUNE', 'inseeCode' => '97602', 'parentInseeCode' => null, 'postalCode' => '97650', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'bandrele', 'name' => 'Bandrélé', 'type' => 'COMMUNE', 'inseeCode' => '97603', 'parentInseeCode' => null, 'postalCode' => '97660', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE. Orthographe normalisée avec accent.'],
            ['slug' => 'boueni', 'name' => 'Bouéni', 'type' => 'COMMUNE', 'inseeCode' => '97604', 'parentInseeCode' => null, 'postalCode' => '97620', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'chiconi', 'name' => 'Chiconi', 'type' => 'COMMUNE', 'inseeCode' => '97605', 'parentInseeCode' => null, 'postalCode' => '97670', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'chirongui', 'name' => 'Chirongui', 'type' => 'COMMUNE', 'inseeCode' => '97606', 'parentInseeCode' => null, 'postalCode' => '97620', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'dembeni', 'name' => 'Dembéni', 'type' => 'COMMUNE', 'inseeCode' => '97607', 'parentInseeCode' => null, 'postalCode' => '97660', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'dzaoudzi', 'name' => 'Dzaoudzi', 'type' => 'COMMUNE', 'inseeCode' => '97608', 'parentInseeCode' => null, 'postalCode' => '97615', 'territory' => 'PT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE. Point d’entrée barge Petite-Terre.'],
            ['slug' => 'labattoir', 'name' => 'Labattoir', 'type' => 'VILLAGE_LOGISTIQUE', 'inseeCode' => null, 'parentInseeCode' => '97608', 'postalCode' => '97615', 'territory' => 'PT', 'isLogisticsPoint' => true, 'note' => 'Point logistique Hodina rattaché administrativement à Dzaoudzi. Ne pas le traiter comme commune INSEE autonome.'],
            ['slug' => 'kani-keli', 'name' => 'Kani-Kéli', 'type' => 'COMMUNE', 'inseeCode' => '97609', 'parentInseeCode' => null, 'postalCode' => '97625', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'koungou', 'name' => 'Koungou', 'type' => 'COMMUNE', 'inseeCode' => '97610', 'parentInseeCode' => null, 'postalCode' => '97600', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'mamoudzou', 'name' => 'Mamoudzou', 'type' => 'COMMUNE', 'inseeCode' => '97611', 'parentInseeCode' => null, 'postalCode' => '97600', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Chef-lieu du département. Point d’entrée barge Grande-Terre.'],
            ['slug' => 'mtsamboro', 'name' => 'Mtsamboro', 'type' => 'COMMUNE', 'inseeCode' => '97612', 'parentInseeCode' => null, 'postalCode' => '97630', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'm-tsangamouji', 'name' => "M'Tsangamouji", 'type' => 'COMMUNE', 'inseeCode' => '97613', 'parentInseeCode' => null, 'postalCode' => '97650', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE. Apostrophe normalisée dans le slug.'],
            ['slug' => 'ouangani', 'name' => 'Ouangani', 'type' => 'COMMUNE', 'inseeCode' => '97614', 'parentInseeCode' => null, 'postalCode' => '97670', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'pamandzi', 'name' => 'Pamandzi', 'type' => 'COMMUNE', 'inseeCode' => '97615', 'parentInseeCode' => null, 'postalCode' => '97615', 'territory' => 'PT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE Petite-Terre.'],
            ['slug' => 'sada', 'name' => 'Sada', 'type' => 'COMMUNE', 'inseeCode' => '97616', 'parentInseeCode' => null, 'postalCode' => '97640', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
            ['slug' => 'tsingoni', 'name' => 'Tsingoni', 'type' => 'COMMUNE', 'inseeCode' => '97617', 'parentInseeCode' => null, 'postalCode' => '97680', 'territory' => 'GT', 'isLogisticsPoint' => true, 'note' => 'Commune administrative INSEE.'],
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function connections(): array
    {
        return [
            ['from' => 'dzaoudzi', 'to' => 'pamandzi', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Voisinage Petite-Terre.'],
            ['from' => 'dzaoudzi', 'to' => 'labattoir', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Voisinage Petite-Terre / localité.'],
            ['from' => 'labattoir', 'to' => 'pamandzi', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Voisinage Petite-Terre.'],
            ['from' => 'dzaoudzi', 'to' => 'mamoudzou', 'linkType' => 'BARGE', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Lien maritime barge Petite-Terre ↔ Grande-Terre.'],
            ['from' => 'mamoudzou', 'to' => 'koungou', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'mamoudzou', 'to' => 'dembeni', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'mamoudzou', 'to' => 'ouangani', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'koungou', 'to' => 'bandraboua', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'bandraboua', 'to' => 'mtsamboro', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'mtsamboro', 'to' => 'acoua', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'acoua', 'to' => 'm-tsangamouji', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'm-tsangamouji', 'to' => 'tsingoni', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'tsingoni', 'to' => 'chiconi', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'tsingoni', 'to' => 'ouangani', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'chiconi', 'to' => 'sada', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'sada', 'to' => 'ouangani', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'ouangani', 'to' => 'dembeni', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'ouangani', 'to' => 'chirongui', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'dembeni', 'to' => 'bandrele', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'bandrele', 'to' => 'chirongui', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'chirongui', 'to' => 'boueni', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'chirongui', 'to' => 'kani-keli', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
            ['from' => 'boueni', 'to' => 'kani-keli', 'linkType' => 'LAND', 'bidirectional' => true, 'hopCount' => 1, 'note' => 'Frontière Grande-Terre.'],
        ];
    }

    private function tableExists(string $tableName): bool
    {
        $result = $this->connection->fetchOne(
            'SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?',
            [$tableName]
        );

        return (int) $result > 0;
    }
}
