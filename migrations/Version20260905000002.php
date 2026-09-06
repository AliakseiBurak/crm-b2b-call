<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260905000002 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Change OrganizationGroup.ownerUser FK from SET NULL to CASCADE';
    }

    public function up(Schema $schema): void
    {
        $fkName = $this->getFkName();
        $this->addSql(sprintf('ALTER TABLE organization_group DROP FOREIGN KEY %s', $fkName));
        $this->addSql(sprintf(
            'ALTER TABLE organization_group ADD CONSTRAINT %s FOREIGN KEY (owner_user_id) REFERENCES `user` (id) ON DELETE CASCADE',
            $fkName,
        ));
    }

    public function down(Schema $schema): void
    {
        $fkName = $this->getFkName();
        $this->addSql(sprintf('ALTER TABLE organization_group DROP FOREIGN KEY %s', $fkName));
        $this->addSql(sprintf(
            'ALTER TABLE organization_group ADD CONSTRAINT %s FOREIGN KEY (owner_user_id) REFERENCES `user` (id) ON DELETE SET NULL',
            $fkName,
        ));
    }

    private function getFkName(): string
    {
        /** @var string $fkName */
        $fkName = $this->connection->fetchOne(
            "SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'organization_group'
               AND COLUMN_NAME = 'owner_user_id'
               AND REFERENCED_TABLE_NAME = 'user'",
        );

        return $fkName;
    }
}
