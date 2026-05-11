<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les champs firstName et lastName à l'entité User
 */
final class Version20260428AddUserNames extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add firstName and lastName columns to user table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" ADD first_name VARCHAR(100) DEFAULT NULL');
        $this->addSql('ALTER TABLE "user" ADD last_name VARCHAR(100) DEFAULT NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE "user" DROP COLUMN first_name');
        $this->addSql('ALTER TABLE "user" DROP COLUMN last_name');
    }
}
