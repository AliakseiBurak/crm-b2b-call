<?php

namespace App\Entity;

use App\Entity\Enum\GroupType;
use App\Repository\OrganizationGroupRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: OrganizationGroupRepository::class)]
#[ORM\Table(name: 'organization_group')]
class OrganizationGroup
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    public private(set) ?int $id = null;

    #[ORM\Column(length: 255)]
    public private(set) string $name;

    #[ORM\Column(length: 255, unique: true)]
    public private(set) string $slug;

    #[ORM\Column(type: 'string', enumType: GroupType::class)]
    public private(set) GroupType $type;

    #[ORM\OneToOne(targetEntity: User::class, inversedBy: 'personalGroup')]
    #[ORM\JoinColumn(name: 'owner_user_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    public private(set) ?User $ownerUser = null;

    #[ORM\Column(name: 'created_at', type: 'datetime_immutable')]
    public private(set) \DateTimeImmutable $createdAt;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: GroupAssignment::class)]
    public private(set) Collection $assignments;

    #[ORM\OneToMany(mappedBy: 'group', targetEntity: OrgGroupMembership::class)]
    public private(set) Collection $memberships;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->assignments = new ArrayCollection();
        $this->memberships = new ArrayCollection();
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }

    public function setType(GroupType $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function setOwnerUser(?User $ownerUser): self
    {
        $this->ownerUser = $ownerUser;

        return $this;
    }
}
