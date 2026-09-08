# Access Control

Права доступа определяются ролями пользователя и группами организаций.
Менеджер имеет полный доступ к организациям групп, которые он создал
(`created_by`), и к организациям назначенных ему групп; администратор не имеет
собственной группы, видит всё и управляет группами и назначениями (ADR-0011).
Механика аутентификации и проверки текущего пользователя — в спецификации
`authentication`.

## Purpose

Определяет роли пользователей (admin, manager) и правила доступа к
организациям: менеджер видит организации своих групп, администратор — все
организации и группы.

## Requirements

### Requirement: Система определяет роли пользователей
The system SHALL assign every user one of the fixed roles: admin or manager.
The role set SHALL be fixed: creating, changing, or deleting roles is not
supported (Roles CRUD endpoints are not implemented, see ADR-0009).

#### Scenario: Назначение роли при создании пользователя
- **WHEN** администратор создаёт пользователя "Мария Смирнова" и назначает ей роль manager
- **THEN** пользователь "Мария Смирнова" получает роль manager

#### Scenario: Система отклоняет создание произвольной роли
- **WHEN** аутентифицированный администратор пытается создать новую роль "supervisor"
- **THEN** система отклоняет операцию создания роли

### Requirement: Менеджер имеет полный доступ к организациям своих групп
A manager SHALL have full access to all organizations in the groups they created
(`OrganizationGroup.created_by`) and in the groups assigned to them
(`GroupAssignment`), including their contacts, calls, and campaigns. Personal
`user-<id>-group` records are eliminated (ADR-0011).

#### Scenario: Менеджер получает доступ к организациям назначенных групп
- **WHEN** менеджер "Иван Петров" создал группу "Берестейский регион" и ему назначена custom-группа "Минский регион" и открывает список организаций
- **THEN** он видит организации группы "Берестейский регион" и группы "Минский регион"

#### Scenario: Менеджер не видит организации вне своих групп
- **WHEN** менеджер "Иван Петров" не имеет доступа к организации "ООО Конкурент" и выполняет поиск организаций
- **THEN** организация "ООО Конкурент" не отображается в результатах

### Requirement: Администратор видит все организации и группы
The administrator SHALL see all organizations and groups and SHALL manage
group membership and manager assignments. The administrator SHALL NOT have a
personal `user-<id>-group`; the access check for an administrator SHALL skip
groups.

#### Scenario: Админ видит все организации
- **WHEN** в системе существуют организации в разных группах и администратор открывает список организаций
- **THEN** он видит все организации без ограничений

#### Scenario: Админу не создаётся собственная группа
- **WHEN** администратор создаёт пользователя с ролью admin
- **THEN** для пользователя с ролью admin группа `user-<id>-group` не создаётся

#### Scenario: Админ управляет назначениями групп
- **WHEN** аутентифицированный администратор назначает и снимает группы менеджерам
- **THEN** изменения доступа применяются сразу
