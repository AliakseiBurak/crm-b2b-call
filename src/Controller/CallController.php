<?php

namespace App\Controller;

use App\Entity\Call;
use App\Entity\Contact;
use App\Entity\Enum\UserRole;
use App\Entity\Organization;
use App\Entity\User;
use App\Repository\CallRepository;
use App\Repository\ContactRepository;
use App\Repository\OrganizationRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[Route(requirements: ['id' => '\d+'])]
class CallController extends AbstractController
{
    public function __construct(
        private readonly CallRepository $calls,
        private readonly ContactRepository $contacts,
        private readonly OrganizationRepository $organizations,
        private readonly UserRepository $users,
        private readonly EntityManagerInterface $em,
    ) {
    }

    #[Route('/calls/new', name: 'app_call_new', methods: ['GET'])]
    #[Route('/organizations/{organizationId}/calls/new', name: 'app_call_new_org', methods: ['GET'])]
    public function new(Request $request, ?int $organizationId = null): Response
    {
        $organizations = $this->organizations->findAccessibleOrganizations($this->getUser());
        // Предвыбор организации из ссылки «Добавить звонок»: только если
        // организация есть в списке доступных.
        $selectedOrganizationId = null;
        foreach ($organizations as $organization) {
            if ($organization->id === $organizationId) {
                $selectedOrganizationId = $organizationId;

                break;
            }
        }

        $isAdmin = $this->isAdmin();

        return $this->render('call/form.html.twig', [
            'call' => null,
            'defaultScheduledAt' => $this->defaultScheduledDate(),
            'organizations' => $organizations,
            'selectedOrganizationId' => $selectedOrganizationId,
            'contacts' => null === $selectedOrganizationId ? [] : $this->organizationContacts($selectedOrganizationId),
            'errors' => [],
            'isAdmin' => $isAdmin,
            'users' => $isAdmin ? $this->users->findAdminsAndManagers() : [],
        ]);
    }

