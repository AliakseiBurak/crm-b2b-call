<?php

namespace App\Repository;

use App\Dto\DashboardStats;
use App\Entity\Call;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Call>
 */
class CallRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Call::class);
    }

    /**
     * Считает метрики дашборда одним запросом. null $organizationIds —
     * вся база (администратор/гость).
     *
     * @param int[]|null $organizationIds
     */
    public function dashboardStats(?array $organizationIds, \DateTimeImmutable $now): DashboardStats
    {
        $todayStart = $now->setTime(0, 0);
        $todayEnd = $now->setTime(23, 59, 59);

        $qb = $this->createQueryBuilder('c')
            ->select(
                'SUM(CASE WHEN c.madeAt IS NOT NULL AND c.madeAt >= :todayStart THEN 1 ELSE 0 END) AS calledToday',
                'SUM(CASE WHEN c.madeAt IS NOT NULL AND c.madeAt >= :weekStart THEN 1 ELSE 0 END) AS calledWeek',
                'SUM(CASE WHEN c.madeAt IS NOT NULL AND c.madeAt >= :monthStart THEN 1 ELSE 0 END) AS calledMonth',
                'SUM(CASE WHEN c.scheduledAt IS NOT NULL AND c.scheduledAt BETWEEN :todayStart AND :todayEnd THEN 1 ELSE 0 END) AS waitingToday',
                'SUM(CASE WHEN c.scheduledAt IS NOT NULL AND c.scheduledAt > :now AND c.scheduledAt <= :weekEnd THEN 1 ELSE 0 END) AS waitingWeek',
                'SUM(CASE WHEN c.scheduledAt IS NOT NULL AND c.scheduledAt > :now AND c.scheduledAt <= :monthEnd THEN 1 ELSE 0 END) AS waitingMonth',
            )
            ->setParameter('todayStart', $todayStart)
            ->setParameter('todayEnd', $todayEnd)
            ->setParameter('weekStart', $todayStart->modify('-6 days'))
            ->setParameter('monthStart', $todayStart->modify('-29 days'))
            ->setParameter('now', $now)
            ->setParameter('weekEnd', $now->modify('+7 days'))
            ->setParameter('monthEnd', $now->modify('+30 days'));

        if (null !== $organizationIds) {
            $qb->andWhere('c.organization IN (:organizationIds)')
                ->setParameter('organizationIds', $organizationIds);
        }

        $row = $qb->getQuery()->getSingleResult();

        return new DashboardStats(
            calledToday: (int) $row['calledToday'],
            calledWeek: (int) $row['calledWeek'],
            calledMonth: (int) $row['calledMonth'],
            waitingToday: (int) $row['waitingToday'],
            waitingWeek: (int) $row['waitingWeek'],
            waitingMonth: (int) $row['waitingMonth'],
        );
    }
}
