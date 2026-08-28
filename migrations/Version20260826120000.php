<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20260826120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Campaigns: create campaign (subject, body, status, launched_at), campaign_attachment and campaign_recipient';
    }

    public function up(Schema $schema): void
    {
        $this->addSql("CREATE TABLE campaign (
            id BIGINT AUTO_INCREMENT NOT NULL,
            name VARCHAR(255) NOT NULL,
            subject VARCHAR(255) NOT NULL,
            preview_text VARCHAR(255) DEFAULT NULL,
            body LONGTEXT NOT NULL,
            status ENUM('draft','ready','launched','failed','archived') DEFAULT 'draft' NOT NULL,
            launched_at DATETIME DEFAULT NULL,
            failed_at DATETIME DEFAULT NULL,
            created_at DATETIME NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB");

        $this->addSql('CREATE TABLE campaign_attachment (
            id BIGINT AUTO_INCREMENT NOT NULL,
            campaign_id BIGINT NOT NULL,
            filename VARCHAR(255) NOT NULL,
            storage_key VARCHAR(128) NOT NULL,
            mime_type VARCHAR(255) DEFAULT NULL,
            size INT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            UNIQUE INDEX UNIQ_C1E7F5A8F5B7E5A2 (storage_key),
            INDEX IDX_C1E7F5A832C8A3DE (campaign_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Каскад строк вложений при удалении кампании (design campaign-entity,
        // решение 2); файлы в storage удаляет сервисный слой.
        $this->addSql('ALTER TABLE campaign_attachment ADD CONSTRAINT FK_C1E7F5A832C8A3DE FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');

        // Ручные адресаты standalone-рассылок (design campaign-entity, решение 5).
        // contact_id — опциональный: рассылка конкретному контакту по email.
        $this->addSql('CREATE TABLE campaign_recipient (
            id BIGINT AUTO_INCREMENT NOT NULL,
            campaign_id BIGINT NOT NULL,
            organization_id BIGINT NOT NULL,
            contact_id BIGINT DEFAULT NULL,
            created_at DATETIME NOT NULL,
            INDEX IDX_A6B9C1D232C8A3DE (campaign_id),
            INDEX IDX_A6B9C1D3989D9B62_2 (organization_id),
            INDEX IDX_A6B9C1D26303C158 (contact_id),
            UNIQUE INDEX UNIQ_CAMPAIGN_ORG (campaign_id, organization_id),
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        $this->addSql('ALTER TABLE campaign_recipient ADD CONSTRAINT FK_A6B9C1D232C8A3DE FOREIGN KEY (campaign_id) REFERENCES campaign (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_recipient ADD CONSTRAINT FK_A6B9C1D3989D9B62_2 FOREIGN KEY (organization_id) REFERENCES organization (id) ON DELETE CASCADE');
        $this->addSql('ALTER TABLE campaign_recipient ADD CONSTRAINT FK_A6B9C1D26303C158 FOREIGN KEY (contact_id) REFERENCES contact (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE campaign_recipient DROP FOREIGN KEY FK_A6B9C1D26303C158');
        $this->addSql('ALTER TABLE campaign_recipient DROP FOREIGN KEY FK_A6B9C1D232C8A3DE');
        $this->addSql('ALTER TABLE campaign_recipient DROP FOREIGN KEY FK_A6B9C1D3989D9B62_2');
        $this->addSql('DROP TABLE campaign_recipient');
        $this->addSql('ALTER TABLE campaign_attachment DROP FOREIGN KEY FK_C1E7F5A832C8A3DE');
        $this->addSql('DROP TABLE campaign_attachment');
        $this->addSql('DROP TABLE campaign');
    }
}
