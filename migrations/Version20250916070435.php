<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250916070435 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album ADD photos_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE album RENAME COLUMN class TO categorie');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT FK_39986E43301EC62 FOREIGN KEY (photos_id) REFERENCES photos (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_39986E43301EC62 ON album (photos_id)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE album DROP CONSTRAINT FK_39986E43301EC62');
        $this->addSql('DROP INDEX IDX_39986E43301EC62');
        $this->addSql('ALTER TABLE album DROP photos_id');
        $this->addSql('ALTER TABLE album RENAME COLUMN categorie TO class');
    }
}
