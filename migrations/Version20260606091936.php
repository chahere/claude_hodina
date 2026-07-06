<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260606091936 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op migration kept for J5C compatibility. The index alignment is handled by a later migration.';
    }

    public function up(Schema $schema): void
    {
        // Intentionally left blank.
        // This migration was originally generated before the delivery fields migration,
        // which made it fail on preproduction because the index did not exist yet.
    }

    public function down(Schema $schema): void
    {
        // Intentionally left blank.
    }
}