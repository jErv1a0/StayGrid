<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260531090000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add guests column to booking (nullable int)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE booking ADD guests INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->fetchOne("SHOW COLUMNS FROM booking LIKE 'guests'")) {
            $this->addSql('ALTER TABLE booking DROP guests');
        }
    }
}