    #[Route('/calls/new', name: 'app_call_create', methods: ['POST'])]
    public function create(Request $request, ValidatorInterface $validator): Response
    {
        $this->assertCsrfToken($request);
        $ajax = $request->isXmlHttpRequest();

        $call = new Call();
        // Организация выбирается из области доступа (ADR-0007/0008). Пустой
        // ввод — ошибка валидации; несуществующий или невидимый идентификатор —
        // отказ (404/403).
        $requestedOrganizationId = (int) $request->request->get('organization', 0);
        if ($requestedOrganizationId > 0) {
            $call->setOrganization($this->accessibleOrganization($requestedOrganizationId));
        }

        $errors = $this->applyRequest($request, $validator, $call);
        if ([] !== $errors) {
            if ($ajax) {
                return $this->json(['ok' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $isAdmin = $this->isAdmin();

            return $this->render('call/form.html.twig', [
                'call' => $call,
                'organizations' => $this->organizations->findAccessibleOrganizations($this->getUser()),
                'selectedOrganizationId' => $requestedOrganizationId > 0 ? $requestedOrganizationId : null,
                // Организация может быть не установлена (ошибка валидации).
                'contacts' => isset($call->organization) ? $this->organizationContacts($call->organization->id) : [],
                'errors' => $errors,
                'isAdmin' => $isAdmin,
                'users' => $isAdmin ? $this->users->findAdminsAndManagers() : [],
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        $this->em->persist($call);
        $this->em->flush();

        if ($ajax) {
            return $this->json(['ok' => true]);
        }

        return $this->redirectToRoute('app_dashboard', ['highlight' => $call->organization->id]);
    }

    #[Route('/calls/{id}/edit', name: 'app_call_edit', methods: ['GET'])]
    public function edit(int $id): Response
    {
        $call = $this->accessibleCall($id);

        $isAdmin = $this->isAdmin();

        return $this->render('call/form.html.twig', [
            'call' => $call,
            'organizations' => [$call->organization],
            'selectedOrganizationId' => $call->organization->id,
            'contacts' => $this->organizationContacts($call->organization->id),
            'errors' => [],
            'isAdmin' => $isAdmin,
            'users' => $isAdmin ? $this->users->findAdminsAndManagers() : [],
        ]);
    }

    #[Route('/calls/{id}/edit', name: 'app_call_update', methods: ['POST'])]
    public function update(int $id, Request $request, ValidatorInterface $validator): Response
    {
        $call = $this->accessibleCall($id);
        $ajax = $request->isXmlHttpRequest();

        // Токен приходит из формы (FormData) или заголовком X-CSRF-Token.
        $this->assertCsrfToken($request);

        $errors = $this->applyRequest($request, $validator, $call);
        if ([] !== $errors) {
            if ($ajax) {
                return $this->json(['ok' => false, 'errors' => $errors], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            $isAdmin = $this->isAdmin();

            return $this->render('call/form.html.twig', [
                'call' => $call,
                'organizations' => [$call->organization],
                'selectedOrganizationId' => $call->organization->id,
                'contacts' => $this->organizationContacts($call->organization->id),
                'errors' => $errors,
                'isAdmin' => $isAdmin,
                'users' => $isAdmin ? $this->users->findAdminsAndManagers() : [],
            ], new Response(null, Response::HTTP_UNPROCESSABLE_ENTITY));
        }

        // Результат «следующий звонок»: новый запланированный звонок той же
        // организации попадает в планирование (spec calls/crud).
        $nextCallDate = $this->parseDate((string) $request->request->get('next_call_date', ''));
        if (null !== $nextCallDate) {
            $this->em->persist(new Call()
                ->setOrganization($call->organization)
                ->setScheduledAt($nextCallDate));
        }

        $this->em->flush();

        if ($ajax) {
            return $this->json([
                'ok' => true,
                'row' => $this->renderView('call/_row.html.twig', [
                    'call' => $this->rowOf($call),
                    'callContact' => $call->contact,
                ]),
            ]);
        }

        return $this->redirectToRoute('app_dashboard', ['highlight' => $call->organization->id]);
    }

    #[Route('/calls/{id}/delete', name: 'app_call_delete', methods: ['GET'])]
    public function delete(int $id): Response
    {
        $call = $this->accessibleCall($id);

        return $this->render('call/delete.html.twig', [
            'call' => $call,
        ]);
    }

    #[Route('/calls/{id}/delete', name: 'app_call_remove', methods: ['POST'])]
    public function remove(int $id, Request $request): Response
    {
        $call = $this->accessibleCall($id);
        $this->assertCsrfToken($request);

        $organizationId = $call->organization->id;
        $this->em->remove($call);
        $this->em->flush();

        return $this->redirectToRoute('app_dashboard', ['highlight' => $organizationId]);
    }

    /**
     * Контакты организации для динамической загрузки в форме звонка
     * (AJAX): список id/имя по алфавиту.
     */
    #[Route('/organizations/{id}/contacts.json', name: 'app_call_organization_contacts', methods: ['GET'])]
    public function organizationContactsAction(int $id): Response
    {
        $this->accessibleOrganization($id);

        return $this->json([
            'ok' => true,
            'contacts' => array_map(
                static fn (Contact $contact): array => ['id' => $contact->id, 'name' => $contact->name],
                $this->organizationContacts($id)
            ),
        ]);
    }

    /**
     * Заполняет звонок данными формы и возвращает ошибки валидации,
     * сгруппированные по полям (organization, scheduledAt, contact).
     *
     * @return array<string, string>
     */
    private function applyRequest(Request $request, ValidatorInterface $validator, Call $call): array
    {
        $errors = [];

        // Контакт опционален и должен принадлежать организации звонка.
        $contactId = (int) $request->request->get('contact', 0);
        if ($contactId > 0) {
            $contact = $this->contacts->find($contactId);
            if (null === $contact) {
                throw $this->createNotFoundException('Контакт не найден');
            }
            if (isset($call->organization) && $contact->organization->id !== $call->organization->id) {
                $errors['contact'] = 'Контакт не принадлежит выбранной организации';
            } else {
                $call->setContact($contact);
            }
        } else {
            $call->setContact(null);
        }

        $scheduledAt = $this->parseDateTime((string) $request->request->get('scheduled_at', ''));
        if (false === $scheduledAt) {
            $errors['scheduledAt'] = 'Некорректный формат даты звонка';
        } else {
            // Значение пишется в entity и при ошибке: форма после 422
            // восстанавливает введённое (сохранения на этом пути нет).
            $call->setScheduledAt($scheduledAt);
            if (null !== $scheduledAt && $scheduledAt < new \DateTimeImmutable('today')) {
                $errors['scheduledAt'] = 'Запланированная дата звонка не может быть в прошлом';
            }
        }

        // Факт звонка определяется наличием фактической даты. Если дата указана,
        // звонок считается проведённым, автор — текущий пользователь (для
        // администратора — выбранный в made_by, иначе текущий).
        $madeAtRaw = (string) $request->request->get('made_at', '');
        if ('' !== trim($madeAtRaw)) {
            $madeAt = $this->parseDateTime($madeAtRaw);
            if (false === $madeAt) {
                $errors['madeAt'] = 'Некорректный формат даты звонка';
            } else {
                // Значение пишется в entity и при ошибке: форма после 422
                // восстанавливает введённое (сохранения на этом пути нет).
                $call->setMadeAt($madeAt);
                if ($madeAt > new \DateTimeImmutable()) {
                    $errors['madeAt'] = 'Фактическая дата звонка не может быть в будущем';
                } else {
                    $call->setMadeBy($this->resolveMadeBy($request));
                }
            }
        } else {
            $call->setMadeAt(null);
            $call->setMadeBy(null);
        }

        $call->setIsDeal(null !== $request->request->get('is_deal'));
        $call->setNotes($this->optionalField($request, 'notes'));

        $violations = $validator->validate($call);
        foreach ($violations as $violation) {
            $errors[$violation->getPropertyPath()] ??= $violation->getMessage();
        }

        return $errors;
    }

    /**
     * Плановая дата звонка по умолчанию: через 3 дня от текущей даты;
     * если выпадает на выходные (суббота/воскресенье), переносится на понедельник.
     */
    private function defaultScheduledDate(): \DateTimeImmutable
    {
        $date = new \DateTimeImmutable('+3 days');
        $weekday = (int) $date->format('w'); // 0 — воскресенье, 6 — суббота
        if (0 === $weekday) {
            $date = $date->modify('+1 day');
        } elseif (6 === $weekday) {
            $date = $date->modify('+2 days');
        }

        return $date;
    }

    /**
     * Дата следующего звонка (input type=date): Y-m-d, опционально.
     */
    private function parseDate(string $value): ?\DateTimeImmutable
    {
        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        if (false === $date) {
            $date = \DateTimeImmutable::createFromFormat('!d.m.Y', $value);
        }

        return false === $date ? null : $date;
    }

    /**
     * datetime-local: пусто — null; неразборчивая строка — false.
     */
    private function parseDateTime(string $value): \DateTimeImmutable|false|null
    {
        $value = trim($value);
        if ('' === $value) {
            return null;
        }

        // Формат ввода/вывода формы — d.m.Y H:i (как в таблице панели), плюс
        // обратная совместимость с ISO-форматами datetime-local.
        foreach (['d.m.Y H:i:s', 'd.m.Y H:i', 'Y-m-d\TH:i:s', 'Y-m-d\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', 'd.m.Y', 'Y-m-d'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if (false !== $date) {
                return $date;
            }
        }

        return false;
    }

    private function optionalField(Request $request, string $field): ?string
    {
        $value = trim((string) $request->request->get($field, ''));

        return '' === $value ? null : $value;
    }

    /**
     * Звонок в области доступа пользователя: менеджеру — только звонки
     * организаций личной и назначенных групп (ADR-0007), администратору —
     * все (ADR-0008, группы не проверяются).
     */
    private function accessibleCall(int $id): Call
    {
        $call = $this->calls->find($id);
        if (null === $call) {
            throw $this->createNotFoundException('Звонок не найден');
        }

        if (!$this->isOrganizationAccessible($call->organization)) {
            throw new AccessDeniedHttpException('Организация звонка вне области доступа');
        }

        return $call;
    }

    /**
     * Текущий пользователь — администратор (видит всех и может назначать
     * автора звонка из числа администраторов и менеджеров).
     */
    private function isAdmin(): bool
    {
        $user = $this->getUser();

        return $user instanceof User && UserRole::Admin === $user->role;
    }

    /**
     * Автор зафиксированного звонка: администратор может выбрать любого
     * менеджера/администратора через поле made_by; иначе — текущий
     * пользователь. Недопустимый выбор игнорируется в пользу текущего.
     */
    private function resolveMadeBy(Request $request): ?User
    {
        $current = $this->getUser();
        if (!$current instanceof User) {
            return null;
        }

        if (UserRole::Admin !== $current->role) {
            return $current;
        }

        $madeById = (int) $request->request->get('made_by', 0);
        if ($madeById > 0) {
            $chosen = $this->users->find($madeById);
            if ($chosen instanceof User
                && in_array($chosen->role, [UserRole::Admin, UserRole::Manager], true)) {
                return $chosen;
            }
        }

        return $current;
    }

    private function accessibleOrganization(int $id): Organization
    {
        $organization = $this->organizations->find($id);
        if (null === $organization) {
            throw $this->createNotFoundException('Организация не найдена');
        }

        if (!$this->isOrganizationAccessible($organization)) {
            throw new AccessDeniedHttpException('Организация вне области доступа');
        }

        return $organization;
    }

    private function isOrganizationAccessible(Organization $organization): bool
    {
        $accessibleIds = $this->organizations->findAccessibleIds($this->getUser());

        return null === $accessibleIds || \in_array($organization->id, $accessibleIds, true);
    }

    /**
     * @return Contact[]
     */
    private function organizationContacts(int $organizationId): array
    {
        return $this->contacts->findBy(['organization' => $organizationId], ['name' => 'ASC']);
    }

    /**
     * Данные строки звонка на панели: те же поля, что отдаёт
     * CallRepository::findAllCallsByOrganizations().
     *
     * @return array{id: int, organizationId: int, contactId: int, date: ?\DateTimeImmutable,
     *     scheduledAt: ?\DateTimeImmutable, madeAt: ?\DateTimeImmutable, isDeal: bool, notes: ?string}
     */
    private function rowOf(Call $call): array
    {
        return [
            'id' => $call->id,
            'organizationId' => $call->organization->id,
            'contactId' => $call->contact?->id ?? 0,
            'date' => $call->madeAt ?? $call->scheduledAt,
            'scheduledAt' => $call->scheduledAt,
            'madeAt' => $call->madeAt,
            'madeById' => $call->madeBy?->id,
            'isDeal' => $call->isDeal,
            'notes' => $call->notes,
        ];
    }

    /**
     * Защита state-changing форм от CSRF; для AJAX-запросов токен передаётся
     * в заголовке X-CSRF-Token.
     */
    private function assertCsrfToken(Request $request): void
    {
        $token = $request->headers->get('X-CSRF-Token') ?? (string) $request->request->get('_csrf_token', '');
        if (!$this->isCsrfTokenValid('call', $token)) {
            throw new AccessDeniedHttpException('Недействительный CSRF-токен');
        }
    }
}
