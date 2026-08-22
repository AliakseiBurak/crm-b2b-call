<?php

namespace App\DataFixtures;

use App\Entity\Call;
use App\Entity\Contact;
use App\Entity\Enum\ContactType;
use App\Entity\Enum\GroupType;
use App\Entity\Enum\UserRole;
use App\Entity\GroupAssignment;
use App\Entity\OrgGroupMembership;
use App\Entity\Organization;
use App\Entity\OrganizationGroup;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public const ADMIN_EMAIL = 'admin@b2b-crm.loc';
    public const ADMIN_PASSWORD = 'admin123';
    public const MANAGER_EMAIL = 'manager@b2b-crm.loc';
    public const MANAGER_PASSWORD = 'manager123';

    private const SECOND_MANAGER_EMAIL = 'manager2@b2b-crm.loc';
    private const SECOND_MANAGER_PASSWORD = 'manager123';

    private const ORGANIZATIONS = [
        ['ООО "Ромашка"', 'Ритейл'],
        ['АО "Вектор"', 'Логистика'],
        ['ИП Сидоров', 'Услуги'],
        ['ООО "Конкурент"', 'Производство'],
        ['ООО "Горизонт"', 'Строительство'], // без контактов и без звонков
        ['ООО "Закат"', 'Туризм'], // с контактом, без звонков
        ['ООО "Парус"', 'Транспорт'], // просрочки и частичные обзвоны (dashboard-stats-by-organization)
    ];

    private const POSITIONS = ['Генеральный директор', 'Руководитель отдела закупок', 'Руководитель отдела кадров'];

    /** Число контактов на каждую организацию (Ромашка, Вектор, Сидоров, Конкурент, Горизонт, Закат, Парус).
     *  У Вектора 4 — чтобы карточки переносились на новую строку (влияет на вид). */
    private const CONTACTS_PER_ORGANIZATION = [2, 4, 1, 2, 0, 1, 0];

    /** Имена контактов: ключ — глобальный индекс контакта (0..9).
     *  Имена не обязаны быть уникальными ни в организации, ни глобально. */
    private const CONTACT_NAMES = [
        'Иван Петрович Иванов',
        'Иван Иванович Петров',
        'Пётр Иванович',
        'Наталья Павловна Сидоровна',
        'Мария Сергеевна Петровна',
        'Марина Александровна',
        'Анна Сергеевна Иванова',
        'Дмитрий Николаевич',
        'Марина Александровна',
        'Ольга Викторовна',
    ];

    /** Должности: ключ — глобальный индекс контакта; для всех контактов разные. */
    private const POSITION_BY_INDEX = [
        0 => 'Генеральный директор',
        1 => 'Руководитель отдела кадров',
        2 => 'Директор по логистике',
        3 => 'Руководитель отдела закупок',
        4 => 'Приёмная',
        5 => 'Менеджер по логистике',
        6 => 'Директор',
        7 => 'Главный инженер',
        8 => 'Начальник производства',
        9 => 'Руководитель отдела продаж',
    ];

    /** Заметки контактов: ключ — локальный индекс контакта в организации. */
    private const CONTACT_NOTES = [
        0 => 'Предпочитает звонки после 14:00',
        2 => 'Запись через приёмную',
    ];

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->makeUser($manager, self::ADMIN_EMAIL, self::ADMIN_PASSWORD, UserRole::Admin);
        $manager1 = $this->makeUser($manager, self::MANAGER_EMAIL, self::MANAGER_PASSWORD, UserRole::Manager);
        $manager2 = $this->makeUser($manager, self::SECOND_MANAGER_EMAIL, self::SECOND_MANAGER_PASSWORD, UserRole::Manager);
        $manager->flush();

        $personal1 = $this->makeGroup($manager, 'user-' . $manager1->id . '-group', $manager1, GroupType::User);
        $personal2 = $this->makeGroup($manager, 'user-' . $manager2->id . '-group', $manager2, GroupType::User);
        $custom = $this->makeGroup($manager, 'custom-partners', null, GroupType::Custom);
        $manager->flush();

        $manager->persist(new GroupAssignment($manager1, $custom));
        $manager->persist(new GroupAssignment($manager2, $custom));

        $organizations = [];
        foreach (self::ORGANIZATIONS as [$name, $industry]) {
            $organization = (new Organization())
                ->setName($name)
                ->setIndustry($industry);
            $manager->persist($organization);
            $organizations[] = $organization;
        }
        $manager->flush();

        $manager->persist(new OrgGroupMembership($organizations[0], $personal1));
        $manager->persist(new OrgGroupMembership($organizations[1], $personal1));
        $manager->persist(new OrgGroupMembership($organizations[1], $custom));
        $manager->persist(new OrgGroupMembership($organizations[2], $personal2));
        $manager->persist(new OrgGroupMembership($organizations[2], $custom));
        $manager->persist(new OrgGroupMembership($organizations[3], $personal2));
        $manager->persist(new OrgGroupMembership($organizations[4], $personal1)); // Горизонт — без контактов
        $manager->persist(new OrgGroupMembership($organizations[5], $personal1)); // Закат — с контактом, без звонков
        $manager->persist(new OrgGroupMembership($organizations[6], $personal1)); // Парус — просрочки/частичные обзвоны

        $contacts = [];
        $index = 0;
        foreach ($organizations as $orgIndex => $organization) {
            $count = self::CONTACTS_PER_ORGANIZATION[$orgIndex];
            for ($i = 0; $i < $count; ++$i) {
                $isReception = $orgIndex === 1 && $index === 4; // «Приёмная» — Мария (3-й контакт Вектора)
                $contact = (new Contact())
                    ->setOrganization($organization)
                    ->setName(self::CONTACT_NAMES[$index])
                    ->setPhone('+7 900 000-00-' . str_pad((string) $index, 2, '0', STR_PAD_LEFT))
                    ->setEmail('contact' . $index . '@example.ru')
                    ->setPosition($isReception ? 'Приёмная' : self::POSITION_BY_INDEX[$index] ?? self::POSITIONS[0])
                    ->setContactType(ContactType::Person)
                    ->setNotes(self::CONTACT_NOTES[$i] ?? null);
                $manager->persist($contact);
                $contacts[] = $contact;
                ++$index;
            }
        }

        // Звонки. ООО "Ромашка" — три звонка с разными наборами данных:
        // первый без заметки и без контакта (только дата), следующий только
        // с заметкой (без контакта), следующий только с контактом (без
        // заметки). ООО "Горизонт" — без звонков вовсе. Вектор/Конкурент —
        // факты и планы с заметками; заметки — рабочие формулировки менеджера.
        $today = new \DateTimeImmutable('today');
        $yesterday = $today->modify('-1 day');
        $make = function (Organization $org, ?Contact $contact, \DateTimeImmutable $madeAt, ?string $notes) use ($manager, $manager1): void {
            $call = (new Call())
                ->setOrganization($org)
                ->setMadeAt($madeAt)
                ->setMadeBy($manager1)
                ->setNotes($notes);
            if (null !== $contact) {
                $call->setContact($contact);
            }
            $manager->persist($call);
        };
        $make($organizations[0], null, $today->modify('-10 days')->setTime(12, 0), null); // Ромашка: только дата
        $make($organizations[0], null, $today->modify('-3 days')->setTime(12, 0), 'Нет ответа, перезвонить завтра'); // Ромашка: только заметка
        $make($organizations[1], $contacts[2], $today->modify('-3 days')->setTime(12, 0), 'Уточнить состав группы');

        // Вне области менеджера (ООО "Конкурент" — только в группе manager2)
        $manager->persist((new Call())
            ->setOrganization($organizations[3])
            ->setContact($contacts[7])
            ->setMadeAt($today->setTime(10, 0))
            ->setMadeBy($manager2)
            ->setNotes('Нет ответа'));
        $manager->persist((new Call())
            ->setOrganization($organizations[3])
            ->setContact($contacts[8])
            ->setScheduledAt($today->setTime(12, 0))
            ->setMadeBy($manager2)
            ->setNotes('Перезвонить утром'));

        // Запланированные обзвоны (scheduled) по периодам дашборда:
        // неделя (+1д) / месяц (+20д у Конкурента) / более месяца (+45д);
        // -2д — просроченный план (без заметки).
        $plan = function (Organization $org, ?Contact $contact, \DateTimeImmutable $at, ?string $notes) use ($manager, $manager1): void {
            $call = (new Call())
                ->setOrganization($org)
                ->setScheduledAt($at)
                ->setMadeBy($manager1)
                ->setNotes($notes);
            if (null !== $contact) {
                $call->setContact($contact);
            }
            $manager->persist($call);
        };
        $plan($organizations[0], $contacts[0], $today->modify('+1 day')->setTime(10, 0), null); // Ромашка: только контакт
        $plan($organizations[1], $contacts[2], $today->modify('+1 day')->setTime(10, 0), 'Перезвонить после 14:00');
        $plan($organizations[2], null, $today->modify('+45 days')->setTime(10, 0), 'План обучения будет в ноябре, уточнить состав группы'); // Сидоров: последний звонок без контакта
        $plan($organizations[2], $contacts[6], $today->modify('-2 days')->setTime(16, 0), null);

        // Звонки «только с датой»: без заметки и без контакта — у Конкурента.
        // Даты видны в таблице; в списке «Все звонки» строки без текста
        // (контакта тоже нет).
        $bare = function (Organization $org, \DateTimeImmutable $at, bool $made) use ($manager, $manager1): void {
            $call = (new Call())
                ->setOrganization($org)
                ->setMadeBy($manager1);
            if ($made) {
                $call->setMadeAt($at);
            } else {
                $call->setScheduledAt($at);
            }
            $manager->persist($call);
        };
        $bare($organizations[3], $today->modify('-9 days')->setTime(12, 0), true); // Конкурент: факт
        $bare($organizations[3], $today->modify('+14 days')->setTime(11, 0), false); // Конкурент: план

        // Просроченные звонки (change dashboard-stats-by-organization): план
        // в прошлом с made_at IS NULL — категория «Просроченные». Вектор:
        // факт сегодня + план сегодня (исключается из «Ожидают сегодня») +
        // нереализованный план вчера («Просроченные: вчера» + факт сегодня —
        // независимые категории). Сидоров/Конкурент: просрочки глубиной
        // 20 и 5 дней. Парус: 5 планов на вчера (3 совершены, 2 нет) —
        // частичная реализация, плюс план на сегодня без факта сегодня.
        $bare($organizations[1], $today->setTime(8, 0), true); // Вектор: факт сегодня
        $bare($organizations[1], $today->setTime(9, 0), false); // Вектор: план сегодня (исключён из «Ожидают»)
        $plan($organizations[1], null, $yesterday->setTime(11, 0), null); // Вектор: просрочка вчера
        $bare($organizations[2], $today->modify('-20 days')->setTime(14, 0), false); // Сидоров: просрочка за 30 дней
        $bare($organizations[3], $today->modify('-5 days')->setTime(12, 0), false); // Конкурент: просрочка вне области менеджера
        for ($i = 0; $i < 5; ++$i) {
            // Парус: 5 планов на вчера — 3 совершены (план + факт), 2 нет
            $call = (new Call())
                ->setOrganization($organizations[6])
                ->setScheduledAt($yesterday->setTime(18, $i))
                ->setMadeBy($manager1);
            if ($i < 3) {
                $call->setMadeAt($yesterday->setTime(19, $i));
            }
            $manager->persist($call);
        }
        $bare($organizations[6], $today->setTime(9, 0), false); // Парус: план сегодня без факта

        $manager->flush();
    }

    private function makeUser(ObjectManager $manager, string $email, string $password, UserRole $role): User
    {
        $user = (new User())
            ->setEmail($email)
            ->setRole($role);
        $user->setPassword($this->passwordHasher->hashPassword($user, $password));
        $manager->persist($user);

        return $user;
    }

    private function makeGroup(ObjectManager $manager, string $slug, ?User $owner, GroupType $type): OrganizationGroup
    {
        $group = (new OrganizationGroup())
            ->setName($owner ? 'Личная группа ' . $owner->email : 'Клиенты-партнёры')
            ->setSlug($slug)
            ->setType($type)
            ->setOwnerUser($owner);
        $manager->persist($group);

        return $group;
    }
}
