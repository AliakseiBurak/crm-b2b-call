<?php

namespace App\Entity;

use App\Repository\CampaignRecipientRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Ручной адресат standalone-рассылки (design campaign-entity, решение 5).
 * Добавлять организацию адресатом менеджеру разрешено только в пределах
 * области доступа (ADR-0007, отказ 403), администратору — любую (ADR-0008).
 *
 * contact_id — опциональный: если указан, рассылка идёт на email контакта;
 * если null — на организацию в целом.
 */
#[ORM\Entity(repositoryClass: CampaignRecipientRepository::class)]
#[ORM\Table(name: 'campaign_recipient')]
#[ORM\UniqueConstraint(name: 'UNIQ_CAMPAIGN_ORG', columns: ['campaign_id', 'organization_id'])]
class CampaignRecipient
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'recipients')]
    #[ORM\JoinColumn(name: 'campaign_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public private(set) Campaign $campaign;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    public private(set) Organization $organization;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: true, onDelete: 'CASCADE')]
    public private(set) ?Contact $contact = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public private(set) \DateTimeImmutable $createdAt;

    public function __construct(Campaign $campaign, Organization $organization, ?Contact $contact = null)
    {
        $this->campaign = $campaign;
        $this->organization = $organization;
        $this->contact = $contact;
        $this->createdAt = new \DateTimeImmutable();
        $campaign->addRecipient($this);
    }
}
