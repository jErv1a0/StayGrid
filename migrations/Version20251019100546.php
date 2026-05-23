<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251019100546 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        // Some environments use 'room_listing' and others 'roomlisting' — detect which exists.
        $table = null;
        try {
            if ($this->connection->fetchOne("SHOW TABLES LIKE 'room_listing'")) {
                $table = 'room_listing';
            } elseif ($this->connection->fetchOne("SHOW TABLES LIKE 'roomlisting'")) {
                $table = 'roomlisting';
            }
        } catch (\Throwable $e) {
            $table = null;
        }

        if ($table) {
            // Build alter statements conditionally
            $clauses = [];
            // Add columns if missing
            if (! $this->connection->fetchOne("SHOW COLUMNS FROM $table LIKE 'croom'")) {
                $clauses[] = "ADD croom VARCHAR(255) NOT NULL";
            }
            if (! $this->connection->fetchOne("SHOW COLUMNS FROM $table LIKE 'description'")) {
                $clauses[] = "ADD description LONGTEXT DEFAULT NULL";
            }
            if (! $this->connection->fetchOne("SHOW COLUMNS FROM $table LIKE 'price'")) {
                $clauses[] = "ADD price DOUBLE PRECISION NOT NULL";
            }
            if (! $this->connection->fetchOne("SHOW COLUMNS FROM $table LIKE 'capacity'")) {
                $clauses[] = "ADD capacity INT NOT NULL";
            }
            // Drop 'number' column only if it exists
            if ($this->connection->fetchOne("SHOW COLUMNS FROM $table LIKE 'number'")) {
                $clauses[] = "DROP number";
            }

            if (count($clauses)) {
                $this->addSql('ALTER TABLE ' . $table . ' ' . implode(', ', $clauses));
            }
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $table = null;
        try {
            if ($this->connection->fetchOne("SHOW TABLES LIKE 'room_listing'")) {
                $table = 'room_listing';
            } elseif ($this->connection->fetchOne("SHOW TABLES LIKE 'roomlisting'")) {
                $table = 'roomlisting';
            }
        } catch (\Throwable $e) {
            $table = null;
        }

        if ($table) {
            $this->addSql("ALTER TABLE $table ADD number VARCHAR(10) NOT NULL, DROP croom, DROP description, DROP price, DROP capacity");
        }
    }
}
