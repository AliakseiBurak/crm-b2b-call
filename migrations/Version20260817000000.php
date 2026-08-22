<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260817000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Initial core schema: user, organization_group, group_assignment, org_group_membership, organization, contact, call';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('CREATE TABLE `user` (
            id BIGINT AUTO_INCREMENT NOT NULL,
            email VARCHAR(255) NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            role VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_8D93D649E7927C74 (email),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE organization_group (
            id BIGINT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            slug VARCHAR(255) NOT NULL,
            type VARCHAR(255) NOT NULL,
            owner_user_id BIGINT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_6D4DCEF9989D9B62 (slug),
            INDEX IDX_6D4DCEF9F4C6F2D0 (owner_user_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE organization_group ADD CONSTRAINT FK_6D4DCEF9F4C6F2D0 FOREIGN KEY (owner_user_id) REFERENCES `user` (id) ON DELETE SET NULL');

        $this->addSql('CREATE TABLE group_assignment (
            user_id BIGINT NOT NULL,
            group_id BIGINT NOT NULL,
            assigned_at DATETIME NOT NULL,
            INDEX IDX_9F0C5C12A76ED395 (user_id),
            INDEX IDX_9F0C5C12FE54D947 (group_id),
            PRIMARY KEY(user_id, group_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE group_assignment ADD CONSTRAINT FK_9F0C5C12A76ED395 FOREIGN KEY (user_id) REFERENCES `user` (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE group_assignment ADD CONSTRAINT FK_9F0C5C12FE54D947 FOREIGN KEY (group_id) REFERENCES organization_group (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE organization (
            id BIGINT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            industry VARCHAR(255) NOT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE TABLE org_group_membership (
            organization_id BIGINT NOT NULL,
            group_id BIGINT NOT NULL,
            added_at DATETIME NOT NULL,
            INDEX IDX_3F7A5F0B32C8A3DE (organization_id),
            INDEX IDX_3F7A5F0BFE54D947 (group_id),
            PRIMARY KEY(organization_id, group_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE org_group_membership ADD CONSTRAINT FK_3F7A5F0B32C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE org_group_membership ADD CONSTRAINT FK_3F7A5F0BFE54D947 FOREIGN KEY (group_id) REFERENCES organization_group (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE contact (
            id BIGINT AUTO_INCREMENT NOT NULL,
            organization_id BIGINT NOT NULL,
            name VARCHAR(255) NOT NULL,
            phone VARCHAR(32) DEFAULT NULL,
            email VARCHAR(255) DEFAULT NULL,
            position VARCHAR(255) DEFAULT NULL,
            contact_type VARCHAR(255) NOT NULL,
            contact_person VARCHAR(255) DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            INDEX IDX_4C62E63832C8A3DE (organization_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE contact ADD CONSTRAINT FK_4C62E63832C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');

        $this->addSql('CREATE TABLE `call` (
            id BIGINT AUTO_INCREMENT NOT NULL,
            organization_id BIGINT NOT NULL,
            contact_id BIGINT DEFAULT NULL,
            scheduled_at DATETIME DEFAULT NULL,
            made_at DATETIME DEFAULT NULL,
            made_by BIGINT DEFAULT NULL,
            notes LONGTEXT DEFAULT NULL,
            is_deal TINYINT(1) DEFAULT 0 NOT NULL,
            next_call_id BIGINT DEFAULT NULL,
            campaign_id BIGINT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_F1A6A5E032C8A3DE (organization_id),
            INDEX IDX_F1A6A5E04A4A3511 (contact_id),
            INDEX IDX_F1A6A5E0F4C6F2D0 (made_by),
            UNIQUE INDEX UNIQ_F1A6A5E0F4C6F2D0_2 (next_call_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('CREATE INDEX IDX_call_org_made ON `call` (organization_id, made_at)');

        $this->addSql('ALTER TABLE `call` ADD CONSTRAINT FK_F1A6A5E032C8A3DE FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE `call` ADD CONSTRAINT FK_F1A6A5E04A4A3511 FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `call` ADD CONSTRAINT FK_F1A6A5E0F4C6F2D0 FOREIGN KEY (made_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE `call` ADD CONSTRAINT FK_F1A6A5E0F4C6F2D0_2 FOREIGN KEY (next_call_id) REFERENCES `call` (id) ON DELETE SET NULL');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP INDEX IDX_call_org_made ON `call`');
        $this->addSql('ALTER TABLE `call` DROP FOREIGN KEY FK_F1A6A5E0F4C6F2D0_2');
        $this->addSql('ALTER TABLE `call` DROP FOREIGN KEY FK_F1A6A5E032C8A3DE');
        $this->addSql('ALTER TABLE `call` DROP FOREIGN KEY FK_F1A6A5E04A4A3511');
        $this->addSql('ALTER TABLE `call` DROP FOREIGN KEY FK_F1A6A5E0F4C6F2D0');
        $this->addSql('DROP TABLE `call`');
        $this->addSql('ALTER TABLE contact DROP FOREIGN KEY FK_4C62E63832C8A3DE');
        $this->addSql('DROP TABLE contact');
        $this->addSql('ALTER TABLE org_group_membership DROP FOREIGN KEY FK_3F7A5F0B32C8A3DE');
        $this->addSql('ALTER TABLE org_group_membership DROP FOREIGN KEY FK_3F7A5F0BFE54D947');
        $this->addSql('DROP TABLE org_group_membership');
        $this->addSql('ALTER TABLE group_assignment DROP FOREIGN KEY FK_9F0C5C12A76ED395');
        $this->addSql('ALTER TABLE group_assignment DROP FOREIGN KEY FK_9F0C5C12FE54D947');
        $this->addSql('DROP TABLE group_assignment');
        $this->addSql('ALTER TABLE organization_group DROP FOREIGN KEY FK_6D4DCEF9F4C6F2D0');
        $this->addSql('DROP TABLE organization_group');
        $this->addSql('DROP TABLE `user`');
        $this->addSql('DROP TABLE organization');
    }
}
