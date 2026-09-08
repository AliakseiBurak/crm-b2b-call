<?php

namespace App\Service;

use App\Entity\Campaign;
use App\Entity\CampaignRecipient;
use App\Entity\Enum\CampaignStatus;
use App\Entity\Enum\UserRole;
use App\Entity\OrganizationGroup;
use App\Entity\User;
use App\Repository\CampaignRecipientRepository;
use App\Repository\OrganizationGroupRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

/**
 * Сервис управления адресатами рассылок (change organization-groups).
 */
final class CampaignRecipientService
{
    public function __construct(
        private readonly CampaignRecipientRepository $recipients,
        private readonly OrganizationGroupRepository $groups,
        private readonly OrganizationRepository $organizations,
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * Массовое добавление организаций из группы в рассылку.
     *
     * @return array{added: int, skipped: int}
     */
    public function bulkAddByGroup(Campaign $campaign, OrganizationGroup $group, User $user): array
    {
        // Verify campaign is not archived
        if (CampaignStatus::Archived === $campaign->status) {
            throw new \InvalidArgumentException('Адресаты недоступны для рассылки в статусе «В архиве»');
        }

        // Verify manager has access to group
        if (UserRole::Admin !== $user->role) {
            $accessibleGroups = $this->groups->findForManager($user);
            $hasAccess = false;
            foreach ($accessibleGroups as $accessibleGroup) {
                if ($accessibleGroup->id === $group->id) {
                    $hasAccess = true;
                    break;
                }
            }
            if (!$hasAccess) {
                throw new AccessDeniedHttpException('Группа вне области доступа');
            }
        }

        // Get organizations in group
        $organizations = [];
        foreach ($group->memberships as $membership) {
            $organizations[] = $membership->organization;
        }

        $added = 0;
        $skipped = 0;

        foreach ($organizations as $organization) {
            // Check if recipient already exists
            $existing = $this->recipients->findOneBy([
                'campaign' => $campaign,
                'organization' => $organization,
            ]);

            if (null !== $existing) {
                ++$skipped;
                continue;
            }

            $recipient = new CampaignRecipient($campaign, $organization);
            $this->em->persist($recipient);
            ++$added;
        }

        $this->em->flush();

        return ['added' => $added, 'skipped' => $skipped];
    }
}
