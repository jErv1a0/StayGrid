<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251019135614 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    private function tableExists(string $table): bool
    {
        try {
            return (bool) $this->connection->fetchOne("SHOW TABLES LIKE '" . $table . "'");
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            return (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '" . $table . "' AND COLUMN_NAME = '" . $column . "'");
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function up(Schema $schema): void
    {
        if (! $this->tableExists('crud_room')) {
            $this->addSql('CREATE TABLE crud_room (id INT AUTO_INCREMENT NOT NULL, number VARCHAR(10) NOT NULL, capacity INT NOT NULL, price DOUBLE PRECISION NOT NULL, description LONGTEXT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if ($this->tableExists('roomlisting')) {
            if (! $this->columnExists('roomlisting', 'is_available')) {
                $this->addSql('ALTER TABLE roomlisting ADD is_available TINYINT(1) NOT NULL');
            }

            if (! $this->columnExists('roomlisting', 'number')) {
                $this->addSql('ALTER TABLE roomlisting ADD number VARCHAR(255) NOT NULL');
            } else {
                $this->addSql('ALTER TABLE roomlisting MODIFY number VARCHAR(255) NOT NULL');
            }

            if (! $this->columnExists('roomlisting', 'capacity')) {
                $this->addSql('ALTER TABLE roomlisting ADD capacity INT DEFAULT NULL');
            } else {
                $this->addSql('ALTER TABLE roomlisting MODIFY capacity INT DEFAULT NULL');
            }

            if (! $this->columnExists('roomlisting', 'price')) {
                $this->addSql('ALTER TABLE roomlisting ADD price NUMERIC(10, 2) NOT NULL');
            } else {
                $this->addSql('ALTER TABLE roomlisting MODIFY price NUMERIC(10, 2) NOT NULL');
            }

            if (! $this->columnExists('roomlisting', 'description')) {
                $this->addSql('ALTER TABLE roomlisting ADD description LONGTEXT DEFAULT NULL');
            } else {
                $this->addSql('ALTER TABLE roomlisting MODIFY description LONGTEXT DEFAULT NULL');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->tableExists('crud_room')) {
            $this->addSql('DROP TABLE crud_room');
        }

        if ($this->tableExists('roomlisting')) {
            $this->addSql('ALTER TABLE roomlisting DROP is_available, CHANGE number number VARCHAR(10) NOT NULL, CHANGE description description LONGTEXT NOT NULL, CHANGE capacity capacity INT NOT NULL, CHANGE price price DOUBLE PRECISION NOT NULL');
        }
    }
}
