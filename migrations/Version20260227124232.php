<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20260227124232 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE photos_themes (photos_id INT NOT NULL, themes_id INT NOT NULL, PRIMARY KEY(photos_id, themes_id))');
        $this->addSql('CREATE INDEX IDX_7748B5C9301EC62 ON photos_themes (photos_id)');
        $this->addSql('CREATE INDEX IDX_7748B5C994F4A9D2 ON photos_themes (themes_id)');
        $this->addSql('ALTER TABLE photos_themes ADD CONSTRAINT FK_7748B5C9301EC62 FOREIGN KEY (photos_id) REFERENCES photos (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE photos_themes ADD CONSTRAINT FK_7748B5C994F4A9D2 FOREIGN KEY (themes_id) REFERENCES themes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE photos_themes DROP CONSTRAINT FK_7748B5C9301EC62');
        $this->addSql('ALTER TABLE photos_themes DROP CONSTRAINT FK_7748B5C994F4A9D2');
        $this->addSql('DROP TABLE photos_themes');
    }
}
