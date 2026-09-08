## REMOVED Requirements

### Requirement: При создании менеджера автоматически создаётся его собственная группа
The system SHALL NOT automatically create a personal group (`user-<id>-group`) when a manager is created. Personal groups are eliminated.

#### Scenario: Удаление личных групп
- **WHEN** система обновляется с версии, содержащей личные группы
- **THEN** все группы формата `user-<id>-group` удаляются
- **AND** организации, состоявшие в этих группах, становятся безгрупповыми

## ADDED Requirements

### Requirement: Менеджер управляет своими custom-группами
The system SHALL allow managers to create, edit, and delete custom groups. Managers SHALL have full CRUD access to groups they created (`created_by = self`). Managers SHALL NOT be able to edit or delete groups created by other managers or by the administrator.

#### Scenario: Менеджер создаёт custom-группу
- **WHEN** менеджер "Иван Петров" создаёт группу "Минский регион" с описанием и цветом
- **THEN** группа создаётся с `created_by = current_manager`
- **AND** группа видна менеджеру "Иван Петров" в списке групп

#### Scenario: Менеджер редактирует свою группу
- **WHEN** менеджер "Иван Петров" редактирует свою группу "Минский регион"
- **THEN** изменения сохраняются
- **AND** группа остаётся доступной другим менеджерам, которым она назначена

#### Scenario: Менеджер удаляет свою группу
- **WHEN** менеджер "Иван Петров" удаляет свою группу "Минский регион"
- **THEN** группа удаляется
- **AND** членство организаций в группе удаляется
- **AND** назначения группы другим менеджерам удаляются

#### Scenario: Менеджер не может редактировать чужую группу
- **WHEN** менеджер "Иван Петров" пытается отредактировать группу "Южный регион", созданную другим менеджером
- **THEN** система отклоняет запрос с ошибкой 403

#### Scenario: Менеджер не может удалить чужую группу
- **WHEN** менеджер "Иван Петров" пытается удалить группу "Южный регион", созданную другим менеджером
- **THEN** система отклоняет запрос с ошибкой 403

### Requirement: Менеджер управляет членством организаций в своих группах
The system SHALL allow managers to add and remove organizations from groups they created. Managers SHALL be able to add organizations they have access to into their groups.

#### Scenario: Менеджер добавляет организацию в свою группу
- **WHEN** менеджер "Иван Петров" открывает страницу своей группы "Минский регион"
- **AND** добавляет организацию "ООО Ромашка"
- **THEN** организация "ООО Ромашка" добавляется в группу "Минский регион"

#### Scenario: Менеджер удаляет организацию из своей группы
- **WHEN** менеджер "Иван Петров" удаляет организацию "ООО Ромашка" из группы "Минский регион"
- **THEN** организация "ООО Ромашка" больше не состоит в группе "Минский регион"
- **AND** организация остаётся в других группах

### Requirement: Группы имеют метаданные для использования в рассылках
Each organization group SHALL support optional metadata fields: a description field (text, nullable) and a color field (hex string, VARCHAR(7), nullable). These fields SHALL be manageable by administrators and by managers for their own groups.

#### Scenario: Менеджер задаёт описание и цвет для своей группы
- **WHEN** менеджер "Иван Петров" редактирует свою группу "Минский регион" и заполняет описание "Организации Минской области" и цвет "#3b82f6"
- **THEN** описание и цвет сохраняются
- **AND** менеджер видит группу с описанием и цветом в интерфейсе выбора для рассылки

### Requirement: При создании организации можно добавить её в группы
When creating an organization, the system SHALL display checkboxes for available groups. For managers: groups where `created_by = self` OR assigned. For admin: all groups. The organization SHALL be added to checked groups. If no groups checked, the organization exists ungrouped.

#### Scenario: Менеджер создаёт организацию и выбирает группы
- **WHEN** менеджер "Иван Петров" создаёт организацию "ООО Ромашка"
- **AND** отмечает группы "Минский регион" и "Южный регион"
- **THEN** организация "ООО Ромашка" добавляется в обе группы

