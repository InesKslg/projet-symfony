<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250916074348 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE album_photos (album_id INT NOT NULL, photos_id INT NOT NULL, PRIMARY KEY(album_id, photos_id))');
        $this->addSql('CREATE INDEX IDX_DA0DDD6E1137ABCF ON album_photos (album_id)');
        $this->addSql('CREATE INDEX IDX_DA0DDD6E301EC62 ON album_photos (photos_id)');
        $this->addSql('ALTER TABLE album_photos ADD CONSTRAINT FK_DA0DDD6E1137ABCF FOREIGN KEY (album_id) REFERENCES album (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE album_photos ADD CONSTRAINT FK_DA0DDD6E301EC62 FOREIGN KEY (photos_id) REFERENCES photos (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE album_photos DROP CONSTRAINT FK_DA0DDD6E1137ABCF');
        $this->addSql('ALTER TABLE album_photos DROP CONSTRAINT FK_DA0DDD6E301EC62');
        $this->addSql('DROP TABLE album_photos');
    }
}
