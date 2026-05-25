<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260525000100 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Align Railway schema with current entities and fixture seed data.';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->fetchOne("SHOW TABLES LIKE 'roomlisting'")) {
            $titleExists = (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'roomlisting' AND COLUMN_NAME = 'title'");
            if ($titleExists) {
                $this->addSql("ALTER TABLE roomlisting MODIFY title VARCHAR(255) NOT NULL DEFAULT ''");
            }
        }

        if ($this->connection->fetchOne("SHOW TABLES LIKE 'log_in_users'")) {
            $fullNameExists = (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_in_users' AND COLUMN_NAME = 'full_name'");
            if (! $fullNameExists) {
                $this->addSql('ALTER TABLE log_in_users ADD full_name VARCHAR(255) DEFAULT NULL');
            }

            $profilePictureExists = (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_in_users' AND COLUMN_NAME = 'profile_picture'");
            if (! $profilePictureExists) {
                $this->addSql('ALTER TABLE log_in_users ADD profile_picture VARCHAR(255) DEFAULT NULL');
            }
        }

        if ($this->connection->fetchOne("SHOW TABLES LIKE 'booking'")) {
            $bookingUserExists = (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking' AND COLUMN_NAME = 'user_id'");
            if (! $bookingUserExists) {
                $this->addSql('ALTER TABLE booking ADD user_id INT DEFAULT NULL');
                $this->addSql('CREATE INDEX IDX_BOOKING_USER_ID ON booking (user_id)');
                $this->addSql('ALTER TABLE booking ADD CONSTRAINT FK_BOOKING_USER FOREIGN KEY (user_id) REFERENCES log_in_users (id)');
            }
        }

        if ($this->connection->fetchOne("SHOW TABLES LIKE 'transaction'")) {
            $amountExists = (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction' AND COLUMN_NAME = 'amount'");
            if (! $amountExists) {
                $this->addSql('ALTER TABLE transaction ADD amount DECIMAL(10,2) DEFAULT NULL');
            }

            $bookingIdExists = (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction' AND COLUMN_NAME = 'booking_id'");
            if (! $bookingIdExists) {
                $this->addSql('ALTER TABLE transaction ADD booking_id INT NOT NULL');
                $this->addSql('CREATE INDEX IDX_TRANSACTION_BOOKING_ID ON transaction (booking_id)');
                $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_TRANSACTION_BOOKING FOREIGN KEY (booking_id) REFERENCES booking (id) ON DELETE CASCADE');
            }

            $userFkExists = (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction' AND CONSTRAINT_NAME = 'FK_TRANSACTION_LOG_IN_USERS' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
            if (! $userFkExists) {
                $oldFkExists = (bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction' AND CONSTRAINT_NAME = 'FK_723705D1A76ED395' AND CONSTRAINT_TYPE = 'FOREIGN KEY'");
                if ($oldFkExists) {
                    $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_723705D1A76ED395');
                }

                $this->addSql('ALTER TABLE transaction ADD CONSTRAINT FK_TRANSACTION_LOG_IN_USERS FOREIGN KEY (user_id) REFERENCES log_in_users (id)');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->fetchOne("SHOW TABLES LIKE 'transaction'")) {
            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction' AND CONSTRAINT_NAME = 'FK_TRANSACTION_LOG_IN_USERS' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")) {
                $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_TRANSACTION_LOG_IN_USERS');
            }

            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction' AND CONSTRAINT_NAME = 'FK_TRANSACTION_BOOKING' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")) {
                $this->addSql('ALTER TABLE transaction DROP FOREIGN KEY FK_TRANSACTION_BOOKING');
            }

            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction' AND COLUMN_NAME = 'booking_id'")) {
                $this->addSql('ALTER TABLE transaction DROP COLUMN booking_id');
            }

            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'transaction' AND COLUMN_NAME = 'amount'")) {
                $this->addSql('ALTER TABLE transaction DROP COLUMN amount');
            }
        }

        if ($this->connection->fetchOne("SHOW TABLES LIKE 'booking'")) {
            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.TABLE_CONSTRAINTS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking' AND CONSTRAINT_NAME = 'FK_BOOKING_USER' AND CONSTRAINT_TYPE = 'FOREIGN KEY'")) {
                $this->addSql('ALTER TABLE booking DROP FOREIGN KEY FK_BOOKING_USER');
            }

            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'booking' AND COLUMN_NAME = 'user_id'")) {
                $this->addSql('ALTER TABLE booking DROP COLUMN user_id');
            }
        }

        if ($this->connection->fetchOne("SHOW TABLES LIKE 'log_in_users'")) {
            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_in_users' AND COLUMN_NAME = 'profile_picture'")) {
                $this->addSql('ALTER TABLE log_in_users DROP COLUMN profile_picture');
            }

            if ((bool) $this->connection->fetchOne("SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'log_in_users' AND COLUMN_NAME = 'full_name'")) {
                $this->addSql('ALTER TABLE log_in_users DROP COLUMN full_name');
            }
        }
    }
}