<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260908000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add organization_hide table (per-manager hide records, default-open deny-list)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE organization_hide (
            id CHAR(36) NOT NULL,
            organization_id BIGINT NOT NULL,
            manager_id BIGINT NOT NULL,
            hidden_at DATETIME NOT NULL,
            INDEX IDX_9D90F5B632C8A3DE (organization_id),
            INDEX IDX_9D90F5B6783E3463 (manager_id),
            UNIQUE INDEX UNIQ_ORG_HIDE_PAIR (organization_id, manager_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE organization_hide ADD CONSTRAINT FK_9D90F5B632C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE organization_hide ADD CONSTRAINT FK_9D90F5B6783E3463 FOREIGN KEY (manager_id) REFERENCES `user` (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_hide DROP FOREIGN KEY FK_9D90F5B6783E3463');
        $this->addSql('ALTER TABLE organization_hide DROP FOREIGN KEY FK_9D90F5B632C8A3DE');
        $this->addSql('DROP TABLE organization_hide');
    }
}
