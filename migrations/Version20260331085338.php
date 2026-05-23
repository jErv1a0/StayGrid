<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260331085338 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE booking ADD booking_type VARCHAR(20) DEFAULT \'daily\' NOT NULL, ADD number_of_hours SMALLINT DEFAULT NULL');
        $this->addSql('ALTER TABLE roomlisting ADD price_per_hour NUMERIC(10, 2) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE roomlisting DROP price_per_hour');
        $this->addSql('ALTER TABLE booking DROP booking_type, DROP number_of_hours');
    }
}
