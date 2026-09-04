<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260904000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Remove contact_type and contact_person columns from contact table';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact DROP contact_type, DROP contact_person');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE contact ADD contact_type VARCHAR(255) DEFAULT \'person\' NOT NULL, ADD contact_person VARCHAR(255) DEFAULT NULL');
    }
}
