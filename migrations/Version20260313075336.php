<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260313075336 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // Table notification
        $this->addSql('CREATE TABLE notification (id SERIAL NOT NULL, PRIMARY KEY(id))');

        // Correction : conversion explicite pour PostgreSQL
        $this->addSql('ALTER TABLE photos ALTER date_prise TYPE TIMESTAMP(0) WITHOUT TIME ZONE USING date_prise::timestamp');

        $this->addSql('COMMENT ON COLUMN photos.date_prise IS \'(DC2Type:datetime_immutable)\'');

        $this->addSql('ALTER TABLE photos_themes DROP CONSTRAINT FK_7748B5C9301EC62');
        $this->addSql('ALTER TABLE photos_themes DROP CONSTRAINT FK_7748B5C994F4A9D2');
        $this->addSql('ALTER TABLE photos_themes DROP CONSTRAINT photos_themes_pkey');
        $this->addSql('ALTER TABLE photos_themes ADD CONSTRAINT FK_7748B5C9301EC62 FOREIGN KEY (photos_id) REFERENCES photos (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE photos_themes ADD CONSTRAINT FK_7748B5C994F4A9D2 FOREIGN KEY (themes_id) REFERENCES themes (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE photos_themes ADD PRIMARY KEY (photos_id, themes_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP TABLE notification');
        $this->addSql('ALTER TABLE photos ALTER date_prise TYPE VARCHAR(255)');
        $this->addSql('COMMENT ON COLUMN photos.date_prise IS NULL');
        $this->addSql('ALTER TABLE photos_themes DROP CONSTRAINT fk_7748b5c9301ec62');
        $this->addSql('ALTER TABLE photos_themes DROP CONSTRAINT fk_7748b5c994f4a9d2');
        $this->addSql('DROP INDEX photos_themes_pkey');
        $this->addSql('ALTER TABLE photos_themes ADD CONSTRAINT fk_7748b5c9301ec62 FOREIGN KEY (photos_id) REFERENCES photos (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE photos_themes ADD CONSTRAINT fk_7748b5c994f4a9d2 FOREIGN KEY (themes_id) REFERENCES themes (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE photos_themes ADD PRIMARY KEY (themes_id, photos_id)');
    }
}
