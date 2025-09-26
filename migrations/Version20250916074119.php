<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250916074119 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE album DROP CONSTRAINT fk_39986e43301ec62');
        $this->addSql('DROP INDEX idx_39986e43301ec62');
        $this->addSql('ALTER TABLE album DROP photos_id');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE album ADD photos_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE album ADD CONSTRAINT fk_39986e43301ec62 FOREIGN KEY (photos_id) REFERENCES photos (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX idx_39986e43301ec62 ON album (photos_id)');
    }
}
