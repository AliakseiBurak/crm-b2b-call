## ADDED Requirements

### Requirement: Список контактов на форме редактирования организации
The system SHALL display the organization's contacts on the organization edit form, each with a link to the contact edit page.

#### Scenario: Отображение связанных контактов
- **WHEN** пользователь открывает форму редактирования организации "ООО Ромашка"
- **AND** у организации есть контакты "Иван Петров" и "Мария Смирнова"
- **THEN** под формой отображается список обоих контактов
- **AND** каждый контакт является ссылкой на `GET /contacts/{id}/edit`

#### Scenario: Организация без контактов
- **WHEN** пользователь открывает форму редактирования организации без контактов
- **THEN** список контактов пуст или показывает, что контактов нет

### Requirement: Таблица ошибок рассылок на форме организации
The system SHALL display a table of campaign delivery errors on the organization edit form for all recipients of this organization with status `failed` or `bounced` when the campaign is not `archived`, including both org-wide recipients (no contact) and recipients tied to a specific contact. The table SHALL show campaign name, contact (name with link to contact edit, or "—" when unset), status, error message, and a reset action. Reset SHALL use the same rules as on the contact form (confirm dialog for `failed`, confirmation page then `POST …/reset` for `bounced`). Cancelling bounced confirmation SHALL return to the organization edit form when the user started from there.

#### Scenario: Отображение org-wide failed
- **WHEN** пользователь открывает форму редактирования организации "ООО Ромашка"
- **AND** существует получатель этой организации без контакта со статусом `failed`
- **AND** рассылка не `archived`
- **THEN** на форме отображается таблица ошибок с этим получателем
- **AND** в колонке контакта отображается "—"
- **AND** есть кнопка "Сбросить"

#### Scenario: Отображение ошибки контакта организации
- **WHEN** пользователь открывает форму редактирования организации "ООО Ромашка"
- **AND** существует получатель с контактом "Иван Петров" и статусом `failed`
- **AND** рассылка не `archived`
- **THEN** в таблице ошибок есть строка этого получателя
- **AND** в колонке контакта отображается "Иван Петров" как ссылка на `GET /contacts/{id}/edit`

#### Scenario: Отображение bounced получателя
- **WHEN** пользователь открывает форму редактирования организации "ООО Ромашка"
- **AND** существует получатель этой организации со статусом `bounced`
- **AND** рассылка не `archived`
- **THEN** в таблице ошибок есть строка со статусом "Отказ" и кнопкой "Сбросить"

#### Scenario: Архивные рассылки исключены
- **WHEN** существует получатель организации со статусом `failed` у архивной рассылки
- **AND** пользователь открывает форму редактирования организации
- **THEN** этот получатель не отображается в таблице ошибок

#### Scenario: Таблица не отображается без ошибок
- **WHEN** пользователь открывает форму редактирования организации "ООО Ромашка"
- **AND** нет получателей этой организации со статусами `failed` или `bounced` в неархивных рассылках
- **THEN** таблица ошибок не отображается
