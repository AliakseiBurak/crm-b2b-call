<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260903120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Call result: FK call.campaign_id, is_no_answer column';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `call` ADD is_no_answer TINYINT(1) DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE `call` ADD CONSTRAINT FK_F1A6A5E0F639F774 FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE SET NULL');
        $this->addSql('CREATE INDEX IDX_F1A6A5E0F639F774 ON `call` (campaign_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE `call` DROP FOREIGN KEY FK_F1A6A5E0F639F774');
        $this->addSql('DROP INDEX IDX_F1A6A5E0F639F774 ON `call`');
        $this->addSql('ALTER TABLE `call` DROP is_no_answer');
    }
}
