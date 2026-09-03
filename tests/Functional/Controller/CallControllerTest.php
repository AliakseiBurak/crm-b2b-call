<?php

namespace App\Tests\Functional\Controller;

use App\Entity\Call;
use App\Entity\Contact;
use App\Entity\Enum\GroupType;
use App\Entity\Enum\UserRole;
use App\Entity\OrgGroupMembership;
use App\Entity\Organization;
use App\Entity\OrganizationGroup;
use App\Entity\User;
use App\Tests\DatabaseWebTestCase;

/**
 * Функциональные тесты CallController (change calls-crud):
 * CRUD с проверкой области доступа (ADR-0005–0008), привязка звонка к
 * организации, валидация на сервере с сообщениями на русском, фиксация
 * факта и результата звонка, AJAX-обновление строки на панели без
 * перезагрузки страницы, удаление с подтверждением, динамическая загрузка
 * контактов организации.
 */
final class CallControllerTest extends DatabaseWebTestCase
{
    public function testAdminCreatesCallBoundToOrganization(): void
    {
        [$organization, $contact] = $this->makeOrganizationWithContact('ООО Ромашка');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $crawler = $this->open('/organizations/' . $organization->id . '/calls/new');
        self::assertSelectorTextContains('h1', 'Новый звонок');
        // Организация предвыбрана из ссылки «Добавить звонок» (задача 3.4).
        $selected = $crawler->filter('select[name="organization"] option[selected]');
        self::assertCount(1, $selected);
        self::assertSame((string) $organization->id, $selected->attr('value'));

        $scheduledAt = new \DateTimeImmutable('+5 days')->setTime(10, 30);
        $this->submitFormByButton('Создать', [
            'organization' => (string) $organization->id,
            'scheduled_at' => $scheduledAt->format('Y-m-d\TH:i'),
            'contact' => (string) $contact->id,
            'notes' => 'Обсудить курсы',
        ]);

        $this->assertResponseRedirects();
        $this->client->followRedirect();
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h1', 'Панель');

        $this->em()->clear();
        $call = $this->findCall('Обсудить курсы');
        self::assertNotNull($call);
        // Звонок привязан к организации при создании (задача 7.4).
        self::assertSame('ООО Ромашка', $call->organization->name);
        self::assertSame('Иван Петров', $call->contact->name);
        self::assertSame($scheduledAt->format('Y-m-d H:i'), $call->scheduledAt->format('Y-m-d H:i'));
        self::assertNull($call->madeAt);
        self::assertFalse($call->isDeal);
    }

    public function testCreateWithActualDateOnlySavesCompletedCall(): void
    {
        [$organization, $contact] = $this->makeOrganizationWithContact('ООО Ромашка');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/organizations/' . $organization->id . '/calls/new');
        // Запланированная дата опциональна: проведённый звонок фиксируется
        // только фактической датой (change call-scheduled-date-optional).
        $this->submitFormByButton('Создать', [
            'organization' => (string) $organization->id,
            'scheduled_at' => '',
            'contact' => (string) $contact->id,
            'made_at' => '24.08.2026 15:30',
        ]);

        $this->assertResponseRedirects();

        $this->em()->clear();
        $call = $this->findOrganizationCall($organization);
        self::assertNotNull($call);
        self::assertNull($call->scheduledAt);
        self::assertSame('2026-08-24 15:30', $call->madeAt->format('Y-m-d H:i'));
    }

    public function testCreateWithoutAnyDatesSavesCall(): void
    {
        $organization = $this->makeOrganization('ООО Ромашка');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/organizations/' . $organization->id . '/calls/new');
        $this->submitFormByButton('Создать', [
            'organization' => (string) $organization->id,
            'scheduled_at' => '',
        ]);

        $this->assertResponseRedirects();

        $this->em()->clear();
        $call = $this->findOrganizationCall($organization);
        self::assertNotNull($call);
        self::assertNull($call->scheduledAt);
        self::assertNull($call->madeAt);
    }

