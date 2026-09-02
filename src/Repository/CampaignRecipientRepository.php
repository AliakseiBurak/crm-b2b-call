<?php

namespace App\Repository;

use App\Entity\CampaignRecipient;
use App\Entity\Enum\CampaignStatus;
use App\Entity\Enum\RecipientStatus;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CampaignRecipient>
 */
class CampaignRecipientRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CampaignRecipient::class);
    }

    /**
     * @return list<int>
     */
    public function findPendingIdsForSend(int $limit): array
    {
        return $this->idsForSend($limit, RecipientStatus::Pending);
    }

    /**
     * Failed-получатели, готовые к повторной попытке (retry_at <= NOW, retry_count < 3).
     *
     * @return list<int>
     */
    public function findRetryIdsForSend(int $limit): array
    {
        /** @var list<int> $ids */
        $ids = $this->createQueryBuilder('cr')
            ->select('cr.id')
            ->innerJoin('cr.campaign', 'c')
            ->where('c.status = :launched')
            ->andWhere('cr.status = :failed')
            ->andWhere('cr.retryAt <= :now')
            ->andWhere('cr.retryCount < 3')
            ->setParameter('launched', CampaignStatus::Launched->value)
            ->setParameter('failed', RecipientStatus::Failed->value)
            ->setParameter('now', new \DateTimeImmutable())
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map(intval(...), $ids);
    }

    public function countStillProcessing(int $campaignId): int
    {
        return (int) $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->where('IDENTITY(cr.campaign) = :campaignId')
            ->andWhere(
                'cr.status IN (:open) OR (cr.status = :failed AND cr.retryCount < 3 AND cr.retryAt IS NOT NULL)',
            )
            ->setParameter('campaignId', $campaignId)
            ->setParameter('open', [
                RecipientStatus::Pending->value,
                RecipientStatus::Sending->value,
            ])
            ->setParameter('failed', RecipientStatus::Failed->value)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countDeliveredOrOpened(int $campaignId): int
    {
        return (int) $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->where('IDENTITY(cr.campaign) = :campaignId')
            ->andWhere('cr.status IN (:ok)')
            ->setParameter('campaignId', $campaignId)
            ->setParameter('ok', [
                RecipientStatus::Delivered->value,
                RecipientStatus::Opened->value,
            ])
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countByCampaign(int $campaignId): int
    {
        return (int) $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->where('IDENTITY(cr.campaign) = :campaignId')
            ->setParameter('campaignId', $campaignId)
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function countNoEmailFailures(int $campaignId): int
    {
        return (int) $this->createQueryBuilder('cr')
            ->select('COUNT(cr.id)')
            ->where('IDENTITY(cr.campaign) = :campaignId')
            ->andWhere('cr.status = :failed')
            ->andWhere('cr.errorMessage LIKE :prefix')
            ->setParameter('campaignId', $campaignId)
            ->setParameter('failed', RecipientStatus::Failed->value)
            ->setParameter('prefix', 'Отсутствует email%')
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return list<int>
     */
    private function idsForSend(int $limit, RecipientStatus $status): array
    {
        /** @var list<int> $ids */
        $ids = $this->createQueryBuilder('cr')
            ->select('cr.id')
            ->innerJoin('cr.campaign', 'c')
            ->where('c.status = :launched')
            ->andWhere('cr.status = :status')
            ->setParameter('launched', CampaignStatus::Launched->value)
            ->setParameter('status', $status->value)
            ->setMaxResults($limit)
            ->getQuery()
            ->getSingleColumnResult();

        return array_map(intval(...), $ids);
    }
}
