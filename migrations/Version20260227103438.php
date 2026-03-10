<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227103438 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album ADD user_id INT NOT NULL');
        $this->addSql('ALTER TABLE album ADD status BOOLEAN NOT NULL');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E43A76ED395 FOREIGN KEY (user_id) REFERENCES "user" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_39986E43A76ED395 ON album (user_id)');
        $this->addSql('ALTER TABLE photos ADD theme VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE photos ADD localisation VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE photos ADD date_prise VARCHAR(255) NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE photos DROP theme');
        $this->addSql('ALTER TABLE photos DROP localisation');
        $this->addSql('ALTER TABLE photos DROP date_prise');
        $this->addSql('ALTER TABLE album DROP CONSTRAINT FK_39986E43A76ED395');
        $this->addSql('DROP INDEX IDX_39986E43A76ED395');
        $this->addSql('ALTER TABLE album DROP user_id');
        $this->addSql('ALTER TABLE album DROP status');
    }
}
