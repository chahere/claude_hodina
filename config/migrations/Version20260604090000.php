<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260604090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les dates métier du workflow commande J4.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order ADD confirmed_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD preparing_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD ready_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD delivered_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', ADD canceled_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\'');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE customer_order DROP confirmed_at, DROP preparing_at, DROP ready_at, DROP delivered_at, DROP canceled_at');
    }
}
