<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260906000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add description, color, created_by to organization_group; remove slug, personal groups, owner_user/type columns';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_group ADD description TEXT DEFAULT NULL, ADD color VARCHAR(7) DEFAULT NULL, ADD created_by BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_group ADD CONSTRAINT FK_C582B417DE2888EE FOREIGN KEY (created_by) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql("DELETE FROM organization_group WHERE type = 'user'");
        $this->addSql('ALTER TABLE organization_group DROP FOREIGN KEY FK_6D4DCEF9F4C6F2D0');
        $this->addSql('ALTER TABLE organization_group DROP INDEX IDX_6D4DCEF9F4C6F2D0');
        $this->addSql('ALTER TABLE organization_group DROP INDEX UNIQ_6D4DCEF9989D9B62');
        $this->addSql('ALTER TABLE organization_group DROP owner_user_id, DROP type, DROP slug');
        $this->addSql('ALTER TABLE organization_group MODIFY created_at DATETIME NOT NULL AFTER created_by');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE organization_group ADD slug VARCHAR(255) NOT NULL');
        $this->addSql('ALTER TABLE organization_group ADD UNIQUE INDEX UNIQ_6D4DCEF9989D9B62 (slug)');
        $this->addSql('ALTER TABLE organization_group ADD type VARCHAR(10) DEFAULT \'custom\' NOT NULL, ADD owner_user_id BIGINT DEFAULT NULL');
        $this->addSql('ALTER TABLE organization_group ADD CONSTRAINT FK_6D4DCEF9F4C6F2D0 FOREIGN KEY (owner_user_id) REFERENCES `user` (id) ON DELETE SET NULL');
        $this->addSql('ALTER TABLE organization_group ADD INDEX IDX_6D4DCEF9F4C6F2D0 (owner_user_id)');
        $this->addSql('ALTER TABLE organization_group DROP FOREIGN KEY FK_C582B417DE2888EE');
        $this->addSql('ALTER TABLE organization_group DROP description, DROP color, DROP created_by');
    }
}
