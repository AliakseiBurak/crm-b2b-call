# Organization Groups

Группы организаций распределяют организации между менеджерами. У каждого
менеджера есть собственная группа (`user-<id>-group`), создаваемая
автоматически; администратор создаёт дополнительные группы (custom) с
именованными slug-именами и назначает их менеджерам. Администратор не имеет
собственной группы. Организация может состоять в нескольких группах
одновременно (`OrganizationGroupMembership`). Длительные решения зафиксированы
в ADR-0005 и ADR-0006.

## Purpose

Группы организаций распределяют организации между менеджерами: группа
`user-<id>-group` создаётся автоматически, custom-группы создаёт и назначает
администратор, членство организации в группах — many-to-many.

## Requirements

### Requirement: При создании менеджера автоматически создаётся его собственная группа
The system SHALL automatically create a group named `user-<id>-group` when a
manager is created, and organizations created by that manager SHALL land in
that group automatically.

#### Scenario: Создание менеджера создаёт его группу
- **WHEN** администратор создаёт менеджера "Иван Петров"
- **THEN** в системе появляется группа `user-<id>-group`, связанная с менеджером "Иван Петров"

#### Scenario: Новая организация менеджера попадает в его группу
- **WHEN** у менеджера "Иван Петров" есть собственная группа
- **AND** менеджер создаёт организацию "ООО Ромашка"
- **THEN** организация "ООО Ромашка" добавляется в его собственную группу

### Requirement: Администратор не имеет собственной группы
The administrator SHALL NOT have a personal `user-<id>-group`; the access check
for an administrator SHALL skip groups entirely.

#### Scenario: Админу группа не создаётся
- **WHEN** администратор создаёт пользователя с ролью admin
- **THEN** для этого пользователя группа `user-<id>-group` не создаётся

#### Scenario: Проверка доступа администратора не обращается к группам
- **WHEN** администратор открывает список организаций
- **THEN** он видит все организации без проверки групп

### Requirement: Администратор управляет custom-группами
The administrator SHALL be able to create, modify, and delete custom groups
with a name and a slug, assign them to managers, and add organizations to
groups. Adding an organization to a group SHALL NOT remove it from other
groups: an organization MAY belong to several groups at once.

#### Scenario: Админ создаёт custom-группу и назначает её менеджеру
- **WHEN** аутентифицированный администратор создаёт custom-группу "Минский регион" с slug `minsk-region-group` и назначает её менеджеру "Иван Петров"
- **THEN** менеджер "Иван Петров" получает доступ к организациям группы "Минский регион"

#### Scenario: Админ добавляет организацию в несколько групп
- **WHEN** организация "ООО Ромашка" состоит в группе "Минский регион"
- **AND** администратор добавляет её в группу "Южный регион"
- **THEN** организация "ООО Ромашка" состоит в группах "Минский регион" и "Южный регион"
- **AND** организация "ООО Ромашка" видна менеджерам обеих групп

#### Scenario: Удаление организации из группы не влияет на другие группы
- **WHEN** организация "ООО Ромашка" состоит в группах "Минский регион" и "Южный регион"
- **AND** администратор удаляет её из группы "Минский регион"
- **THEN** организация "ООО Ромашка" продолжает состоять в группе "Южный регион"

### Requirement: Членство организации в группах является many-to-many
An organization SHALL be able to belong to several groups at once through
`OrganizationGroupMembership`, and one group MAY be assigned to several
managers through `GroupAssignment`.

#### Scenario: Одна группа назначается нескольким менеджерам
- **WHEN** custom-группа "Минский регион" назначена менеджеру "Иван Петров"
- **AND** администратор назначает группу "Минский регион" менеджеру "Мария Смирнова"
- **THEN** оба менеджера имеют доступ к организациям группы "Минский регион"

### Requirement: Группа менеджера не удаляется и не назначается другим
The manager's `user-<id>-group` SHALL NOT be deletable while the manager
exists, and SHALL NOT be assignable to other managers (`adr/0005`); shared work
SHALL use custom groups.

#### Scenario: Группу менеджера нельзя удалить
- **WHEN** существует менеджер "Иван Петров" с группой `user-<id>-group`
- **AND** администратор пытается удалить эту группу
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** группа остаётся связанной с менеджером "Иван Петров"

#### Scenario: Личную группу менеджера нельзя назначить другому менеджеру
- **WHEN** существует группа `user-<id>-group` менеджера "Иван Петров"
- **AND** администратор пытается назначить эту группу менеджеру "Мария Смирнова"
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** группа остаётся доступной только менеджеру "Иван Петров"

### Requirement: Управление группами доступно только администратору
The group management UI (creating custom groups, assignments, and membership
of organizations) SHALL be visible and available only to the administrator;
managers SHALL NOT see it (`adr/0008`).

#### Scenario: Менеджер не видит раздел управления группами
- **WHEN** менеджер открывает главное меню
- **THEN** он не видит раздел управления группами и назначениями

#### Scenario: Менеджер не может создать custom-группу
- **WHEN** менеджер выполняет прямой запрос на создание custom-группы
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** группа не создаётся
