## Purpose

Определяет процедуру удаления пользователя системы администратором: какие
условия должны быть соблюдены, что происходит с персональной группой
при удалении менеджера и как обеспечивается доступ к операции удаления.

## Requirements

### Requirement: Удалять пользователя может только администратор
The system SHALL allow deleting a user only to an authenticated
administrator. Managers and anonymous users SHALL NOT be permitted to
delete users.

#### Scenario: Администратор удаляет пользователя
- **WHEN** аутентифицированный администратор отправляет запрос на удаление пользователя
- **THEN** система удаляет пользователя

#### Scenario: Менеджер не может удалять пользователей
- **WHEN** аутентифицированный менеджер пытается выполнить запрос удаления пользователя
- **THEN** система отклоняет операцию с ошибкой доступа

#### Scenario: Анонимный пользователь не может удалять пользователей
- **WHEN** неаутентифицированный пользователь пытается выполнить запрос удаления пользователя
- **THEN** система возвращает ошибку аутентификации

### Requirement: Администратор не может удалить самого себя
The system SHALL reject an administrator's request to delete their own
account; the administrator SHALL remain authenticated and the system
SHALL return an error indicating self-deletion is not permitted.

#### Scenario: Администратор пытается удалить себя
- **WHEN** аутентифицированный администратор пытается удалить собственную учётную запись
- **THEN** система отклоняет операцию с ошибкой валидации и не удаляет учётную запись

### Requirement: Судьба групп менеджера выбирается при удалении
The system SHALL NOT automatically delete groups when a manager is deleted;
for every group created by the manager (`created_by`) the administrator MUST
choose per-group: reassign to the administrator or delete (ADR-0011,
capability `organization-groups`). When a user with role `admin` is deleted,
no group ownership change occurs (ADR-0008).

#### Scenario: Удаление менеджера требует выбора для его групп
- **WHEN** администратор удаляет пользователя с ролью manager, создавшего группы
- **THEN** система требует выбрать действие для каждой группы (переназначить или удалить)
- **AND** пользователь удаляется только после выбора

#### Scenario: Удаление администратора не затрагивает группы
- **WHEN** администратор удаляет пользователя с ролью admin
- **THEN** система удаляет пользователя; группы не затрагиваются

### Requirement: Удаление каскадно — связанные данные обрабатываются
The system SHALL handle all related data when deleting a user. Calls,
campaigns, deals and contacts belonging to the deleted user SHALL be
reassigned or removed according to the data model rules (ADR-0004,
ADR-0010). The deletion SHALL execute within a transaction.

#### Scenario: Удаление пользователя с историей звонков
- **WHEN** администратор удаляет пользователя, имеющего связанные
  звонки и кампании
- **THEN** система удаляет пользователя и обрабатывает связанные
  сущности согласно модели данных (ADR-0004, ADR-0010)

#### Scenario: Удаление пользователя в транзакции
- **WHEN** администратор удаляет пользователя
- **THEN** удаление пользователя и связанных данных выполняется в
  одной транзакции; при ошибке все изменения откатываются
