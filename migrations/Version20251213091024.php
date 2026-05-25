<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20251213091024 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if (! $this->connection->fetchOne("SHOW TABLES LIKE 'transaction'")) {
            $this->addSql('CREATE TABLE transaction (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, check_in DATETIME NOT NULL, check_out DATETIME NOT NULL, status VARCHAR(20) NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_723705D154177093 (room_id), INDEX IDX_723705D1A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if (! $this->connection->fetchOne("SHOW TABLES LIKE 'user'")) {
            $this->addSql('CREATE TABLE `user` (id INT AUTO_INCREMENT NOT NULL, email VARCHAR(180) NOT NULL, roles JSON NOT NULL, password VARCHAR(255) NOT NULL, is_verified TINYINT(1) NOT NULL, UNIQUE INDEX UNIQ_8D93D649E7927C74 (email), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        }

        if ($this->connection->fetchOne("SHOW TABLES LIKE 'transaction'")) {
            $this->addSql('ALTER TABLE transaction ADD CONSTRAINT IF NOT EXISTS FK_723705D154177093 FOREIGN KEY (room_id) REFERENCES roomlisting (id)');
            $this->addSql('ALTER TABLE transaction ADD CONSTRAINT IF NOT EXISTS FK_723705D1A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id)');
        }

        if ($this->connection->fetchOne("SHOW TABLES LIKE 'booking'")) {
            $this->addSql('ALTER TABLE booking ADD CONSTRAINT IF NOT EXISTS FK_E00CEDDE54177093 FOREIGN KEY (room_id) REFERENCES roomlisting (id)');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D154177093');
        $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395');
        $this->addSql('DROP TABLE transaction');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_E00CEDDE54177093');
    }
}
