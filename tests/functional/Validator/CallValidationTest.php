<?php

namespace App\Tests\Functional\Validator;

use App\Entity\Call;
use App\Entity\Contact;
use App\Entity\Enum\ContactType;
use App\Entity\Organization;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * Integration-тесты серверной валидации звонка (change calls-crud,
 * задачи 4.3/7.2): обязательные поля organization/scheduledAt
 * с сообщениями на русском.
 */
final class CallValidationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        /** @var ValidatorInterface $validator */
        $validator = self::getContainer()->get(ValidatorInterface::class);
        $this->validator = $validator;
    }

    public function testValidCallHasNoViolations(): void
    {
        $organization = (new Organization())->setName('ООО Ромашка')->setIndustry('IT');
        $contact = (new Contact())
            ->setOrganization($organization)
            ->setName('Иван Петров')
            ->setContactType(ContactType::Person);

        $call = (new Call())
            ->setOrganization($organization)
            ->setContact($contact)
            ->setScheduledAt(new \DateTimeImmutable('2026-09-01 10:30'));

        self::assertSame(0, $this->validator->validate($call)->count());
    }

    public function testMissingScheduledAtShowsRussianMessage(): void
    {
        $call = (new Call())
            ->setOrganization((new Organization())->setName('ООО Ромашка')->setIndustry('IT'));

        $violations = $this->validator->validate($call);

        $messages = [];
        foreach ($violations as $violation) {
            $messages[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        self::assertArrayHasKey('scheduledAt', $messages);
        self::assertSame('Дата звонка обязательна для заполнения', $messages['scheduledAt']);
        self::assertArrayNotHasKey('organization', $messages);
    }

    public function testMissingOrganizationShowsRussianMessage(): void
    {
        $call = (new Call())
            ->setScheduledAt(new \DateTimeImmutable('2026-09-01 10:30'));

        $violations = $this->validator->validate($call);

        $messages = [];
        foreach ($violations as $violation) {
            $messages[$violation->getPropertyPath()] = (string) $violation->getMessage();
        }

        self::assertArrayHasKey('organization', $messages);
        self::assertSame('Организация обязательна для выбора', $messages['organization']);
        self::assertArrayNotHasKey('scheduledAt', $messages);
    }

    public function testBothRequiredFieldsReportedWhenEmpty(): void
    {
        $call = new Call();

        $violations = $this->validator->validate($call);

        $paths = [];
        foreach ($violations as $violation) {
            $paths[] = $violation->getPropertyPath();
        }

        self::assertContains('organization', $paths);
        self::assertContains('scheduledAt', $paths);
    }
}
