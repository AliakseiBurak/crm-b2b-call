# Access Control

Права доступа определяются ролями пользователя и группами организаций.
Менеджер имеет полный доступ к организациям своей группы (`user-<id>-group`)
и назначенных custom-групп; администратор не имеет собственной группы, видит
всё и управляет группами и назначениями.

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
A manager SHALL have full access to all organizations in their `user-<id>-group`
and all assigned custom groups, including their contacts, calls, and campaigns.

#### Scenario: Менеджер получает доступ к организациям назначенных групп
- **WHEN** менеджер "Иван Петров" имеет собственную группу и назначенную custom-группу "Минский регион" и открывает список организаций
- **THEN** он видит организации своей группы и группы "Минский регион"

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
