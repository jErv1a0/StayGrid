<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525000200 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create user_activity table for user-specific activity logging.';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE IF NOT EXISTS user_activity (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, action VARCHAR(32) NOT NULL, ip VARCHAR(45) DEFAULT NULL, user_agent VARCHAR(512) DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_USER_ACTIVITY_USER_ID (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
        $this->addSql('ALTER TABLE user_activity ADD CONSTRAINT FK_USER_ACTIVITY_USER FOREIGN KEY (user_id) REFERENCES log_in_users (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->fetchOne("SHOW TABLES LIKE 'user_activity'")) {
            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'user_activity' AND CONSTRAINT_NAME = 'FK_USER_ACTIVITY_USER' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")) {
                $this->addSql('ALTER TABLE user_activity DROP FOREIGN KEY FK_USER_ACTIVITY_USER');
            }

            $this->addSql('DROP TABLE user_activity');
        }
    }
}