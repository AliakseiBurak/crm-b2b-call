# Spec: dashboard (statistics by organization)

## Purpose

Дашборд раскрывает девять агрегированных показателей статистики до уровня
организаций: три группы («Звонков», «Ожидают», «Просроченные») по три
периода каждая, с карточкой общего числа организаций, индикаторами по
организациям и навигацией на панель организаций.

## ADDED Requirements

### Requirement: Карточка общего числа организаций
The system SHALL render above the statistics figures a full-width card
displaying «Доступно организаций: Y», where Y SHALL be the total number of
organizations in the user's access scope. For an administrator Y SHALL be
the total number of organizations in the system. Y SHALL be computed once
and displayed as a single card, not repeated under each figure.

#### Scenario: Карточка показывает общее число организаций
- **WHEN** вошедший пользователь открывает домашнюю страницу
- **THEN** над секцией статистики отображается карточка «Доступно организаций: Y»
- **AND** Y — число организаций области доступа пользователя

#### Scenario: Администратор видит все организации
- **WHEN** администратор открывает домашнюю страницу
- **THEN** в карточке отображается Y = общее число организаций системы

#### Scenario: Карточка не отображается для гостей
- **WHEN** неаутентифицированный посетитель открывает домашнюю страницу
- **THEN** карточка «Доступно организаций» не отображается

### Requirement: Индикаторы статистики по организациям
The system SHALL render under each of the nine dashboard statistics figures a
«По организациям: N» indicator link, where N SHALL be the number of distinct
organizations of the user's access scope having calls of that figure's
category. For the called figures the category SHALL be determined by the call
date (`made_at`), for the waiting figures — by the scheduled date
(`scheduled_at`), within the same periods as the figure. For the overdue
figures the category SHALL be determined by the scheduled date
(`scheduled_at`) where `made_at IS NULL`, within the same periods as the
figure. The indicator SHALL be rendered as an anchor element linking to the
organizations panel with a `filter` query parameter identifying the bucket.
The indicator SHALL be rendered even when N equals zero.

#### Scenario: Индикатор по организациям под показателем
- **WHEN** пользователь открывает домашнюю страницу
- **THEN** под каждым из девяти показателей статистики отображается ссылка «По организациям: N»
- **AND** в качестве N отображается число уникальных организаций области доступа со звонками этой категории
- **AND** ссылка содержит параметр `filter` с ключом категории

#### Scenario: Пустая категория
- **WHEN** в области доступа пользователя нет организаций со звонками категории
- **AND** пользователь открывает домашнюю страницу
- **THEN** под показателем отображается «По организациям: 0»
- **AND** ссылка присутствует и ведёт на панель организаций с параметром filter

#### Scenario: Навигация на панель организаций
- **WHEN** пользователь кликает по индикатору категории
- **THEN** происходит переход на `/dashboard?filter=<bucket>`, где `<bucket>` — ключ категории (called1, called7, called30, waiting1, waiting7, waiting30, overdue1, overdue7, overdue30)

### Requirement: Статистика просроченных звонков
The system SHALL render three overdue statistics figures counting distinct
organizations having calls with a scheduled date (`scheduled_at`) in the past
and no call date (`made_at IS NULL`). The three periods SHALL be: yesterday
(00:00–23:59 of the previous calendar day), last 7 days (today minus 6 days
through yesterday), and last 30 days (today minus 29 days through yesterday).
Figures SHALL update automatically when call data changes and SHALL NOT be
hardcoded.

#### Scenario: Просроченные вчера
- **WHEN** в системе существуют организации с запланированными звонками на вчера без `made_at`
- **AND** пользователь открывает домашнюю страницу
- **THEN** отображается показатель «Просроченные: вчера» с числом уникальных организаций области доступа

#### Scenario: Просроченные за 7 дней
- **WHEN** в системе существуют организации с запланированными звонками за последние 7 дней без `made_at`
- **AND** пользователь открывает домашнюю страницу
- **THEN** отображается показатель «Просроченные: за 7 дней» с числом уникальных организаций области доступа

#### Scenario: Просроченные за 30 дней
- **WHEN** в системе существуют организации с запланированными звонками за последние 30 дней без `made_at`
- **AND** пользователь открывает домашнюю страницу
- **THEN** отображается показатель «Просроченные: за 30 дней» с числом уникальных организаций области доступа

#### Scenario: Организация с просроченным и совершённым звонком
- **WHEN** организация «Ромашка» имеет запланированный звонок на вчера без `made_at` и совершённый звонок сегодня
- **THEN** организация «Ромашка» учитывается в «Просроченные: вчера»
- **AND** организация «Ромашка» учитывается в «Звонков сегодня»

#### Scenario: Организация с частично нереализованными звонками
- **WHEN** организация «Вектор» запланировала 5 звонков на вчера, из них 3 совершены, 2 — нет
- **THEN** организация «Вектор» учитывается в «Просроченные: вчера»

### Requirement: Исключающая логика waiting-категорий
The system SHALL NOT count an organization in a waiting figure when that
organization has at least one call with a call date (`made_at`) within the
same period as the waiting figure. Specifically: an organization having a
call with `made_at` on the current day SHALL NOT appear in `waitingToday`;
an organization having a call with `made_at` within the last 7 days SHALL
NOT appear in `waitingWeek`; an organization having a call with `made_at`
within the last 30 days SHALL NOT appear in `waitingMonth`. An organization
MAY appear in both a called figure and a waiting figure of a different period
(e.g., called today AND waiting this week). The overdue figures SHALL be
independent: an organization MAY appear in both an overdue figure and a
called figure simultaneously.

