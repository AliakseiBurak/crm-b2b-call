## Context

Создание пользователя — новая операция в B2B Call CRM. Текущая модель доступа
(ADR-0005–0008) и фиксированные роли (ADR-0009) уже определены; отсутствует
лишь процедурная часть создания пользователя с полями имени/фамилии и ролью.
Стек: Symfony 7.x, Doctrine ORM 3.x, MySQL. Мотивация — в proposal.md (## Why).

Существующая сущность `User` ещё не имеет полей `name`/`surname` как
nullable-колонок; персональная группа `user-<id>-group` создаётся логикой,
описанной в ADR-0005 (для manager), и пропускается для admin (ADR-0008).

## Goals / Non-Goals

**Goals:**
- Обеспечить эндпоинт и форму создания пользователя администратором.
- Сохранять обязательный `email`, необязательные `name`/`surname` и
  обязательную `role`. Пароль не задаётся при создании.
- Автоматически создавать `user-<id>-group` для роли `manager`.
- Защитить операцию ролью `admin`.
- Обеспечить удаление пользователя администратором с каскадным удалением
  персональной группы для `manager`.
- Обеспечить страницу списка пользователей `/admin/users` для администратора.

**Non-Goals:**
- CRUD ролей (запрещён, ADR-0009).
- Редактирование существующих пользователей — отдельная возможность.
- Аутентификация и сессионная модель — в `authentication`.
- Назначение групп менеджерам — в `access-control`.
- Добавление Symfony Messenger / CQRS-слоя — кодбейд использует прямые
  вызовы `EntityManagerInterface`.

## Decisions

### Decision: Один контроллер с DTO и формой Symfony (прямой EM)
Создаётся `UserController` (Symfony attribute `#[Route]` +
`#[IsGranted('ROLE_ADMIN')]`). Запрос связывается с DTO `CreateUserRequest`
(поля: `email`, `name`, `surname`, `role`), валидируется через Symfony Validator,
затем обрабатывается в `UserController` напрямую через `EntityManagerInterface`.
Шаблон совпадает с `OrganizationController` (see `src/Controller/OrganizationController`).

**Альтернатива**: CQRS-команды с MessageBus (проектирование, см. design.md). Отклонено —
кодбейд использует прямые `EntityManagerInterface` вызовы во всех контроллерах;
добавление Messenger было бы лишним слоем абстракции для простой операции.

**Альтернатива 2**: CRUD-controller с генерацией админки (EasyAdmin). Отклонено —
проект не использует EasyAdmin, и спецификация описывает конкретную операцию, а
не CRUD-сет.

### Decision: Поля `name`/`surname` nullable в БД
Doctrine-сущность `User` получает свойства `?string $name = null` и
`?string $surname = null` с `nullable: true`. Миграция добавляет колонки как
nullable, чтобы не ломать существующих пользователей.

**Альтернатива**: NOT NULL с дефолтом `''`. Отклонено — `null` семантически
точнее выражает «не указано» и соответствует спецификации (поля остаются
пустыми).

### Decision: Роль — enum из двух значений
`role` валидируется как `Choice` из `{admin, manager}` (фиксированный набор,
ADR-0009). В БД хранится как строка (или PHP enum-backed value), без таблицы
ролей.

### Decision: Email — обязательное поле, уникальность через Validator
`email` валидируется как обязательное поле с форматом email. Уникальность
проверяется через Symfony Validator (`UniqueEntity` constraint) или
ручной проверкой в контроллере. Пароль не задаётся при создании —
пользователь устанавливает его позже через сброс пароля.

### Decision: Список пользователей — GET /admin/users
`UserController::list` (GET) отображает таблицу всех пользователей с
колонками email, name, surname, role. Кнопка удаления отображается для
всех кроме текущего аутентифицированного администратора. Форма удаления
ведёт на отдельную страницу подтверждения.

### Decision: Персональная группа создаётся в транзакции
`UserController::create` оборачивает persist пользователя и (для `manager`)
создание `OrganizationGroup` с ключом `user-<id>-group` в одну транзакцию
(`wrapInTransaction`). Это гарантирует атомарность: либо пользователь и группа
созданы вместе, либо ничего.

### Decision: Доступ через `#[IsGranted('ROLE_ADMIN')]`
На уровне маршрута/контроллера, а не отдельного voter. Роли фиксированы, и
правило «только admin» не требует domain-логики.

### Decision: Удаление в UserController напрямую через EM
`UserController::delete` (с `#[IsGranted('ROLE_ADMIN')]` и self-deletion guard)
выполняет удаление напрямую через `EntityManagerInterface`, без CQRS-слоя.
Ручная валидация «нельзя удалить себя» выполняется в контроллере до
удаления.

### Decision: Каскадное удаление группы менеджера через FK CASCADE
`OrganizationGroup.ownerUser` имеет `onDelete: 'SET NULL'` (see `src/Entity/OrganizationGroup.php`).
Для соответствия спецификации (группа должна удаляться вместе с `manager`),
миграция изменяет FK на `onDelete: 'CASCADE'`. При удалении `manager`
персональная группа `user-<id>-group` удаляется автоматически на уровне БД.
Для `admin` группа не существует — только удаление пользователя.
Все операции в одной транзакции (`wrapInTransaction`).

**Альтернатива**: Явный `$em->remove($group)` в контроллере (не менять FK). Отклонено —
требует дополнительного запроса на загрузку группы и усложняет логику;
CASCADE на уровне БД чище и надёжнее.

### Decision: Обработка связанных данных при удалении
Связанные звонки, кампании и сделки обрабатываются согласно ADR-0004 и
ADR-0010. Звонки пользователя (`made_by`) имеют `onDelete: 'SET NULL'`,
контакты привязаны к организации (не к пользователю), электронные рассылки
(ADR-0010) — отменены. Реализация привязана к логике существующих сущностей
в ADR-0004/ADR-0010 и не требует новых таблиц.

## Component view (C4, Container/Component level)

```
+-------------------+        +-----------------------+        +---------------------+
|  Admin (browser)  |------>|  UserController       |------>|  UserRepository     |
|  role: admin      |  HTTP  |  (Symfony controller) |  EM    |  (Doctrine)         |
+-------------------+        |  #[IsGranted(ADMIN)]  |        +----------+----------+
                             |  GET/POST/DELETE       |               |
                             |  /admin/users          |               |
                             +-----------+-----------+               |
                                         |                         |
                                         |  tx                     v
                                         |                  +---------------------+
                                         |                  | OrganizationGroup   |
                                         |                  | (CASCADE on delete  |
                                         |                  |  for manager)       |
                                         |                  +---------------------+
                                         v
                                +-------------------+
                                |  MySQL (users,    |
                                |  organization_groups)|
                                +-------------------+
```

Поток создания (role=manager):
1. Admin → POST /admin/users/new (form с email, name?, surname?, role).
2. `UserController` проверяет `ROLE_ADMIN`, валидирует DTO (`email` и `role`
   обязательны, `name`/`surname` опц., `email` уникален).
3. В транзакции:
   a. persist `User` (role из enum).
   b. если `manager` — создать `OrganizationGroup` (`slug=user-<id>-group`,
      `type=GroupType::User`).
   c. flush.
4. Возврат редирект на список `/admin/users`.

Поток списка пользователей:
1. Admin → GET /admin/users.
2. `UserController` проверяет `ROLE_ADMIN`, загружает всех пользователей
   через `UserRepository`.
3. Рендер шаблона `user/list.html.twig` с таблицей пользователей.

Поток удаления:
1. Admin → GET /admin/users/{id}/delete (страница подтверждения).
2. Admin → POST /admin/users/{id}/delete (подтверждение с CSRF).
3. `UserController` проверяет `ROLE_ADMIN`, отклоняет если
   `id == current_user_id` (self-deletion).
4. В транзакции:
   a. remove `User` (DB CASCADE удалит `OrganizationGroup` для manager).
   b. flush.
5. Возврат редирект на список `/admin/users`.

## Risks / Trade-offs

- **[Риск] Гонка при генерации `user-<id>-group` slug** → slug строится из ID
  только что persist-нутого пользователя; уникальность обеспечивается
  unique-индексом на slug + транзакцией. При коллизии (невозможно, т.к. ID
  уникален) — rollback.
- **[Trade-off] nullable поля затрудняют вывод** → принято ради соответствия
  спецификации «остаются пустыми»; в UI/списке пользователей пустые значения
  рендерятся как прочерк.
- **[Риск] Утечка операции менеджеру** → нивелируется `#[IsGranted]` на
  маршруте и тестом `access-control`-сценария (менеджер отклоняется).
- **[Risk] Самоудаление администратором** → нивелируется
  проверкой `id == current_user_id` в контроллере до отправки команды;
  тест покрывает сценарий.
- **[Risk] FK CASCADE на OrganizationGroup** → при удалении manager
  автоматически удаляется группа; нужно убедиться, что нет других данных,
  ссылающихся на группу (GroupAssignment FK уже CASCADE). Миграция
  требует аккуратного применения на продакшене.
- **[Risk] Целостность данных при удалении** → FK SET NULL на `Call.made_by`,
  контакты привязаны к организации; при удалении пользователя звонки остаются
  (made_by → NULL) согласно ADR-0004.
- **[Risk] Уникальность email** → валидация через `UniqueEntity` constraint
  или ручная проверка; при гонке — DB unique index откатит транзакцию.
- **[Trade-off] Пароль не задаётся при создании** → пользователь
  устанавливает пароль через сброс пароля; это требует существующего
  механизма password reset (smpt/outbox в ADR-0010) или отдельной
  функции приглашения (out of scope).
- **[Trade-off] Нет CQRS** → прямой EM простой и соответствует кодбейду;
  сложная логика в будущем может потребовать извлечения в handler.
- **[Trade-off] Нет отдельного voter** → правило простое и не меняется;
  введение voter отложено до появления более сложной ACL (что запрещено
  ADR-0007).
