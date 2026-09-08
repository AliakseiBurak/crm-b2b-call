<?php

namespace App\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\OrgGroupMembership;
use App\Entity\Organization;
use App\Entity\OrganizationGroup;
use App\Repository\OrganizationGroupRepository;
use App\Repository\OrganizationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/groups')]
class GroupController extends AbstractController
{
    public function __construct(
        private readonly OrganizationGroupRepository $groups,
        private readonly OrganizationRepository $organizations,
        private readonly EntityManagerInterface $em,
        private readonly ValidatorInterface $validator,
    ) {
    }

    #[Route('', name: 'app_group_list', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function list(): Response
    {
        $user = $this->getUser();
        $groups = (UserRole::Admin === $user->role)
            ? $this->groups->findAllGroups()
            : $this->groups->findForManager($user);

        return $this->render('group/list.html.twig', [
            'groups' => $groups,
        ]);
    }

    #[Route('/new', name: 'app_group_new', methods: ['GET'])]
    #[IsGranted('ROLE_MANAGER')]
    public function new(): Response
    {
        return $this->render('group/form.html.twig', [
            'group' => null,
            'errors' => [],
        ]);
    }

    #[Route('/new', name: 'app_group_create', methods: ['POST'])]
    #[IsGranted('ROLE_MANAGER')]
    public function create(Request $request): Response
    {
        $this->assertCsrfToken($request);

        $name = trim((string) $request->request->get('name', ''));
        $description = trim((string) $request->request->get('description', '')) ?: null;
        $color = trim((string) $request->request->get('color', '')) ?: null;

        $group = new OrganizationGroup();
        $group->setName($name);
        $group->setDescription($description);
        $group->setColor($color);

        $errors = [];
        if ('' === $name) {
            $errors['name'] = 'Название обязательно';
        }
        foreach ($this->validator->validate($group) as $violation) {
            if ('color' === $violation->getPropertyPath()) {
                $errors['color'] = $violation->getMessage();
            }
        }

        if ([] !== $errors) {
            return $this->render('group/form.html.twig', [
                'group' => null,
                'errors' => $errors,
                'name' => $name,
                'description' => $description,
                'color' => $color,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $group->setCreatedBy($this->getUser());

        $this->em->persist($group);
        $this->em->flush();

        return $this->redirectToRoute('app_group_list');
    }

    #[Route('/{id}/edit', name: 'app_group_edit', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MANAGER')]
    public function edit(int $id): Response
    {
        $group = $this->accessibleGroup($id);

        return $this->render('group/form.html.twig', [
            'group' => $group,
            'errors' => [],
        ]);
    }

    #[Route('/{id}/edit', name: 'app_group_update', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MANAGER')]
    public function update(int $id, Request $request): Response
    {
        $group = $this->accessibleGroup($id);
        $this->assertCsrfToken($request);

        $name = trim((string) $request->request->get('name', ''));
        $description = trim((string) $request->request->get('description', '')) ?: null;
        $color = trim((string) $request->request->get('color', '')) ?: null;

        $errors = [];
        if ('' === $name) {
            $errors['name'] = 'Название обязательно';
        }

        $group->setName($name);
        $group->setDescription($description);
        $group->setColor($color);

        foreach ($this->validator->validate($group) as $violation) {
            if ('color' === $violation->getPropertyPath()) {
                $errors['color'] = $violation->getMessage();
            }
        }

        if ([] !== $errors) {
            $this->em->clear(OrganizationGroup::class);

            return $this->render('group/form.html.twig', [
                'group' => $group,
                'errors' => $errors,
                'name' => $name,
                'description' => $description,
                'color' => $color,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $this->em->flush();

        return $this->redirectToRoute('app_group_list');
    }

    #[Route('/{id}/delete', name: 'app_group_delete', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MANAGER')]
    public function delete(int $id): Response
    {
        $group = $this->accessibleGroup($id);

        return $this->render('group/delete.html.twig', [
            'group' => $group,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_group_remove', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MANAGER')]
    public function remove(int $id, Request $request): Response
    {
        $group = $this->accessibleGroup($id);
        $this->assertCsrfToken($request);

        $this->em->remove($group);
        $this->em->flush();

        return $this->redirectToRoute('app_group_list');
    }

    #[Route('/{id}/members', name: 'app_group_members', methods: ['GET'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MANAGER')]
    public function members(int $id): Response
    {
        $group = $this->accessibleGroup($id);

        $memberIds = array_map(
            static fn (OrgGroupMembership $m): int => $m->organization->id,
            $group->memberships->toArray()
        );

        $organizations = $this->organizations->findAccessibleOrganizations($this->getUser());

        return $this->render('group/members.html.twig', [
            'group' => $group,
            'organizations' => $organizations,
            'memberIds' => $memberIds,
        ]);
    }

    #[Route('/{id}/members', name: 'app_group_update_members', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_MANAGER')]
    public function updateMembers(int $id, Request $request): Response
    {
        $group = $this->accessibleGroup($id);
        $this->assertCsrfToken($request);

        $selectedIds = array_map('intval', $request->request->all('organizations'));

        // Менеджер может добавлять в группу только организации своей области
        // доступа (ADR-0007); администратору доступны все (ADR-0008).
        $accessibleIds = $this->organizations->findAccessibleIds($this->getUser());
        if (null !== $accessibleIds) {
            $selectedIds = array_values(array_intersect($selectedIds, $accessibleIds));
        }

        // Remove existing memberships not in selection
        foreach ($group->memberships as $membership) {
            if (!in_array($membership->organization->id, $selectedIds, true)) {
                $group->memberships->removeElement($membership);
                $this->em->remove($membership);
            }
        }

        // Add new memberships
        foreach ($selectedIds as $orgId) {
            $alreadyMember = false;
            foreach ($group->memberships as $existing) {
                if ($existing->organization->id === $orgId) {
                    $alreadyMember = true;
                    break;
                }
            }

            if (!$alreadyMember) {
                $organization = $this->organizations->find($orgId);
                if (null !== $organization) {
                    $membership = new OrgGroupMembership($organization, $group);
                    $this->em->persist($membership);
                }
            }
        }

        $this->em->flush();

        return $this->redirectToRoute('app_group_list');
    }

    private function accessibleGroup(int $id): OrganizationGroup
    {
        $group = $this->groups->find($id);
        if (null === $group) {
            throw $this->createNotFoundException('Группа не найдена');
        }

        $user = $this->getUser();

        // Admin can access all groups
        if ($user instanceof \App\Entity\User && UserRole::Admin === $user->role) {
            return $group;
        }

        // Manager can only access own groups
        if ($group->createdBy && $group->createdBy->id === $user->id) {
            return $group;
        }

        throw new AccessDeniedHttpException('Группа вне области доступа');
    }

    private function assertCsrfToken(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-Token') ?? (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('group', $token)) {
            throw new AccessDeniedHttpException('Недействительный CSRF-токен');
        }
    }
}
