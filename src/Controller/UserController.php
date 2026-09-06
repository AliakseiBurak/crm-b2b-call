<?php

namespace App\Controller;

use App\Dto\CreateUserRequest;
use App\Entity\Enum\GroupType;
use App\Entity\Enum\UserRole;
use App\Entity\OrganizationGroup;
use App\Entity\User;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route('/admin/users')]
#[IsGranted('ROLE_ADMIN')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('', name: 'app_user_list', methods: ['GET'])]
    public function list(): Response
    {
        return $this->render('user/list.html.twig', [
            'users' => $this->users->findAdminsAndManagers(),
        ]);
    }

    #[Route('/new', name: 'app_user_new', methods: ['GET'])]
    public function new(): Response
    {
        return $this->render('user/form.html.twig', [
            'request' => new CreateUserRequest(),
            'errors' => [],
        ]);
    }

    #[Route('/new', name: 'app_user_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): Response
    {
        $this->assertCsrfToken($request);

        $createRequest = new CreateUserRequest();
        $createRequest->email = trim((string) $request->request->get('email', ''));
        $createRequest->name = trim((string) $request->request->get('name', '')) ?: null;
        $createRequest->surname = trim((string) $request->request->get('surname', '')) ?: null;
        $createRequest->role = (string) $request->request->get('role', '');

        $violations = $validator->validate($createRequest);
        $errors = [];
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] ??= $violation->getMessage();
        }

        if (null === $createRequest->email || '' === $createRequest->email) {
            $errors['email'] ??= 'Email обязателен для заполнения';
        }

        if (null === $createRequest->role || '' === $createRequest->role) {
            $errors['role'] ??= 'Роль обязательна для заполнения';
        }

        if ([] === $errors) {
            $existing = $this->users->findOneBy(['email' => $createRequest->email]);
            if (null !== $existing) {
                $errors['email'] = 'Пользователь с таким email уже существует';
            }
        }

        if ([] !== $errors) {
            return $this->render('user/form.html.twig', [
                'request' => $createRequest,
                'errors' => $errors,
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $role = UserRole::from($createRequest->role);

        $this->em->wrapInTransaction(function () use ($createRequest, $role): void {
            $user = new User()
                ->setEmail($createRequest->email)
                ->setRole($role);
            $user->setPassword(''); // Пароль не задаётся при создании

            if (null !== $createRequest->name) {
                $user->setName($createRequest->name);
            }
            if (null !== $createRequest->surname) {
                $user->setSurname($createRequest->surname);
            }

            $this->em->persist($user);
            $this->em->flush();

            if (UserRole::Manager === $role) {
                $group = new OrganizationGroup()
                    ->setName('Личная группа ' . $user->email)
                    ->setSlug('user-' . $user->id . '-group')
                    ->setType(GroupType::User)
                    ->setOwnerUser($user);
                $this->em->persist($group);
                $this->em->flush();
            }
        });

        return $this->redirectToRoute('app_user_list');
    }

    #[Route('/{id}/delete', name: 'app_user_delete', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function delete(int $id): Response
    {
        $user = $this->users->find($id);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        return $this->render('user/delete.html.twig', [
            'user' => $user,
        ]);
    }

    #[Route('/{id}/delete', name: 'app_user_remove', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function remove(int $id, Request $request): Response
    {
        $this->assertCsrfToken($request);

        $user = $this->users->find($id);
        if (null === $user) {
            throw $this->createNotFoundException('Пользователь не найден');
        }

        /** @var User $currentUser */
        $currentUser = $this->getUser();
        if ($currentUser->id === $user->id) {
            throw new AccessDeniedHttpException('Нельзя удалить самого себя');
        }

        $this->em->wrapInTransaction(function () use ($user): void {
            $this->em->remove($user);
        });

        return $this->redirectToRoute('app_user_list');
    }

    private function assertCsrfToken(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-Token') ?? (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('user', $token)) {
            throw new AccessDeniedHttpException('Недействительный CSRF-токен');
        }
    }
}
