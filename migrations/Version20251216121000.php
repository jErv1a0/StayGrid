<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251216121000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create activity_log table to store tivities';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE activity_log (id INT AUTO_INCREMENT NOT NULL, action VARCHAR(50) NOT NULL, actor_type VARCHAR(50) DEFAULT NULL, actor_id INT DEFAULT NULL, actor_email VARCHAR(255) DEFAULT NULL, target_type VARCHAR(100) DEFAULT NULL, target_id INT DEFAULT NULL, details JSON DEFAULT NULL, created_at DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE activity_log');
    }
}
