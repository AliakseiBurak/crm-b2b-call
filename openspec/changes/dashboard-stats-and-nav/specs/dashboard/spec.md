## Purpose

Статистика обзвона на дашборде из реальных данных звонков с учётом области доступа пользователя.

## ADDED Requirements

### Requirement: Реальная статистика обзвона на дашборде
The system SHALL render on the dashboard six statistics figures computed
from actual call data: three "called" figures counting calls whose call
date (`made_at`) falls on the current calendar day, within the last 7
calendar days and within the last 30 calendar days, and three "waiting"
figures counting calls whose scheduled date (`scheduled_at`) falls on the
current calendar day, within the next 7 days and within the next 30 days.
Figures SHALL update automatically when call data changes and SHALL NOT be
hardcoded.

#### Scenario: Дашборд показывает факты звонков за периоды
- **WHEN** в системе существуют звонки с фактом звонка сегодня, вчера и 10 дней назад
- **AND** пользователь открывает дашборд
- **THEN** на дашборде отображается число звонков, сделанных сегодня
- **AND** на дашборде отображается число звонков, сделанных за последние 7 дней
- **AND** на дашборде отображается число звонков, сделанных за последние 30 дней

#### Scenario: Дашборд показывает запланированные звонки
- **WHEN** в системе существуют звонки, запланированные на сегодня, на через 3 дня и на через 20 дней
- **AND** пользователь открывает дашборд
- **THEN** на дашборде отображается число звонков, ожидающих обзвона сегодня
- **AND** на дашборде отображается число звонков, ожидающих обзвона в течение недели
- **AND** на дашборде отображается число звонков, ожидающих обзвона в течение месяца

#### Scenario: Отсутствие захардкоженных значений
- **WHEN** в системе не существует ни одного звонка
- **AND** пользователь открывает дашборд
- **THEN** все шесть показателей отображают нулевые значения
- **AND** ни один показатель не отображает значение, отсутствующее в данных

### Requirement: Область доступа статистики
The system SHALL compute dashboard statistics within the user's access
scope: an administrator SHALL see statistics across all organizations,
while a manager SHALL see statistics only for organizations in their own
group (`user-<id>-group`) and in the custom groups assigned to them
(`adr/0007`). The dashboard SHALL NOT display statistics of organizations
outside the user's access scope.

#### Scenario: Менеджер видит статистику своей области доступа
- **WHEN** в системе существуют организации "ООО Ромашка" и "ООО Конкурент"
- **AND** организация "ООО Ромашка" входит в область доступа менеджера, а "ООО Конкурент" нет
- **AND** в обеих организациях зафиксированы звонки
- **THEN** на дашборде менеджера учитываются только звонки организации "ООО Ромашка"
- **AND** звонки организации "ООО Конкурент" не влияют на показатели дашборда

#### Scenario: Администратор видит всю статистику
- **WHEN** в системе существуют организации в разных группах с зафиксированными звонками
- **AND** администратор открывает дашборд
- **THEN** показатели дашборда учитывают звонки всех организаций