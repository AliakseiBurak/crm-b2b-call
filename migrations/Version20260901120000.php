<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260901120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add mailing service fields: failure_reason on campaign; per-letter status on campaign_recipient';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE campaign ADD failure_reason TEXT DEFAULT NULL');

        $this->addSql("ALTER TABLE campaign_recipient
            ADD status ENUM('pending','sending','delivered','bounced','failed','opened') DEFAULT 'pending' NOT NULL,
            ADD sent_at DATETIME DEFAULT NULL,
            ADD error_message TEXT DEFAULT NULL,
            ADD tracking_token VARCHAR(64) DEFAULT NULL,
            ADD retry_count INT DEFAULT 0 NOT NULL,
            ADD retry_at DATETIME DEFAULT NULL");

        $this->addSql('CREATE UNIQUE INDEX UNIQ_RECIPIENT_TOKEN ON campaign_recipient (tracking_token)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE campaign_recipient DROP INDEX UNIQ_RECIPIENT_TOKEN');

        $this->addSql('ALTER TABLE campaign_recipient
            DROP status,
            DROP sent_at,
            DROP error_message,
            DROP tracking_token,
            DROP retry_count,
            DROP retry_at');

        $this->addSql('ALTER TABLE campaign DROP failure_reason');
    }
}
