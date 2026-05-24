<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251019125200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    private function resolveRoomTable(): ?string
    {
        try {
            if ($this->connection->fetchOne("SHOW TABLES LIKE 'roomlisting'")) {
                return 'roomlisting';
            }

            if ($this->connection->fetchOne("SHOW TABLES LIKE 'room_listing'")) {
                return 'room_listing';
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    public function up(Schema $schema): void
    {
        $table = $this->resolveRoomTable();

        if (! $table) {
            return;
        }

        $this->addSql('ALTER TABLE ' . $table . ' ADD is_available TINYINT(1) DEFAULT 0 NOT NULL, CHANGE croom title VARCHAR(255) NOT NULL, CHANGE price price_per_night DOUBLE PRECISION NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $table = $this->resolveRoomTable();

        if (! $table) {
            return;
        }

        $this->addSql('ALTER TABLE ' . $table . ' DROP is_available, CHANGE title croom VARCHAR(255) NOT NULL, CHANGE price_per_night price DOUBLE PRECISION NOT NULL');
    }
}
