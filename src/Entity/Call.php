<?php

namespace App\Entity;

use App\Repository\CallRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CallRepository::class)]
#[ORM\Table(name: '`call`')]
class Call
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Organization::class)]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Organization $organization;

    #[ORM\ManyToOne(targetEntity: Contact::class)]
    #[ORM\JoinColumn(name: 'contact_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Contact $contact = null;

    #[ORM\Column(name: 'scheduled_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $scheduledAt = null;

    #[ORM\Column(name: 'made_at', type: 'datetime_immutable', nullable: true)]
    private ?\DateTimeImmutable $madeAt = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(name: 'made_by', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?User $madeBy = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(name: 'is_deal', type: 'boolean', options: ['default' => false])]
    private bool $isDeal = false;

    #[ORM\OneToOne(targetEntity: Call::class)]
    #[ORM\JoinColumn(name: 'next_call_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    private ?Call $nextCall = null;

    #[ORM\Column(name: 'campaign_id', type: 'bigint', nullable: true)]
    private ?string $campaignId = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function setOrganization(Organization $organization): self
    {
        $this->organization = $organization;

        return $this;
    }

    public function getContact(): ?Contact
    {
        return $this->contact;
    }

    public function setContact(?Contact $contact): self
    {
        $this->contact = $contact;

        return $this;
    }

    public function getScheduledAt(): ?\DateTimeImmutable
    {
        return $this->scheduledAt;
    }

    public function setScheduledAt(?\DateTimeImmutable $scheduledAt): self
    {
        $this->scheduledAt = $scheduledAt;

        return $this;
    }

    public function getMadeAt(): ?\DateTimeImmutable
    {
        return $this->madeAt;
    }

    public function setMadeAt(?\DateTimeImmutable $madeAt): self
    {
        $this->madeAt = $madeAt;

        return $this;
    }

    public function getMadeBy(): ?User
    {
        return $this->madeBy;
    }

    public function setMadeBy(?User $madeBy): self
    {
        $this->madeBy = $madeBy;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

        return $this;
    }

    public function isDeal(): bool
    {
        return $this->isDeal;
    }

    public function setIsDeal(bool $isDeal): self
    {
        $this->isDeal = $isDeal;

        return $this;
    }

    public function getNextCall(): ?Call
    {
        return $this->nextCall;
    }

    public function setNextCall(?Call $nextCall): self
    {
        $this->nextCall = $nextCall;

        return $this;
    }

    public function getCampaignId(): ?string
    {
        return $this->campaignId;
    }

    public function setCampaignId(?string $campaignId): self
    {
        $this->campaignId = $campaignId;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
