<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260520014348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking CHANGE status status VARCHAR(50) DEFAULT \'pending\' NOT NULL');
        // Indexes already exist in the database; only altering booking.status here.
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        // Index drops removed because indexes are already present in production DB.
        $this->addSql('ALTER TABLE booking CHANGE status status VARCHAR(50) NOT NULL');
    }
}
