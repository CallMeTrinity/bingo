<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260528151850 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE bingo ADD size INT DEFAULT 4 NOT NULL, CHANGE slug slug VARCHAR(8) NOT NULL');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_D6060C3A989D9B62 ON bingo (slug)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP INDEX UNIQ_D6060C3A989D9B62 ON bingo');
        $this->addSql('ALTER TABLE bingo DROP size, CHANGE slug slug VARCHAR(255) NOT NULL');
    }
}