#### Scenario: Менеджер создаёт организацию без выбора группы
- **WHEN** менеджер "Иван Петров" создаёт организацию "ООО Ромашка" без выбора групп
- **THEN** организация создаётся без групповой принадлежности

#### Scenario: Админ видит все группы при создании организации
- **WHEN** администратор создаёт организацию
- **THEN** он видит все доступные группы в списке чекбоксов

### Requirement: При удалении менеджера администратор выбирает судьбу его групп
When deleting a manager, the system SHALL display a warning listing all groups created by that manager. The administrator SHALL be forced to choose per-group: "Reassign to Admin" (changes `created_by` to admin, keeps group and assignments) or "Delete group" (removes group, membership, and assignments).

#### Scenario: Администратор переназначает группы при удалении менеджера
- **WHEN** администратор удаляет менеджера "Иван Петров", создавшего группы "Минский регион" и "Южный регион"
- **AND** выбирает "Переназначить администратору" для группы "Минский регион"
- **AND** выбирает "Удалить группу" для группы "Южный регион"
- **THEN** группа "Минский регион" переназначается администратору (`created_by = admin`)
- **AND** группа "Южный регион" удаляется вместе с членством и назначениями

#### Scenario: Администратор не может удалить менеджера без выбора для каждой группы
- **WHEN** администратор пытается подтвердить удаление менеджера без выбора действия для группы
- **THEN** система отклоняет запрос с ошибкой

## MODIFIED Requirements

### Requirement: Администратор управляет custom-группами
The administrator SHALL be able to create, modify, and delete ALL custom groups (including manager-owned groups), with a name, an optional description, and an optional hex color, and add organizations to groups. Assignment of groups to managers (`GroupAssignment` management UI) is out of scope for this change and is delivered by the follow-up `organization-group-assignment` change. Adding an organization to a group SHALL NOT remove it from other groups: an organization MAY belong to several groups at once.

#### Scenario: Админ создаёт custom-группу
- **WHEN** аутентифицированный администратор создаёт custom-группу "Минский регион" с описанием "Организации Минской области" и цветом "#3b82f6"
- **THEN** группа сохраняется с `created_by = admin`
- **AND** группа видна в списке групп администратора

#### Scenario: Админ видит создателя группы
- **WHEN** администратор открывает список групп
- **THEN** он видит колонку "Создатель" с указанием имени создателя группы

#### Scenario: Админ добавляет организацию в несколько групп
- **WHEN** организация "ООО Ромашка" состоит в группе "Минский регион"
- **AND** администратор добавляет её в группу "Южный регион"
- **THEN** организация "ООО Ромашка" состоит в группах "Минский регион" и "Южный регион"

#### Scenario: Удаление организации из группы не влияет на другие группы
- **WHEN** организация "ООО Ромашка" состоит в группах "Минский регион" и "Южный регион"
- **AND** администратор удаляет её из группы "Минский регион"
- **THEN** организация "ООО Ромашка" продолжает состоять в группе "Южный регион"

### Requirement: Членство организации в группах является many-to-many
An organization SHALL be able to belong to several groups at once through `OrgGroupMembership`, and one group MAY be assigned to several managers through `GroupAssignment`.

#### Scenario: Одна группа назначена нескольким менеджерам
- **WHEN** группа "Минский регион" создана менеджером "Иван Петров" и назначена (`GroupAssignment`) менеджеру "Мария Смирнова"
- **THEN** обоим менеджерам доступны организации группы "Минский регион"

### Requirement: Управление группами доступно менеджерам и администратору
The group management UI SHALL be visible to both administrators and managers. Managers SHALL see "Мои группы" menu item with CRUD access to their own groups. Administrators SHALL see all groups with full management access.

#### Scenario: Менеджер видит раздел "Мои группы"
- **WHEN** менеджер открывает главное меню
- **THEN** он видит раздел "Мои группы"

#### Scenario: Менеджер не видит чужие группы в управлении
- **WHEN** менеджер "Иван Петров" открывает раздел "Мои группы"
- **THEN** он видит только группы, которые создал сам, и группы, назначенные ему администратором
