## ADDED Requirements

### Requirement: Менеджер может массово добавлять организации в рассылку по группе
The system SHALL allow managers to bulk-add all organizations from a specific group they have access to as campaign recipients in a single action. The group selection SHALL be limited to groups the manager has access to (created + assigned custom groups). Organizations already added as recipients SHALL be skipped.

#### Scenario: Менеджер добавляет все организации своей группы в рассылку
- **WHEN** менеджер "Иван Петров" открывает страницу адресатов рассылки "Новые курсы"
- **AND** выбирает действие "Добавить по группе" и выбирает группу "Минский регион"
- **THEN** все организации группы "Минский регион" добавляются как адресаты рассылки
- **AND** организации, уже являющиеся адресатами, пропускаются

#### Scenario: Менеджер не может добавить организации чужой группы
- **WHEN** менеджер "Иван Петров" пытается выполнить массовое добавление по группе "Южный регион", к которой у него нет доступа
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** организации не добавляются

#### Scenario: Группа без организаций
- **WHEN** менеджер выбирает группу, в которой нет организаций
- **THEN** система не добавляет ни одной организации
- **AND** возвращается сообщение о пустом результате

## MODIFIED Requirements

### Requirement: Массовое добавление всех организаций
The system SHALL support bulk-adding all accessible organizations as recipients at once. Each organization SHALL have at most one recipient per campaign (unique constraint on `campaign_id`, `organization_id`). Organizations SHALL be filtered by the manager's access scope (custom groups: created + assigned). Already existing recipients SHALL be skipped.

#### Scenario: Массовое добавление всех организаций
- **WHEN** менеджер нажимает «Выбрать все организации»
- **THEN** все доступные организации (из созданных и назначенных групп) добавляются как адресаты
- **AND** уже существующие организации пропускаются

#### Scenario: Менеджер не может добавить недоступную организацию адресатом
- **WHEN** в системе существует организация «ООО Конкурент», отсутствующая в области доступа менеджера (ни в одной из групп)
- **AND** менеджер пытается добавить её адресатом рассылки
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** организация не включается в получатели
