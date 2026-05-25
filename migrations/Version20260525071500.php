<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525071500 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add approval workflow columns to feedback table (approved, approved_at, approved_by)';
    }

    public function up(Schema $schema): void
    {
        // Only apply if feedback table exists
        if (! $this->connection->fetchOne("SHOW TABLES LIKE 'feedback'")) {
            return;
        }

        // approved (boolean)
        $approvedExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'approved'"
        );

        if (! $approvedExists) {
            $this->addSql("ALTER TABLE feedback ADD approved TINYINT(1) NOT NULL DEFAULT 0");
        }

        // approved_at (datetime_immutable)
        $approvedAtExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'approved_at'"
        );

        if (! $approvedAtExists) {
            $this->addSql("ALTER TABLE feedback ADD approved_at DATETIME DEFAULT NULL");
        }

        // approved_by (FK to log_in_users)
        $approvedByExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = 'approved_by'"
        );

        if (! $approvedByExists) {
            $this->addSql('ALTER TABLE feedback ADD approved_by INT DEFAULT NULL');
            $this->addSql('CREATE INDEX IDX_FEEDBACK_APPROVED_BY ON feedback (approved_by)');

            // Add FK only if log_in_users table exists
            if ($this->connection->fetchOne("SHOW TABLES LIKE 'log_in_users'")) {
                $this->addSql('ALTER TABLE feedback ADD CONSTRAINT FK_FEEDBACK_APPROVED_BY FOREIGN KEY (approved_by) REFERENCES log_in_users (id)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if (! $this->connection->fetchOne("SHOW TABLES LIKE 'feedback'")) {
            return;
        }

        // Drop foreign key if exists
        $fkExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND CONSTRAINT_NAME = 'FK_FEEDBACK_APPROVED_BY'"
        );

        if ($fkExists) {
            $this->addSql('ALTER TABLE feedback DROP FOREIGN KEY FK_FEEDBACK_APPROVED_BY');
        }

        // Drop index if exists
        $indexExists = (bool) $this->connection->fetchOne(
            "SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND INDEX_NAME = 'IDX_FEEDBACK_APPROVED_BY'"
        );

        if ($indexExists) {
            $this->addSql('DROP INDEX IDX_FEEDBACK_APPROVED_BY ON feedback');
        }

        // Drop columns if they exist
        $cols = ['approved_by', 'approved_at', 'approved'];
        foreach ($cols as $col) {
            $colExists = (bool) $this->connection->fetchOne(
                sprintf("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'feedback' AND COLUMN_NAME = '%s'", $col)
            );
            if ($colExists) {
                $this->addSql(sprintf('ALTER TABLE feedback DROP COLUMN %s', $col));
            }
        }
    }
}
