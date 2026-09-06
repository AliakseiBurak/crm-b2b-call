<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\GroupType;
use App\Entity\Enum\UserRole;
use App\Entity\OrganizationGroup;
use App\Entity\User;
use App\Repository\UserRepository;
use App\Tests\DatabaseWebTestCase;

/**
 * Функциональные тесты UserController (change add-new-user):
 * создание, удаление, список пользователей, проверка доступа (ADR-0008),
 * каскадное удаление персональной группы менеджера (ADR-0005).
 */
final class UserControllerTest extends DatabaseWebTestCase
{
    // --- Create ---

    public function testAdminCreatesUserWithAllFields(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => 'maria@example.com',
            'name' => 'Мария',
            'surname' => 'Смирнова',
            'role' => 'manager',
        ]);

        $this->assertResponseRedirects('/admin/users');

        $this->em()->clear();
        $user = $this->findUser('maria@example.com');
        self::assertNotNull($user);
        self::assertSame('Мария', $user->name);
        self::assertSame('Смирнова', $user->surname);
        self::assertSame(UserRole::Manager, $user->role);
    }

    public function testAdminCreatesUserWithoutNameAndSurname(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => 'ivan@example.com',
            'name' => '',
            'surname' => '',
            'role' => 'manager',
        ]);

        $this->assertResponseRedirects('/admin/users');

        $this->em()->clear();
        $user = $this->findUser('ivan@example.com');
        self::assertNotNull($user);
        self::assertNull($user->name);
        self::assertNull($user->surname);
    }

    public function testCreateManagerCreatesPersonalGroup(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => 'manager@example.com',
            'name' => 'Иван',
            'surname' => 'Петров',
            'role' => 'manager',
        ]);

        $this->assertResponseRedirects('/admin/users');

        $this->em()->clear();
        $user = $this->findUser('manager@example.com');
        self::assertNotNull($user);

        $group = $this->em()->getRepository(OrganizationGroup::class)
            ->findOneBy(['slug' => 'user-' . $user->id . '-group']);
        self::assertNotNull($group, 'Персональная группа создаётся для менеджера');
        self::assertSame(GroupType::User, $group->type);
        self::assertSame($user->id, $group->ownerUser->id);
    }

    public function testCreateAdminDoesNotCreatePersonalGroup(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => 'newadmin@example.com',
            'name' => '',
            'surname' => '',
            'role' => 'admin',
        ]);

        $this->assertResponseRedirects('/admin/users');

        $this->em()->clear();
        $user = $this->findUser('newadmin@example.com');
        self::assertNotNull($user);

        $group = $this->em()->getRepository(OrganizationGroup::class)
            ->findOneBy(['slug' => 'user-' . $user->id . '-group']);
        self::assertNull($group, 'Персональная группа не создаётся для admin');
    }

    public function testCreateWithMissingEmailShowsError(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => '',
            'name' => '',
            'surname' => '',
            'role' => 'manager',
        ]);

        $this->assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->em()->getRepository(User::class)->count([]));
    }

    public function testCreateWithDuplicateEmailShowsError(): void
    {
        $this->makeUser('existing@example.com', UserRole::Manager);
        $this->em()->flush();

        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => 'existing@example.com',
            'name' => '',
            'surname' => '',
            'role' => 'manager',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.field__error', 'уже существует');
    }

    public function testCreateWithMissingRoleShowsError(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => 'test@example.com',
            'name' => '',
            'surname' => '',
            'role' => '',
        ]);

        $this->assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->em()->getRepository(User::class)->count([]));
    }

    public function testCreateWithInvalidRoleShowsError(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => 'test@example.com',
            'name' => '',
            'surname' => '',
            'role' => 'supervisor',
        ]);

        $this->assertResponseStatusCodeSame(422);
        self::assertSame(0, $this->em()->getRepository(User::class)->count([]));
    }

    public function testCreateWithInvalidEmailFormatShowsError(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));
        $this->open('/admin/users/new');
        $this->submitFormByButton('Создать', [
            'email' => 'not-an-email',
            'name' => '',
            'surname' => '',
            'role' => 'manager',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.field__error', 'Некорректный формат email');
        self::assertSame(0, $this->em()->getRepository(User::class)->count([]));
    }

    // --- Delete ---

    public function testAdminDeletesManagerAndPersonalGroupIsDeleted(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $this->em()->flush();

        $group = new OrganizationGroup()
            ->setName('Личная группа manager')
            ->setSlug('user-' . $manager->id . '-group')
            ->setType(GroupType::User)
            ->setOwnerUser($manager);
        $this->em()->persist($group);
        $this->em()->flush();

        $groupId = $group->id;
        $managerId = $manager->id;

        $this->login($admin);
        $this->open('/admin/users/' . $managerId . '/delete');
        $this->submitFormByButton('Удалить', []);

        $this->assertResponseRedirects('/admin/users');

        $this->em()->clear();
        self::assertNull($this->em()->find(User::class, $managerId));
        self::assertNull($this->em()->find(OrganizationGroup::class, $groupId),
            'Персональная группа менеджера удаляется каскадно');
    }

    public function testAdminDeletesAdminNoGroupDeleted(): void
    {
        $admin1 = $this->makeUser('admin1@b2b-crm.loc', UserRole::Admin);
        $admin2 = $this->makeUser('admin2@b2b-crm.loc', UserRole::Admin);
        $this->em()->flush();

        $admin2Id = $admin2->id;

        $this->login($admin1);
        $this->open('/admin/users/' . $admin2Id . '/delete');
        $this->submitFormByButton('Удалить', []);

        $this->assertResponseRedirects('/admin/users');

        $this->em()->clear();
        self::assertNull($this->em()->find(User::class, $admin2Id));
    }

    public function testAdminCannotDeleteSelf(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $this->em()->flush();

        $adminId = $admin->id;

        $this->login($admin);
        $this->open('/admin/users/' . $adminId . '/delete');
        $this->submitFormByButton('Удалить', []);

        $this->assertResponseStatusCodeSame(403);

        $this->em()->clear();
        self::assertNotNull($this->em()->find(User::class, $adminId));
    }

    // --- List ---

    public function testAdminSeesAllUsersInList(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $this->em()->flush();

        $this->login($admin);
        $this->open('/admin/users');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Пользователи');
        $this->assertSelectorTextContains('body', 'admin@b2b-crm.loc');
        $this->assertSelectorTextContains('body', 'manager@b2b-crm.loc');
    }

    public function testDeleteButtonMissingForCurrentUser(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $this->em()->flush();

        $this->login($admin);
        $this->open('/admin/users');

        $this->assertResponseIsSuccessful();
        // Кнопка удаления отсутствует для текущего пользователя.
        $this->assertSelectorNotExists('a[href="/admin/users/' . $admin->id . '/delete"]');
    }

    // --- Access Control ---

    public function testManagerCannotAccessUserList(): void
    {
        $this->login($this->makeUser('manager@b2b-crm.loc', UserRole::Manager));
        $this->client->request('GET', '/admin/users');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testManagerCannotAccessCreateForm(): void
    {
        $this->login($this->makeUser('manager@b2b-crm.loc', UserRole::Manager));
        $this->client->request('GET', '/admin/users/new');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testManagerCannotDeleteUser(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $target = $this->makeUser('target@b2b-crm.loc', UserRole::Manager);
        $this->em()->flush();

        $this->login($manager);
        $this->client->request('GET', '/admin/users/' . $target->id . '/delete');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testAnonymousCannotAccessUserPages(): void
    {
        $this->client->request('GET', '/admin/users');
        $this->assertResponseRedirects('/login');

        $this->client->request('GET', '/admin/users/new');
        $this->assertResponseRedirects('/login');
    }

    // --- Helpers ---

    private function makeUser(string $email, UserRole $role): User
    {
        $user = new User()
            ->setEmail($email)
            ->setRole($role);
        $user->setPassword('test-password-hash');
        $this->em()->persist($user);
        $this->em()->flush();

        return $user;
    }

    private function findUser(string $email): ?User
    {
        return $this->em()->getRepository(User::class)->findOneBy(['email' => $email]);
    }
}
