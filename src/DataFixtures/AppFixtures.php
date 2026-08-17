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
    ];

    private const POSITIONS = ['Генеральный директор', 'Руководитель отдела закупок'];

    public function __construct(private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function load(ObjectManager $manager): void
    {
        $admin = $this->makeUser($manager, self::ADMIN_EMAIL, self::ADMIN_PASSWORD, UserRole::Admin);
        $manager1 = $this->makeUser($manager, self::MANAGER_EMAIL, self::MANAGER_PASSWORD, UserRole::Manager);
        $manager2 = $this->makeUser($manager, self::SECOND_MANAGER_EMAIL, self::SECOND_MANAGER_PASSWORD, UserRole::Manager);
        $manager->flush();

        $personal1 = $this->makeGroup($manager, 'user-' . $manager1->getId() . '-group', $manager1, GroupType::User);
        $personal2 = $this->makeGroup($manager, 'user-' . $manager2->getId() . '-group', $manager2, GroupType::User);
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

        $contacts = [];
        $index = 0;
        foreach ($organizations as $organization) {
            foreach (self::POSITIONS as $position) {
                $contact = (new Contact())
                    ->setOrganization($organization)
                    ->setName('Контакт ' . ($index + 1))
                    ->setPhone('+7 900 000-00-' . str_pad((string) $index, 2, '0', STR_PAD_LEFT))
                    ->setEmail('contact' . $index . '@example.ru')
                    ->setPosition($position)
                    ->setContactType(ContactType::Person);
                $manager->persist($contact);
                $contacts[] = $contact;
                ++$index;
            }
        }

        foreach ($organizations as $index => $organization) {
            $call = (new Call())
                ->setOrganization($organization)
                ->setContact($contacts[$index * 2])
                ->setScheduledAt((new \DateTimeImmutable('+1 day'))->setTime(10, 0))
                ->setMadeBy($manager1)
                ->setNotes('Запланированный обзвон из fixtures');
            $manager->persist($call);
        }

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
            ->setName($owner ? 'Личная группа ' . $owner->getEmail() : 'Клиенты-партнёры')
            ->setSlug($slug)
            ->setType($type)
            ->setOwnerUser($owner);
        $manager->persist($group);

        return $group;
    }
}
