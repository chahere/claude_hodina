<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260613094055 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'No-op: ancienne migration corrective J5I neutralisée car elle dépendait de launch_subscriber avant sa création.';
    }

    public function up(Schema $schema): void
    {
        // Intentionally empty.
        // The created_at column is now created correctly by Version20260613110000
        // and old environments are normalized by Version20260613130000.
    }

    public function down(Schema $schema): void
    {
        // Intentionally empty.
    }
}
