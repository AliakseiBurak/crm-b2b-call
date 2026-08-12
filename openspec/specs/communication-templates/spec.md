# Communication Templates

Шаблоны коммуникаций — готовые тексты для писем рассылок (см. `campaigns`) и
быстрых ответов. Шаблон содержит токены (`{{contact_name}}`,
`{{organization_name}}`) и, опционально, встроенные курсы (описания и PDF из
`courses`).

## Purpose

Готовые тексты с токенами и встроенными курсами для писем рассылок и быстрых
ответов; используются менеджерами и администратором.

## Requirements

### Requirement: Управление шаблонами коммуникаций
The system SHALL allow creating, viewing, and modifying communication templates
that store the letter structure (subject and body) with tokens and optional
embedded courses.

#### Scenario: Создание шаблона
- **WHEN** аутентифицированный пользователь создаёт шаблон "Знакомство с курсами" с темой и телом письма
- **THEN** шаблон "Знакомство с курсами" появляется в списке шаблонов

#### Scenario: Просмотр списка шаблонов
- **WHEN** в системе есть шаблоны "Знакомство с курсами" и "Напоминание о звонке"
- **AND** пользователь открывает список шаблонов коммуникаций
- **THEN** он видит оба шаблона

### Requirement: Токены в шаблоне
The system SHALL fill template tokens with the contact name and the
organization name when generating a letter.

#### Scenario: Подстановка имени организации и контакта
- **WHEN** шаблон "Знакомство с курсами" содержит токены `{{organization_name}}` и `{{contact_name}}`
- **AND** система формирует письмо организации "ООО Ромашка" для контакта "Иван Петров"
- **THEN** в письме токены заменены на "ООО Ромашка" и "Иван Петров"

### Requirement: Встроенные курсы в шаблоне
The system SHALL support embedding course descriptions and PDFs in a template,
filled from the course catalog (see `courses`).

#### Scenario: Курс в шаблоне
- **WHEN** шаблон "Знакомство с курсами" включает курс "Python для анализа данных"
- **AND** система формирует письмо из шаблона
- **THEN** в письмо включено описание курса и прикреплён PDF

### Requirement: Использование шаблонов для быстрых ответов
Managers SHALL use templates for quick replies to contacts without retyping
text.

#### Scenario: Быстрый ответ по шаблону
- **WHEN** существует шаблон "Знакомство с курсами"
- **AND** менеджер выбирает шаблон для ответа контакту
- **THEN** письмо контакту формируется из темы и тела шаблона "Знакомство с курсами"
