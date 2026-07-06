<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260629141000 extends AbstractMigration
{
    private const TARGET_FEES = [
        'PT_LOCAL' => '12.00',
        'MAMOUDZOU_LOCAL' => '12.00',
        'CENTRE_LOCAL' => '17.00',
        'SUD_LOCAL' => '21.00',
        'NORD_LOCAL' => '21.00',
        'GT_LOCAL' => '21.00',
    ];

    /** Baseline J5W-A avant décision tarifaire J5X-A. */
    private const J5W_A_BASELINE_FEES = [
        'PT_LOCAL' => '12.00',
        'MAMOUDZOU_LOCAL' => '15.00',
        'CENTRE_LOCAL' => '15.00',
        'SUD_LOCAL' => '15.00',
        'NORD_LOCAL' => '15.00',
        'GT_LOCAL' => '15.00',
    ];

    public function getDescription(): string
    {
        return 'J5X-A update customer delivery fees by local pricing sector without changing logistics formula';
    }

    public function up(Schema $schema): void
    {
        if (!$this->tableExists('delivery_pricing_zone')) {
            $this->write('J5X-A skipped: delivery_pricing_zone table is missing.');
            return;
        }

        $this->applyCustomerDeliveryFees(
            self::TARGET_FEES,
            'J5X-A : mise à jour des frais client par secteur tarifaire, sans modifier la rémunération livreur ni la formule logistique.',
        );
    }

    public function down(Schema $schema): void
    {
        if (!$this->tableExists('delivery_pricing_zone')) {
            return;
        }

        $this->applyCustomerDeliveryFees(
            self::J5W_A_BASELINE_FEES,
            'Rollback J5X-A : retour aux frais client de base J5W-A, sans modifier la rémunération livreur.',
        );
    }

    /** @param array<string, string> $feesByCode */
    private function applyCustomerDeliveryFees(array $feesByCode, string $note): void
    {
        foreach ($feesByCode as $code => $fee) {
            $affectedRows = $this->connection->executeStatement(<<<'SQL'
UPDATE delivery_pricing_zone
SET
    customer_delivery_fee = ?,
    internal_note = CASE
        WHEN internal_note LIKE ? THEN internal_note
        WHEN internal_note IS NULL OR internal_note = '' THEN ?
        ELSE CONCAT(internal_note, CHAR(10), ?)
    END,
    updated_at = NOW()
WHERE code = ?
SQL, [
                $fee,
                '%J5X-A%',
                $note,
                $note,
                $code,
            ]);

            if ($affectedRows === 0) {
                $this->write(sprintf('J5X-A warning: delivery pricing zone %s was not found.', $code));
            }
        }
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
