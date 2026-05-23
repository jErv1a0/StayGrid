<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520021000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Migrate non-null room start/end dates into booking rows and drop room columns';
    }

    public function up(Schema $schema): void
    {
        // Insert bookings for rooms that had explicit start/end dates
        $this->addSql("
            INSERT INTO booking (room_id, start_date, end_date, status, booking_type)
            SELECT id, start_date, end_date, 'pending', 'daily'
            FROM roomlisting
            WHERE start_date IS NOT NULL OR end_date IS NOT NULL
        ");

        // Remove room-level date columns
        $this->addSql('ALTER TABLE roomlisting DROP COLUMN start_date, DROP COLUMN end_date');
    }

    public function down(Schema $schema): void
    {
        // Recreate the columns (nullable)
        $this->addSql('ALTER TABLE roomlisting ADD start_date DATETIME DEFAULT NULL, ADD end_date DATETIME DEFAULT NULL');

        // Copy back an aggregated booking range per room (if any)
        $this->addSql("
            UPDATE roomlisting r
            LEFT JOIN (
                SELECT room_id, MIN(start_date) AS start_date, MAX(end_date) AS end_date
                FROM booking
                GROUP BY room_id
            ) b ON r.id = b.room_id
            SET r.start_date = b.start_date, r.end_date = b.end_date
        ");

        // Optionally remove the synthetic bookings created by the up() migration
        $this->addSql("DELETE FROM booking WHERE start_date IS NOT NULL OR end_date IS NOT NULL");
    }
}
