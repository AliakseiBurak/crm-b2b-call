<?php

namespace App\Repository;

use App\Entity\Enum\UserRole;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    /**
     * Все администраторы и менеджеры (персонал, способный совершать звонки),
     * отсортированные по email. Используется для выбора автора звонка.
     *
     * @return User[]
     */
    public function findAdminsAndManagers(): array
    {
        return $this->createQueryBuilder('u')
            ->where('u.role IN (:roles)')
            ->setParameter('roles', [UserRole::Admin, UserRole::Manager])
            ->orderBy('u.email', 'ASC')
            ->getQuery()
            ->getResult();
    }
}