    public function testCreateValidationErrorRestoresEnteredValues(): void
    {
        $organization = $this->makeOrganization('ООО Ромашка');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/organizations/' . $organization->id . '/calls/new');
        $this->submitFormByButton('Создать', [
            'organization' => (string) $organization->id,
            'scheduled_at' => '20.08.2020 10:00',
            'made_at' => '24.08.2026 15:30',
            'notes' => 'Черновик звонка',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.field__error', 'Запланированная дата звонка не может быть в прошлом');

        $crawler = $this->client->getCrawler();
        // Запланированная дата восстанавливается в формате поля (без времени)
        self::assertSame('20.08.2020', $crawler->filter('#scheduled_at')->attr('value'));
        self::assertSame('24.08.2026 15:30', $crawler->filter('#made_at')->attr('value'));
        self::assertSame('Черновик звонка', trim((string) $crawler->filter('#notes')->text()));

        $this->em()->clear();
        self::assertNull($this->findOrganizationCall($organization));
    }

    public function testCreateWithFutureActualDateShowsRussianErrorAndRestoresValues(): void
    {
        $organization = $this->makeOrganization('ООО Ромашка');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $scheduledAt = new \DateTimeImmutable('+5 days')->format('d.m.Y');
        $this->open('/organizations/' . $organization->id . '/calls/new');
        $this->submitFormByButton('Создать', [
            'organization' => (string) $organization->id,
            'scheduled_at' => $scheduledAt,
            'made_at' => '01.01.2027 10:00',
            'notes' => 'Черновик встречи',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.field__error', 'Фактическая дата звонка не может быть в будущем');

        // Ошибка фактической даты не теряет значения остальных полей
        $crawler = $this->client->getCrawler();
        self::assertSame($scheduledAt, $crawler->filter('#scheduled_at')->attr('value'));
        self::assertSame('01.01.2027 10:00', $crawler->filter('#made_at')->attr('value'));
        self::assertSame('Черновик встречи', trim((string) $crawler->filter('#notes')->text()));

        $this->em()->clear();
        self::assertNull($this->findOrganizationCall($organization));
    }

    public function testCreateWithoutOrganizationShowsRussianError(): void
    {
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/calls/new');
        $this->submitFormByButton('Создать', [
            'organization' => '',
            'scheduled_at' => new \DateTimeImmutable('+5 days')->format('Y-m-d\TH:i'),
        ]);

        $this->assertResponseStatusCodeSame(422);
        $this->assertSelectorTextContains('.field__error', 'Организация обязательна для выбора');
    }

    public function testManagerCreatesCallInVisibleOrganization(): void
    {
        [$manager1, , $romashka] = $this->makeTwoManagersWithOrganizations();
        $this->login($manager1);

        $this->open('/calls/new');
        $this->submitFormByButton('Создать', [
            'organization' => (string) $romashka->id,
            'scheduled_at' => new \DateTimeImmutable('+5 days')->format('Y-m-d\TH:i'),
        ]);

        $this->assertResponseRedirects();

        $this->em()->clear();
        self::assertNotNull($this->findOrganizationCall($romashka));
    }

    public function testManagerCannotCreateCallInInaccessibleOrganization(): void
    {
        [$manager1, , , $zavod] = $this->makeTwoManagersWithOrganizations();
        $this->login($manager1);

        // Токен берём со своей формы создания — он не даёт доступа к чужой организации.
        $this->submitCallAjax('/calls/new', '/calls/new', [
            'organization' => (string) $zavod->id,
            'scheduled_at' => new \DateTimeImmutable('+5 days')->format('Y-m-d\TH:i'),
        ], ajax: false);

        // Организация отсутствует в области доступа менеджера (ADR-0007).
        $this->assertResponseStatusCodeSame(403);
        $this->em()->clear();
        self::assertNull($this->findOrganizationCall($zavod));
    }

    public function testAdminEditsCallNotes(): void
    {
        $call = $this->makeCallWithOrganization(notes: 'Старая заметка');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/calls/' . $call->id . '/edit');
        self::assertSelectorTextContains('h1', 'Редактирование звонка');

        $this->submitFormByButton('Сохранить', [
            'scheduled_at' => new \DateTimeImmutable('+5 days')->format('Y-m-d\TH:i'),
            'notes' => 'Новая заметка',
        ]);

        $this->assertResponseRedirects();

        $this->em()->clear();
        $updated = $this->findCallById($call->id);
        self::assertSame('Новая заметка', $updated->notes);
    }

    public function testEditClearedScheduledAtClearsDate(): void
    {
        $call = $this->makeCallWithOrganization(notes: 'Заметка');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/calls/' . $call->id . '/edit');
        $this->submitFormByButton('Сохранить', [
            'scheduled_at' => '',
            'notes' => 'Заметка',
        ]);

        // Очистка запланированной даты допустима — звонок остаётся без плана.
        $this->assertResponseRedirects();

        $this->em()->clear();
        self::assertNull($this->findCallById($call->id)->scheduledAt);
    }

    public function testManagerCannotOpenEditOfInvisibleCall(): void
    {
        [$manager1, , , $zavod] = $this->makeTwoManagersWithOrganizations();
        $foreignCall = $this->makeCallFor($zavod);
        $this->em()->flush();
        $this->login($manager1);

        $this->open('/calls/' . $foreignCall->id . '/edit');

        // Звонок чужой организации вне области доступа менеджера (ADR-0007).
        $this->assertResponseStatusCodeSame(403);
    }

    public function testManagerCannotUpdateInvisibleCallViaPost(): void
    {
        [$manager1, , , $zavod] = $this->makeTwoManagersWithOrganizations();
        $foreignCall = $this->makeCallFor($zavod);
        $this->em()->flush();
        $this->login($manager1);

        $attemptedAt = new \DateTimeImmutable('+5 days')->setTime(9, 0);
        $this->submitCallAjax('/calls/' . $foreignCall->id . '/edit', '/calls/new', [
            'scheduled_at' => $attemptedAt->format('Y-m-d\TH:i'),
        ], ajax: false);

        $this->assertResponseStatusCodeSame(403);
        $this->em()->clear();
        // Чужой звонок не изменён и новых звонков не появилось.
        $calls = $this->em()->getRepository(Call::class)->findBy(['organization' => $zavod]);
        self::assertCount(1, $calls);
        self::assertSame($foreignCall->id, $calls[0]->id);
        self::assertNotSame($attemptedAt->format('Y-m-d H:i'), $calls[0]->scheduledAt->format('Y-m-d H:i'));
    }

    public function testRecordedFactSetsDateAndCurrentUserAsAuthor(): void
    {
        $user = $this->makeUser('manager@b2b-crm.loc', UserRole::Manager);
        $personal = $this->personalGroup($user);
        $this->em()->persist($personal);
        $organization = $this->makeOrganization('ООО Ромашка');
        $this->em()->persist(new OrgGroupMembership($organization, $personal));
        $call = $this->makeCallFor($organization);
        $this->em()->flush();
        $this->login($user);

        $this->open('/calls/' . $call->id . '/edit');
        $this->submitFormByButton('Сохранить', [
            'scheduled_at' => new \DateTimeImmutable('+5 days')->format('Y-m-d\TH:i'),
            'made_at' => '2026-08-24T12:30',
            'notes' => 'Договорились о встрече',
        ]);

        $this->assertResponseRedirects();

        $this->em()->clear();
        $recorded = $this->findCallById($call->id);
        self::assertSame('2026-08-24 12:30', $recorded->madeAt->format('Y-m-d H:i'));
        // Факт звонка: автором фиксируется текущий пользователь (spec calls/crud).
        self::assertSame('manager@b2b-crm.loc', $recorded->madeBy->email);
    }

    public function testUncheckingMadeClearsFact(): void
    {
        $call = $this->makeCallWithOrganization(notes: 'Факт был');
        $call->setMadeAt(new \DateTimeImmutable('2026-08-20 10:00'));
        $this->em()->flush();
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        // Снятие галочки «звонок проведён»: браузер не отправляет поле made.
        $this->open('/calls/' . $call->id . '/edit');
        $token = $this->client->getCrawler()->filter('input[name="_csrf_token"]')->attr('value');
        $this->client->request('POST', '/calls/' . $call->id . '/edit', [
            '_csrf_token' => $token,
            'scheduled_at' => new \DateTimeImmutable('+5 days')->format('Y-m-d\TH:i'),
        ]);

        $this->assertResponseRedirects();

        $this->em()->clear();
        self::assertNull($this->findCallById($call->id)->madeAt);
    }

    public function testDealFlagPersists(): void
    {
        $call = $this->makeCallWithOrganization(notes: 'Переговоры');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/calls/' . $call->id . '/edit');
        $this->submitFormByButton('Сохранить', [
            'scheduled_at' => new \DateTimeImmutable('+5 days')->format('Y-m-d\TH:i'),
            'is_deal' => '1',
        ]);

        $this->assertResponseRedirects();

        $this->em()->clear();
        self::assertTrue($this->findCallById($call->id)->isDeal);
    }

    public function testNextCallDateCreatesNewScheduledCall(): void
    {
        $organization = $this->makeOrganization('ООО Ромашка');
        $call = $this->makeCallFor($organization);
        $this->em()->flush();
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/calls/' . $call->id . '/edit');
        $this->submitFormByButton('Сохранить', [
            'scheduled_at' => new \DateTimeImmutable('+5 days')->format('Y-m-d\TH:i'),
            'next_call_date' => '2026-10-01',
        ]);

        $this->assertResponseRedirects();

        $this->em()->clear();
        $calls = $this->em()->getRepository(Call::class)->findBy(['organization' => $organization]);
        self::assertCount(2, $calls);

        $next = array_find(
            $calls,
            static fn (Call $c): bool => $c->id !== $call->id
        );
        self::assertNotNull($next);
        // Следующий звонок создаётся запланированным по той же организации.
        self::assertSame('2026-10-01 00:00', $next->scheduledAt->format('Y-m-d H:i'));
        self::assertNull($next->madeAt);
    }

    public function testAjaxUpdateReturnsJsonRowAndPersistsChanges(): void
    {
        [$organization, $contact] = $this->makeOrganizationWithContact('ООО Ромашка');
        $call = $this->makeCallFor($organization, $contact, notes: 'До изменения');
        $this->em()->flush();
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->submitCallAjax('/calls/' . $call->id . '/edit', '/calls/' . $call->id . '/edit', [
            'scheduled_at' => new \DateTimeImmutable('+5 days')->setTime(14, 0)->format('Y-m-d\TH:i'),
            'contact' => (string) $contact->id,
            'notes' => 'После изменения',
        ]);

        $this->assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertTrue($payload['ok']);
        // Строка обновляется отрисованным с сервера HTML без перезагрузки страницы.
        self::assertStringContainsString('data-call-row', $payload['row']);
        self::assertStringContainsString('data-call-id="' . $call->id . '"', $payload['row']);
        self::assertStringContainsString('После изменения', $payload['row']);

        $this->em()->clear();
        self::assertSame('После изменения', $this->findCallById($call->id)->notes);
    }

    public function testAjaxUpdateInvalidDataReturnsJsonErrors(): void
    {
        $call = $this->makeCallWithOrganization(notes: 'Заметка');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->submitCallAjax('/calls/' . $call->id . '/edit', '/calls/' . $call->id . '/edit', [
            'scheduled_at' => '20.08.2020 10:00',
        ]);

        $this->assertResponseStatusCodeSame(422);
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertFalse($payload['ok']);
        self::assertSame('Запланированная дата звонка не может быть в прошлом', $payload['errors']['scheduledAt']);
    }

    public function testDeleteConfirmationPageShowsWarning(): void
    {
        $call = $this->makeCallWithOrganization(notes: 'На удаление');
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $crawler = $this->open('/calls/' . $call->id . '/delete');

        self::assertSelectorTextContains('h1', 'Удаление звонка');
        self::assertSelectorTextContains('.organization-delete__warning', 'ООО Ромашка');
        $form = $crawler->filter('form[action="' . '/calls/' . $call->id . '/delete' . '"]');
        self::assertCount(1, $form);

        // Подтверждение требуется: без отправки формы звонок остаётся.
        $this->em()->clear();
        self::assertNotNull($this->findCallById($call->id));
    }

    public function testRemoveDeletesCall(): void
    {
        $organization = $this->makeOrganization('ООО Ромашка');
        $call = $this->makeCallFor($organization);
        $this->em()->flush();
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->open('/calls/' . $call->id . '/delete');
        $this->submitFormByButton('Удалить', []);

        $this->assertResponseRedirects();

        $this->em()->clear();
        self::assertNull($this->findCallById($call->id));
    }

    public function testGuestCannotAccessCallPages(): void
    {
        $this->client->request('GET', '/calls/new');

        // Неаутентифицированный пользователь попадает на вход (access_control).
        $this->assertResponseRedirects('/login');
    }

    public function testOrganizationContactsEndpointReturnsAccessibleContacts(): void
    {
        [$organization, $contact] = $this->makeOrganizationWithContact('ООО Ромашка');
        $second = new Contact()
            ->setOrganization($organization)
            ->setName('Анна Смирнова')
            ->setContactType(\App\Entity\Enum\ContactType::Person);
        $this->em()->persist($second);
        $this->em()->flush();
        $this->login($this->makeUser('admin@b2b-crm.loc', UserRole::Admin));

        $this->client->request('GET', '/organizations/' . $organization->id . '/contacts.json');

        $this->assertResponseIsSuccessful();
        $payload = json_decode((string) $this->client->getResponse()->getContent(), true);
        self::assertTrue($payload['ok']);
        // Контакты организации — по алфавиту (динамическая загрузка, задача 6.4).
        self::assertSame(
            ['Анна Смирнова', 'Иван Петров'],
            array_column($payload['contacts'], 'name')
        );
        self::assertSame($contact->id, $payload['contacts'][1]['id']);
    }

    public function testManagerCannotLoadContactsOfInaccessibleOrganization(): void
    {
        [$manager1, , , $zavod] = $this->makeTwoManagersWithOrganizations();
        $this->login($manager1);

        $this->client->request('GET', '/organizations/' . $zavod->id . '/contacts.json');

        $this->assertResponseStatusCodeSame(403);
    }

    // ------------------------------------------------------------------
    // Помощники

    private function makeUser(string $email, UserRole $role): User
    {
        $user = new User()
            ->setEmail($email)
            ->setRole($role);
        $user->setPassword('test-password-hash');
        $this->em()->persist($user);
        // Идентификатор нужен до loginUser() (EntityUserProvider требует id).
        $this->em()->flush();

        return $user;
    }

    private function makeOrganization(string $name): Organization
    {
        $organization = new Organization()->setName($name)->setIndustry('IT');
        $this->em()->persist($organization);
        $this->em()->flush();

        return $organization;
    }

    /**
     * @return array{0: Organization, 1: Contact}
     */
    private function makeOrganizationWithContact(string $name): array
    {
        $organization = $this->makeOrganization($name);
        $contact = new Contact()
            ->setOrganization($organization)
            ->setName('Иван Петров')
            ->setContactType(\App\Entity\Enum\ContactType::Person);
        $this->em()->persist($contact);
        $this->em()->flush();

        return [$organization, $contact];
    }

    private function makeCallFor(Organization $organization, ?Contact $contact = null, string $notes = 'Тестовый звонок'): Call
    {
        $call = new Call()
            ->setOrganization($organization)
            ->setScheduledAt(new \DateTimeImmutable('2026-08-20 10:00'))
            ->setNotes($notes);
        if (null !== $contact) {
            $call->setContact($contact);
        }
        $this->em()->persist($call);

        return $call;
    }

    private function makeCallWithOrganization(string $notes = 'Тестовый звонок'): Call
    {
        $call = $this->makeCallFor($this->makeOrganization('ООО Ромашка'), notes: $notes);
        $this->em()->flush();

        return $call;
    }

    private function makeTwoManagersWithOrganizations(): array
    {
        $em = $this->em();
        $manager1 = $this->makeUser('manager1@b2b-crm.loc', UserRole::Manager);
        $manager2 = $this->makeUser('manager2@b2b-crm.loc', UserRole::Manager);
        $em->flush();

        $personal1 = $this->personalGroup($manager1);
        $personal2 = $this->personalGroup($manager2);
        $em->persist($personal1);
        $em->persist($personal2);

        $romashka = new Organization()->setName('ООО Ромашка')->setIndustry('IT');
        $zavod = new Organization()->setName('ООО Завод')->setIndustry('Производство');
        $em->persist($romashka);
        $em->persist($zavod);

        $em->persist(new OrgGroupMembership($romashka, $personal1));
        $em->persist(new OrgGroupMembership($zavod, $personal2));
        $em->flush();

        return [$manager1, $manager2, $romashka, $zavod];
    }

    private function personalGroup(User $owner): OrganizationGroup
    {
        return new OrganizationGroup()
            ->setName('Личная группа ' . $owner->email)
            ->setSlug('user-' . $owner->id . '-group')
            ->setType(GroupType::User)
            ->setOwnerUser($owner);
    }

    private function findCall(string $notes): ?Call
    {
        /** @var Call|null $call */
        $call = $this->em()->getRepository(Call::class)->findOneBy(['notes' => $notes]);

        return $call;
    }

    private function findCallById(int $id): ?Call
    {
        return $this->em()->getRepository(Call::class)->find($id);
    }

    private function findOrganizationCall(Organization $organization): ?Call
    {
        /** @var Call|null $call */
        $call = $this->em()->getRepository(Call::class)->findOneBy(['organization' => $organization]);

        return $call;
    }

    /**
     * AJAX-POST формы звонка с CSRF-токеном открытой страницы.
     *
     * @param array<string, string> $fields
     */
    private function submitCallAjax(string $url, string $tokenPageUrl, array $fields, bool $ajax = true): void
    {
        $token = $this->open($tokenPageUrl)->filter('input[name="_csrf_token"]')->attr('value');

        $this->client->request(
            'POST',
            $url,
            $fields + ['_csrf_token' => $token],
            [],
            $ajax ? ['HTTP_X_REQUESTED_WITH' => 'XMLHttpRequest'] : [],
        );
    }
}
