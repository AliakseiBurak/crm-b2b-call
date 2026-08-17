<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ORM\Table(name: 'group_assignment')]
class GroupAssignment
{
    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'groupAssignments')]
    #[ORM\JoinColumn(name: 'user_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Id]
    #[ORM\ManyToOne(targetEntity: OrganizationGroup::class, inversedBy: 'assignments')]
    #[ORM\JoinColumn(name: 'group_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private OrganizationGroup $group;

    #[ORM\Column(name: 'assigned_at', type: 'datetime_immutable')]
    private \DateTimeImmutable $assignedAt;

    public function __construct(User $user, OrganizationGroup $group)
    {
        $this->user = $user;
        $this->group = $group;
        $this->assignedAt = new \DateTimeImmutable();
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getGroup(): OrganizationGroup
    {
        return $this->group;
    }

    public function getAssignedAt(): \DateTimeImmutable
    {
        return $this->assignedAt;
    }
}
