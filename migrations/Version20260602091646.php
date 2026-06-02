<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260602091646 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Make bingo.owner_id NOT NULL. Backfills any orphan bingos to a recovery admin user.';
    }

    public function up(Schema $schema): void
    {
        $orphans = (int) $this->connection->fetchOne('SELECT COUNT(*) FROM bingo WHERE owner_id IS NULL');

        if ($orphans > 0) {
            $this->addSql("INSERT IGNORE INTO user (email, roles, password, display_name, created_at) VALUES ('admin@bingo.local', '[\"ROLE_ADMIN\"]', '', 'Admin (récupération)', NOW())");
            $this->addSql("UPDATE bingo SET owner_id = (SELECT id FROM user WHERE email = 'admin@bingo.local') WHERE owner_id IS NULL");
        }

        $this->addSql('ALTER TABLE bingo CHANGE owner_id owner_id INT NOT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE bingo CHANGE owner_id owner_id INT DEFAULT NULL');
    }
}
