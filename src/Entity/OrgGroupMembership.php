<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'org_group_membership')]
class OrgGroupMembership
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: Organization::class, inversedBy: 'groupMemberships')]
    #[ORM\JoinColumn(name: 'organization_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Organization $organization;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: OrganizationGroup::class, inversedBy: 'memberships')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private OrganizationGroup $group;

    #[ORM\Column(name: 'added_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $addedAt;

    public function __construct(Organization $organization, OrganizationGroup $group)
    {
        $this->organization = $organization;
        $this->group = $group;
        $this->addedAt = new \DateTimeImmutable();
    }

    public function getOrganization(): Organization
    {
        return $this->organization;
    }

    public function getGroup(): OrganizationGroup
    {
        return $this->group;
    }

    public function getAddedAt(): \DateTimeImmutable
    {
        return $this->addedAt;
    }
}
