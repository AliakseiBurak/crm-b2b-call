<?php

namespace App\Repository;

use App\Entity\Organization;
use App\Entity\User;
use App\Entity\Enum\UserRole;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Organization>
 */
class OrganizationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Organization::class);
    }

    /**
     * Возвращает ID организаций, доступных менеджеру (личная группа
     * user-<id>-group + все назначенные группы). Администратору и
     * гостю возвращается null — полный доступ ко всем организациям.
     *
     * @return int[]|null
     */
    public function findAccessibleIds(?User $user): ?array
    {
        if (null === $user || UserRole::Admin === $user->role) {
            return null;
        }

        $rows = $this->createQueryBuilder('o')
            ->select('o.id')
            ->distinct()
            ->join('App\Entity\OrgGroupMembership', 'm', 'WITH', 'm.organization = o')
            ->join('m.group', 'g')
            ->where('g.ownerUser = :user')
            ->orWhere('EXISTS (SELECT a FROM App\Entity\GroupAssignment a WHERE a.group = m.group AND a.user = :user)')
            ->setParameter('user', $user)
            ->getQuery()
            ->getScalarResult();

        return array_values(array_unique(array_map(
            static fn (array $row): int => (int) $row['id'],
            $rows
        )));
    }
}
