## ADDED Requirements

### Requirement: Менеджер может массово добавлять организации в рассылку по группе
The system SHALL allow managers to bulk-add all organizations from a specific group they have access to as campaign recipients in a single action. The group selection SHALL be limited to groups the manager has access to (created + assigned custom groups). Organizations already added as recipients SHALL be skipped. Organizations without a deliverable e-mail SHALL be skipped as well (rule "Организация без e-mail не может стать адресатом" applies to every creation path). The result message SHALL report skipped organizations, and SHALL disclose the missing-e-mail count only when such organizations were skipped.

#### Scenario: Менеджер добавляет все организации своей группы в рассылку
- **WHEN** менеджер "Иван Петров" открывает страницу адресатов рассылки "Новые курсы"
- **AND** выбирает действие "Добавить по группе" и выбирает группу "Минский регион"
- **THEN** все организации группы "Минский регион" добавляются как адресаты рассылки
- **AND** организации, уже являющиеся адресатами, пропускаются

#### Scenario: Менеджер не может добавить организации чужой группы
- **WHEN** менеджер "Иван Петров" пытается выполнить массовое добавление по группе "Южный регион", к которой у него нет доступа
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** организации не добавляются

#### Scenario: Пропуск организаций без e-mail при добавлении по группе
- **WHEN** менеджер "Иван Петров" добавляет адресатов по группе "Минский регион"
- **AND** в группе 4 организации, из которых 2 уже являются адресатами, а у 1 нет e-mail ни у одного контакта
- **THEN** организация без e-mail не добавляется в адресаты
- **AND** сообщение о результате содержит «пропущено: 3 (в том числе нет e-mail: 1)»

#### Scenario: Сообщение о результате без упоминания e-mail
- **WHEN** менеджер "Иван Петров" добавляет адресатов по группе, где все пропущенные организации уже являются адресатами
- **THEN** сообщение о результате содержит только «Добавлено: N, пропущено: M» без упоминания e-mail

#### Scenario: Группа без организаций
- **WHEN** менеджер выбирает группу, в которой нет организаций
- **THEN** система не добавляет ни одной организации
- **AND** возвращается сообщение о пустом результате: «В группе «Южный регион» нет организаций — добавлять нечего»

## MODIFIED Requirements

### Requirement: Массовое добавление всех организаций
The system SHALL support bulk-adding all accessible organizations as recipients at once. Each organization SHALL have at most one recipient per campaign (unique constraint on `campaign_id`, `organization_id`). Organizations SHALL be filtered by the manager's access scope (custom groups: created + assigned). Already existing recipients SHALL be skipped. Organizations skipped because they have no deliverable e-mail SHALL be disclosed in the result message as «пропущено: M (в том числе нет e-mail: K)».

#### Scenario: Массовое добавление всех организаций
- **WHEN** менеджер нажимает «Выбрать все организации»
- **THEN** все доступные организации (из созданных и назначенных групп) добавляются как адресаты
- **AND** уже существующие организации пропускаются

#### Scenario: Массовое добавление раскрывает пропуск организаций без e-mail
- **WHEN** менеджер нажимает «Выбрать все организации»
- **AND** среди доступных организаций есть 2 без e-mail у контактов
- **THEN** они не добавляются в адресаты
- **AND** сообщение о результате содержит «(в том числе нет e-mail: 2)»

#### Scenario: Менеджер не может добавить недоступную организацию адресатом
- **WHEN** в системе существует организация «ООО Конкурент», отсутствующая в области доступа менеджера (ни в одной из групп)
- **AND** менеджер пытается добавить её адресатом рассылки
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** организация не включается в получатели
