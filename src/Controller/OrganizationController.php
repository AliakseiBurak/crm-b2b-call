<?php

namespace App\Controller;

use App\Entity\Enum\GroupType;
use App\Entity\Enum\UserRole;
use App\Entity\OrgGroupMembership;
use App\Entity\Organization;
use App\Entity\OrganizationGroup;
use App\Entity\User;
use App\Repository\CampaignRecipientRepository;
use App\Repository\OrganizationGroupRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(requirements: ['id' => '\d+'])]
class OrganizationController extends AbstractController
{
    public function __construct(
        private readonly OrganizationRepository $organizations,
        private readonly OrganizationGroupRepository $groups,
        private readonly CampaignRecipientRepository $campaignRecipients,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/organizations/new', name: 'app_organization_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('organization/form.html.twig', [
            'organization' => null,
            'errors' => [],
        ]);
    }

    #[Route('/organizations/new', name: 'app_organization_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): Response
    {
        $this->assertCsrfToken($request);

        $organization = new Organization();
        $errors = $this->applyRequest($request, $validator, $organization);
        if ([] !== $errors) {
            return $this->render('organization/form.html.twig', [
                'organization' => $organization,
                'errors' => $errors,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $this->em->persist($organization);

        // Менеджер: организация попадает в личную группу user-<id>-group
        // (ADR-0005). У администратора личной группы нет (ADR-0008).
        $user = $this->getUser();
        if ($user instanceof User && UserRole::Manager === $user->role) {
            $this->em->persist(new OrgGroupMembership($organization, $this->personalGroup($user)));
        }

        $this->em->flush();

        return $this->redirectToRoute('app_dashboard', ['highlight' => $organization->id]);
    }

    #[Route('/organizations/{id}/edit', name: 'app_organization_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        $organization = $this->accessibleOrganization($id);

        return $this->render('organization/form.html.twig', [
            'organization' => $organization,
            'errors' => [],
            'errorRecipients' => $this->campaignRecipients->findErrorRecipientsForOrganization($organization),
        ]);
    }

    #[Route('/organizations/{id}/edit', name: 'app_organization_update', methods: ['POST'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): Response
    {
        $organization = $this->accessibleOrganization($id);
        $ajax = $request->isXmlHttpRequest();

        // Токен приходит из формы (FormData) или заголовком X-CSRF-Token.
        $this->assertCsrfToken($request);

        $errors = $this->applyRequest($request, $validator, $organization);
        if ([] !== $errors) {
            if ($ajax) {
                return $this->json(['ok' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            return $this->render('organization/form.html.twig', [
                'organization' => $organization,
                'errors' => $errors,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        // Сохранение изменений — updatedAt обновляется вручную (авто-таймстампов нет).
        $organization->touch();
        $this->em->flush();

        if ($ajax) {
            return $this->json([
                'ok' => true,
                'organization' => [
                    'id' => $organization->id,
                    'name' => $organization->name,
                    'industry' => $organization->industry,
                ],
            ]);
        }

        return $this->redirectToRoute('app_dashboard', ['highlight' => $organization->id]);
    }

    #[Route('/organizations/{id}/delete', name: 'app_organization_delete', methods: ['GET'])]
    public function delete(int $id): Response
    {
        $organization = $this->accessibleOrganization($id);

        return $this->render('organization/delete.html.twig', [
            'organization' => $organization,
        ]);
    }

    #[Route('/organizations/{id}/delete', name: 'app_organization_remove', methods: ['POST'])]
    public function remove(int $id, Request $request): Response
    {
        $organization = $this->accessibleOrganization($id);
        $this->assertCsrfToken($request);

        // Каскадное удаление контактов и звонков обеспечивает БД
        // (FK organization_id ON DELETE CASCADE).
        $this->em->remove($organization);
        $this->em->flush();

        return $this->redirectToRoute('app_dashboard');
    }

    /**
     * Заполняет организацию данными формы и возвращает ошибки валидации,
     * сгруппированные по полям (name, industry).
     *
     * @return array<string, string>
     */
    private function applyRequest(Request $request, ValidatorInterface $validator, Organization $organization): array
    {
        $organization->setName(trim((string) $request->request->get('name', '')));
        $organization->setIndustry(trim((string) $request->request->get('industry', '')));

        $violations = $validator->validate($organization);

        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] ??= $violation->getMessage();
        }

        return $errors;
    }

    /**
     * Организация в области доступа пользователя: менеджеру — только
     * организации личной и назначенных групп (ADR-0007), администратору —
     * все (ADR-0008, группы не проверяются).
     */
    private function accessibleOrganization(int $id): Organization
    {
        $organization = $this->organizations->find($id);
        if (null === $organization) {
            throw $this->createNotFoundException('Организация не найдена');
        }

        $accessibleIds = $this->organizations->findAccessibleIds($this->getUser());
        if (null !== $accessibleIds && !\in_array($id, $accessibleIds, true)) {
            throw new AccessDeniedHttpException('Организация вне области доступа');
        }

        return $organization;
    }

    /**
     * Личная группа менеджера user-<id>-group; создаётся автоматически
     * при регистрации менеджера, здесь — страховка на случай отсутствия.
     */
    private function personalGroup(User $user): OrganizationGroup
    {
        $slug = 'user-' . $user->id . '-group';
        $group = $this->groups->findOneBy(['slug' => $slug]);
        if (null !== $group) {
            return $group;
        }

        $group = new OrganizationGroup()
            ->setName('Личная группа ' . $user->email)
            ->setSlug($slug)
            ->setType(GroupType::User);
        $this->em->persist($group);

        return $group;
    }

    /**
     * Защита state-changing форм от CSRF; для AJAX-запросов токен передаётся
     * в заголовке X-CSRF-Token.
     */
    private function assertCsrfToken(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-Token') ?? (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('organization', $token)) {
            throw new AccessDeniedHttpException('Недействительный CSRF-токен');
        }
    }
}
