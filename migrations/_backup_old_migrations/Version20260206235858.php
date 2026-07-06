<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260206235858 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
		  // 1) Sécurise les slugs vides (au cas où)
		$this->addSql("UPDATE product SET slug = CONCAT('product-', id) WHERE slug IS NULL OR slug = ''");

		// 2) Crée l'index unique seulement si pas déjà présent
		// MySQL: on utilise un nom d'index stable (celui que Doctrine a généré)
		// Si ton index existe déjà, MySQL plantera -> on le gère dans l'étape 3 si besoin
		$this->addSql('CREATE UNIQUE INDEX UNIQ_D34A04AD989D9B62 ON product (slug)');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D34A04AD989D9B62 ON product');

    }
}
