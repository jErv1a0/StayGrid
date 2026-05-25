<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525080000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add rating column to feedback (nullable int)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE feedback ADD rating INT DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->fetchOne("SHOW COLUMNS FROM feedback LIKE 'rating'")) {
            $this->addSql('ALTER TABLE feedback DROP rating');
        }
    }
}
