<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Enum\UserRole;
use App\Entity\OrgGroupMembership;
use App\Entity\Organization;
use App\Entity\OrganizationGroup;
use App\Entity\User;
use App\Tests\DatabaseWebTestCase;

final class GroupControllerTest extends DatabaseWebTestCase
{
    public function testManagerSeesOwnGroupsInList(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('My Group', $manager);
        $this->em()->persist($group);
        $this->em()->flush();

        $this->login($manager);
        $this->client->request('GET', '/groups');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'My Group');
    }

    public function testManagerSeesAssignedGroupsInList(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $group = $this->makeGroup('Assigned Group', $admin);
        $this->em()->persist($group);
        $this->em()->flush();

        $assignment = new \App\Entity\GroupAssignment($manager, $group);
        $this->em()->persist($assignment);
        $this->em()->flush();

        $this->login($manager);
        $this->client->request('GET', '/groups');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Assigned Group');
    }

    public function testManagerDoesNotSeeOtherManagerGroupsInList(): void
    {
        $manager1 = $this->makeUser('manager1@b2b-crm.loc', UserRole::Manager);
        $manager2 = $this->makeUser('manager2@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('Other Group', $manager2);
        $this->em()->persist($group);
        $this->em()->flush();

        $this->login($manager1);
        $this->client->request('GET', '/groups');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorNotTextContains('body', 'Other Group');
    }

    public function testManagerCreatesGroup(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $this->em()->flush();

        $this->login($manager);
        $this->client->request('GET', '/groups/new');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Создать', [
            'name' => 'Новая группа',
            'description' => 'Описание',
            'color' => '#ff0000',
        ]);

        $this->assertResponseRedirects('/groups');
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();

        $this->em()->clear();
        $group = $this->em()->getRepository(OrganizationGroup::class)->findOneBy(['name' => 'Новая группа']);
        self::assertNotNull($group);
        self::assertSame('Описание', $group->description);
        self::assertSame('#ff0000', $group->color);
        self::assertSame($manager->id, $group->createdBy->id);
    }

    public function testManagerCreatesGroupWithInvalidColorReturns422(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $this->em()->flush();

        $this->login($manager);
        $this->client->request('GET', '/groups/new');

        $this->client->submitForm('Создать', [
            'name' => 'Новая группа',
            'color' => 'not-a-color',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.field__error', 'Неверный формат цвета');
    }

    public function testManagerCreatesGroupWithBlankNameReturns422(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $this->em()->flush();

        $this->login($manager);
        $this->client->request('GET', '/groups/new');

        $this->client->submitForm('Создать', [
            'name' => '',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.field__error', 'Название обязательно');
    }

    public function testManagerEditsOwnGroup(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('My Group', $manager);
        $this->em()->persist($group);
        $this->em()->flush();
        $groupId = $group->id;

        $this->login($manager);
        $this->client->request('GET', '/groups/' . $groupId . '/edit');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Сохранить', [
            'name' => 'Updated Group',
        ]);

        $this->assertResponseRedirects('/groups');
        $this->em()->clear();
        $group = $this->em()->find(OrganizationGroup::class, $groupId);
        self::assertSame('Updated Group', $group->name);
    }

    public function testManagerCannotEditOtherManagerGroup(): void
    {
        $manager1 = $this->makeUser('manager1@b2b-crm.loc', UserRole::Manager);
        $manager2 = $this->makeUser('manager2@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('Other Group', $manager2);
        $this->em()->persist($group);
        $this->em()->flush();

        $this->login($manager1);
        $this->client->request('GET', '/groups/' . $group->id . '/edit');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testManagerDeletesOwnGroup(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('My Group', $manager);
        $this->em()->persist($group);
        $this->em()->flush();
        $groupId = $group->id;

        $this->login($manager);
        $this->client->request('GET', '/groups/' . $groupId . '/delete');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Удалить', []);

        $this->assertResponseRedirects('/groups');
        $this->em()->clear();
        self::assertNull($this->em()->find(OrganizationGroup::class, $groupId));
    }

    public function testManagerCannotDeleteOtherManagerGroup(): void
    {
        $manager1 = $this->makeUser('manager1@b2b-crm.loc', UserRole::Manager);
        $manager2 = $this->makeUser('manager2@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('Other Group', $manager2);
        $this->em()->persist($group);
        $this->em()->flush();

        $this->login($manager1);
        $this->client->request('GET', '/groups/' . $group->id . '/delete');

        $this->assertResponseStatusCodeSame(403);
    }

    public function testManagerAddsOrgsToGroupMembership(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('My Group', $manager);
        $this->em()->persist($group);

        $org1 = new Organization()->setName('Org1')->setIndustry('IT');
        $org2 = new Organization()->setName('Org2')->setIndustry('IT');
        $this->em()->persist($org1);
        $this->em()->persist($org2);

        $membership = new OrgGroupMembership($org1, $group);
        $this->em()->persist($membership);
        $this->em()->flush();
        $groupId = $group->id;

        $this->login($manager);
        $this->client->request('GET', '/groups/' . $groupId . '/members');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Сохранить', [
            'organizations' => [$org1->id, $org2->id],
        ]);

        $this->assertResponseRedirects('/groups');
        $this->em()->clear();

        $group = $this->em()->find(OrganizationGroup::class, $groupId);
        self::assertCount(2, $group->memberships);
    }

    public function testManagerRemovesOrgsFromGroupMembership(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('My Group', $manager);
        $this->em()->persist($group);

        $org1 = new Organization()->setName('Org1')->setIndustry('IT');
        $org2 = new Organization()->setName('Org2')->setIndustry('IT');
        $this->em()->persist($org1);
        $this->em()->persist($org2);

        $this->em()->persist(new OrgGroupMembership($org1, $group));
        $this->em()->persist(new OrgGroupMembership($org2, $group));
        $this->em()->flush();
        $groupId = $group->id;

        $this->login($manager);
        $this->client->request('GET', '/groups/' . $groupId . '/members');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Сохранить', [
            'organizations' => [$org1->id],
        ]);

        $this->assertResponseRedirects('/groups');
        $this->em()->clear();

        $group = $this->em()->find(OrganizationGroup::class, $groupId);
        self::assertCount(1, $group->memberships);
    }

    public function testManagerCannotAccessOtherManagerGroupMembers(): void
    {
        $manager1 = $this->makeUser('manager1@b2b-crm.loc', UserRole::Manager);
        $manager2 = $this->makeUser('manager2@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('Other Group', $manager2);
        $this->em()->persist($group);
        $this->em()->flush();

        $this->login($manager1);
        $this->client->request('GET', '/groups/' . $group->id . '/members');

        $this->assertResponseStatusCodeSame(403);
    }

    // --- Admin group management (task 3.4) ---

    public function testAdminSeesAllGroupsInList(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $ownGroup = $this->makeGroup('Manager Group', $manager);
        $adminGroup = $this->makeGroup('Admin Group', $admin);
        $this->em()->persist($ownGroup);
        $this->em()->persist($adminGroup);
        $this->em()->flush();

        $this->login($admin);
        $this->client->request('GET', '/groups');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Manager Group');
        $this->assertSelectorTextContains('body', 'Admin Group');
    }

    public function testAdminCanEditManagerGroup(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('Manager Group', $manager);
        $this->em()->persist($group);
        $this->em()->flush();
        $groupId = $group->id;

        $this->login($admin);
        $this->client->request('GET', '/groups/' . $groupId . '/edit');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Сохранить', [
            'name' => 'Renamed by Admin',
        ]);

        $this->assertResponseRedirects('/groups');
        $this->em()->clear();
        $group = $this->em()->find(OrganizationGroup::class, $groupId);
        self::assertSame('Renamed by Admin', $group->name);
    }

    public function testAdminCanDeleteManagerGroup(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('Manager Group', $manager);
        $this->em()->persist($group);
        $this->em()->flush();
        $groupId = $group->id;

        $this->login($admin);
        $this->client->request('GET', '/groups/' . $groupId . '/delete');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Удалить', []);

        $this->assertResponseRedirects('/groups');
        $this->em()->clear();
        self::assertNull($this->em()->find(OrganizationGroup::class, $groupId));
    }

    public function testAdminCreatesGroup(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $this->em()->flush();

        $this->login($admin);
        $this->client->request('GET', '/groups/new');

        $this->client->submitForm('Создать', [
            'name' => 'Admin Group',
            'description' => 'Created by admin',
            'color' => '#00ff00',
        ]);

        $this->assertResponseRedirects('/groups');
        $this->em()->clear();
        $group = $this->em()->getRepository(OrganizationGroup::class)->findOneBy(['name' => 'Admin Group']);
        self::assertNotNull($group);
        self::assertSame($admin->id, $group->createdBy->id);
    }

    public function testAdminCanAccessManagerGroupMembers(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('Manager Group', $manager);
        $this->em()->persist($group);

        $org = new Organization()->setName('Org1')->setIndustry('IT');
        $this->em()->persist($org);
        $this->em()->persist(new OrgGroupMembership($org, $group));
        $this->em()->flush();

        $this->login($admin);
        $this->client->request('GET', '/groups/' . $group->id . '/members');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('body', 'Org1');
    }

    public function testAdminCanAddOrgsToManagerGroup(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $group = $this->makeGroup('Manager Group', $manager);
        $this->em()->persist($group);

        $org1 = new Organization()->setName('Org1')->setIndustry('IT');
        $org2 = new Organization()->setName('Org2')->setIndustry('IT');
        $this->em()->persist($org1);
        $this->em()->persist($org2);
        $this->em()->flush();
        $groupId = $group->id;

        $this->login($admin);
        $this->client->request('GET', '/groups/' . $groupId . '/members');
        $this->assertResponseIsSuccessful();

        $this->client->submitForm('Сохранить', [
            'organizations' => [$org1->id, $org2->id],
        ]);

        $this->assertResponseRedirects('/groups');
        $this->em()->clear();

        $group = $this->em()->find(OrganizationGroup::class, $groupId);
        self::assertCount(2, $group->memberships);
    }

    public function testAdminGroupListHeading(): void
    {
        $admin = $this->makeUser('admin@b2b-crm.loc', UserRole::Admin);
        $this->em()->flush();

        $this->login($admin);
        $this->client->request('GET', '/groups');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Группы');
    }

    public function testManagerGroupListHeading(): void
    {
        $manager = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $this->em()->flush();

        $this->login($manager);
        $this->client->request('GET', '/groups');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Мои группы');
    }

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

    private function makeGroup(string $name, User $createdBy): OrganizationGroup
    {
        return (new OrganizationGroup())
            ->setName($name)
            ->setCreatedBy($createdBy);
    }
}
