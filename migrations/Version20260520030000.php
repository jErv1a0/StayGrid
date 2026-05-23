<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260520030000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add CHECK constraints for booking.status and booking.booking_type';
    }

    public function up(Schema $schema): void
    {
        // Allowed statuses and booking types
        $this->addSql("ALTER TABLE booking ADD CONSTRAINT chk_booking_status CHECK (status IN ('pending','confirmed','cancelled','completed'))");
        $this->addSql("ALTER TABLE booking ADD CONSTRAINT chk_booking_type CHECK (booking_type IN ('daily','hourly'))");
    }

    public function down(Schema $schema): void
    {
        $this->addSql("ALTER TABLE booking DROP CHECK chk_booking_status");
        $this->addSql("ALTER TABLE booking DROP CHECK chk_booking_type");
    }
}