#### Scenario: Организация с звонком сегодня не учитывается в «Ожидают сегодня»
- **WHEN** в области доступа пользователя организация «Ромашка» имеет звонок с `made_at` сегодня
- **AND** та же организация «Ромашка» имеет запланированный звонок с `scheduled_at` сегодня
- **THEN** организация «Ромашка» учитывается в показателе «Звонков сегодня»
- **AND** организация «Ромашка» НЕ учитывается в показателе «Ожидают сегодня»

#### Scenario: Организация с звонком за неделю не учитывается в «Ожидают на неделе»
- **WHEN** организация имеет звонок с `made_at` 5 дней назад
- **AND** та же организация имеет запланированный звонок через 3 дня
- **THEN** организация учитывается в «Звонков за 7 дней»
- **AND** организация НЕ учитывается в «Ожидают на неделе»

#### Scenario: Организация с звонком за месяц не учитывается в «Ожидают в месяце»
- **WHEN** организация имеет звонок с `made_at` 20 дней назад
- **AND** та же организация имеет запланированный звонок через 15 дней
- **THEN** организация учитывается в «Звонков за 30 дней»
- **AND** организация НЕ учитывается в «Ожидают в месяце»

#### Scenario: Организация в разных периодах — разрешено
- **WHEN** организация имеет звонок с `made_at` сегодня
- **AND** та же организация имеет запланированный звонок через 10 дней
- **THEN** организация учитывается в «Звонков сегодня»
- **AND** организация учитывается в «Ожидают на неделе»

### Requirement: Переименование подписей показателей
The system SHALL render the nine dashboard statistics figures with the
following captions: «Звонков сегодня», «Звонков за 7 дней», «Звонков за
30 дней», «Ожидают сегодня», «Ожидают на неделе», «Ожидают в месяце»,
«Просроченные: вчера», «Просроченные: за 7 дней», «Просроченные: за
30 дней». The previous captions («Обзвонено сегодня», «Сегодня», «В
течение недели», «В течение месяца») SHALL NOT be used.

#### Scenario: Подписи показателей обновлены
- **WHEN** пользователь открывает домашнюю страницу
- **THEN** подписи показателей содержат «Звонков сегодня», «Звонков за 7 дней», «Звонков за 30 дней»
- **AND** подписи показателей «ждут» содержат «Ожидают сегодня», «Ожидают на неделе», «Ожидают в месяце»
- **AND** подписи просроченных содержат «Просроченные: вчера», «Просроченные: за 7 дней», «Просроченные: за 30 дней»
- **AND** старые подписи «Обзвонено сегодня», «Сегодня», «В течение недели», «В течение месяца» отсутствуют

### Requirement: Область доступа индикаторов
The system SHALL compute the by-organization indicators, the total
organizations card and the navigation links within the user's access scope:
an administrator SHALL see organizations and indicators across all
organizations, while a manager SHALL see only the organizations in their own
group (`user-<id>-group`) and in the custom groups assigned to them
(`adr/0007`). Negative or foreign organizations SHALL NOT affect the
indicators.

#### Scenario: Менеджер видит индикаторы только своей области доступа
- **WHEN** в системе существуют организации в области доступа менеджера и вне её
- **AND** в обеих группах организаций зафиксированы звонки
- **AND** менеджер открывает домашнюю страницу
- **THEN** индикаторы «По организациям» учитывают только организации области доступа менеджера
- **AND** организации вне области доступа не влияют на числа индикаторов
- **AND** карточка «Доступно организаций» показывает Y = число организаций области доступа менеджера

#### Scenario: Администратор видит индикаторы по всем организациям
- **WHEN** администратор открывает домашнюю страницу
- **THEN** индикаторы «По организациям» учитывают все организации системы
- **AND** карточка «Доступно организаций» показывает Y = все организации системы

### Requirement: Статистика на домашней странице
The system SHALL render the total organizations card, the nine dashboard
statistics figures and the by-organization indicators on the home page (`/`)
below the hero banner for authenticated users. The statistics SHALL NOT be
rendered on the home page for guests (unauthenticated visitors). The
organizations panel (`/dashboard`) SHALL NOT duplicate the statistics
figures; it SHALL only render the organizations table. After login, the user
SHALL be redirected to the home page (`/`) where statistics are visible
immediately.

#### Scenario: Вошедший пользователь видит статистику на домашней
- **WHEN** вошедший пользователь открывает домашнюю страницу `/`
- **THEN** под hero-баннером отображается карточка «Доступно организаций: Y»
- **AND** под карточкой отображается три секции статистики с девятью показателями
- **AND** под каждым показателем отображается индикатор «По организациям: N»

#### Scenario: Гость не видит статистику
- **WHEN** неаутентифицированный посетитель открывает домашнюю страницу `/`
- **THEN** отображается только hero-баннер
- **AND** карточка и секция статистики не отображаются

#### Scenario: Редирект после логина
- **WHEN** пользователь проходит аутентификацию
- **THEN** происходит редирект на домашнюю страницу `/`
- **AND** на домашней странице отображается карточка и статистика

#### Scenario: Таблица организаций не дублируется
- **WHEN** пользователь открывает `/dashboard`
- **THEN** отображается таблица организаций с поиском и сортировкой
- **AND** карточка и секция статистики (девять показателей) НЕ отображаются на `/dashboard`
