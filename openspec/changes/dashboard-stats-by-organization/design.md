## Context

Дашборд `/dashboard` рендерит шесть показателей статистики
(`stats.calledToday/calledWeek/calledMonth`, `stats.waitingToday/waitingWeek/
waitingMonth`) из `CallRepository::dashboardStats($organizationIds, $now)` —
один DQL-запрос с фильтром по области доступа (`OrganizationRepository::
findAccessibleIds`, ADR-0005–0008: `null` для администратора, иначе личная
группа `user-<id>-group` + назначенные custom-группы). Валидный
change `dashboard-stats-and-nav` уже синхронизировал эти требования в
`openspec/specs/dashboard/spec.md`.

Сущности: `Call(organization, madeAt, scheduledAt, notes, contact)`,
`Contact(organization, name, phone, email)`, `Organization(name, industry,
contacts)`. Домен не меняется; индикаторы — представление существующих
данных. БД — MySQL 8.4.

## Goals / Non-Goals

**Goals:**
- Девять показателей статистики: «Звонков» (3), «Ожидают» (3),
  «Просроченные» (3) — под каждым индикатор по организациям.
- Перенос статистики на домашнюю страницу `/` для мгновенного входа
  в рабочий процесс после логина.
- Клик по индикатору — переход на панель организаций с `?filter=<bucket>`.
- Исключающая логика: called исключает из waiting за тот же период.
  Просроченные — независимая категория.
- Все вычисления — в SQL, без агрегации набора строк в PHP; не более двух
  запросов на страницу (существующий `dashboardStats()` + новый
  `organizationCounts()`).
- Переименование подписей показателей для ясности.

**Non-Goals:**
- Изменение вычислительной модели существующих показателей.
- Изменение домена, области доступа (ADR-0005–0008) и дизайн-системы.
- Реализация пре-фильтра на панели организаций (только URL-структура).
- Детализация по клику (раскрывающийся список организаций).
- Пагинация, отдельные страницы списков.
- Удаление hero-баннера с домашней страницы.

## Decisions

### 1. Девять CASE-категорий в одном запросе
`CallRepository::dashboardStats()` остаётся источником шести
показателей ( count call rows ). Рядом добавляется
`CallRepository::organizationCounts($organizationIds,
DateTimeImmutable $now): array` — один нативный SQL-запрос (DBAL)
с `COUNT(DISTINCT c.organization_id)` и 9 CASE-категориями:

```sql
SELECT
  -- === Звонков (calls made) ===
  COUNT(DISTINCT CASE WHEN c.made_at >= :todayStart
    THEN c.organization_id END) AS calledToday,
  COUNT(DISTINCT CASE WHEN c.made_at >= :weekStart
    THEN c.organization_id END) AS calledWeek,
  COUNT(DISTINCT CASE WHEN c.made_at >= :monthStart
    THEN c.organization_id END) AS calledMonth,

  -- === Ожидают (waiting, scheduled in future) ===
  -- с исключением организаций из corresponding called-категории
  COUNT(DISTINCT CASE
    WHEN c.scheduled_at BETWEEN :todayStart AND :todayEnd
    AND NOT EXISTS (
      SELECT 1 FROM `call` cx
      WHERE cx.organization_id = c.organization_id
      AND cx.made_at >= :todayStart
    )
    THEN c.organization_id
  END) AS waitingToday,

  COUNT(DISTINCT CASE
    WHEN c.scheduled_at > :now AND c.scheduled_at <= :weekEnd
    AND NOT EXISTS (
      SELECT 1 FROM `call` cx
      WHERE cx.organization_id = c.organization_id
      AND cx.made_at >= :weekStart
    )
    THEN c.organization_id
  END) AS waitingWeek,

  COUNT(DISTINCT CASE
    WHEN c.scheduled_at > :now AND c.scheduled_at <= :monthEnd
    AND NOT EXISTS (
      SELECT 1 FROM `call` cx
      WHERE cx.organization_id = c.organization_id
      AND cx.made_at >= :monthStart
    )
    THEN c.organization_id
  END) AS waitingMonth,

  -- === Просроченные (overdue, scheduled in past, not made) ===
  -- без NOT EXISTS — независимая категория
  COUNT(DISTINCT CASE
    WHEN c.scheduled_at BETWEEN :yesterdayStart AND :yesterdayEnd
    AND c.made_at IS NULL
    THEN c.organization_id
  END) AS overdueYesterday,

  COUNT(DISTINCT CASE
    WHEN c.scheduled_at >= :weekStart AND c.scheduled_at < :todayStart
    AND c.made_at IS NULL
    THEN c.organization_id
  END) AS overdueWeek,

  COUNT(DISTINCT CASE
    WHEN c.scheduled_at >= :monthStart AND c.scheduled_at < :todayStart
    AND c.made_at IS NULL
    THEN c.organization_id
  END) AS overdueMonth

FROM `call` c
WHERE (:orgIds IS NULL OR c.organization_id IN (:orgIds))
```

Все категории возвращают `0` (не отсутствуют) при нулевом результате.

### 2. Границы периодов — единый источник истины
Параметры привязаны к одному `$now`:

| Параметр | Значение |
|----------|----------|
| `todayStart` | `$now setTime(0, 0)` |
| `todayEnd` | `$now setTime(23, 59, 59)` |
| `yesterdayStart` | `todayStart - 1 day` |
| `yesterdayEnd` | `todayStart - 1 day + 23:59:59` |
| `weekStart` | `todayStart - 6 days` |
| `monthStart` | `todayStart - 29 days` |
| `weekEnd` | `now + 7 days` |
| `monthEnd` | `now + 30 days` |
| `now` | `$now` (текущий момент) |

### 3. Исключающая логика — per-period exclusion
Организация, имеющая хотя бы один звонок с `made_at` в периоде, НЕ
учитывается в показателе «Ожидают» за тот же период:

| Waiting bucket | Исключение |
|---|---|
| waitingToday | org имеет `made_at >= todayStart` |
| waitingWeek | org имеет `made_at >= weekStart` |
| waitingMonth | org имеет `made_at >= monthStart` |

**Просроченные — независимая категория.** Нет NOT EXISTS. Организация
МОЖЕТ быть одновременно в «Просроченные вчера» и «Звонков сегодня»
(разные периоды и разные аспекты: один звонок не состоялся, другой —
состоялся).

```
               called   waiting   overdue
called          —        NO       no
waiting        NO        —        no
overdue        no        no       —
```

### 4. Карточка общего числа организаций
Y = `count(findAccessibleIds($user))` — общее число организаций области
доступа. Вычисляется один раз, показывается в отдельной карточке
«Доступно организаций: Y» над секцией статистики. Для администратора
Y = общее число организаций системы. Под каждым показателем Y не
повторяется.

### 5. Навигация вместо детализации
Индикатор «По организациям: N» реализуется как тег `<a>`, ведущий на
панель организаций с параметром `?filter=<bucket>`. Пре-фильтр на панели
организаций реализуется в последующем change; пока URL передаётся, но
фильтрация не применяется.

Маппинг bucket → URL:
| Показатель | bucket | URL |
|---|---|---|
| Звонков сегодня | calledToday | `/dashboard?filter=called1` |
| Звонков за 7 дней | calledWeek | `/dashboard?filter=called7` |
| Звонков за 30 дней | calledMonth | `/dashboard?filter=called30` |
| Ожидают сегодня | waitingToday | `/dashboard?filter=waiting1` |
| Ожидают на неделе | waitingWeek | `/dashboard?filter=waiting7` |
| Ожидают в месяце | waitingMonth | `/dashboard?filter=waiting30` |
| Просроченные: вчера | overdueYesterday | `/dashboard?filter=overdue1` |
| Просроченные: 7 дн | overdueWeek | `/dashboard?filter=overdue7` |
| Просроченные: 30 дн | overdueMonth | `/dashboard?filter=overdue30` |

### 7. Рендер — карточка Y и ссылки
Над секцией статистики: тег `<div class="stats__total">` с текстом
«Доступно организаций: Y». Под каждым показателем: тег `<a class="stats__orgs">`
с href на `/dashboard?filter=<bucket>`, текст «По организациям: N».
При N=0 ссылка отображается (нулевое значение — осмысленное).

### 8. Переименование подписей
- «Обзвонено сегодня» → «Звонков сегодня»
- «Обзвонено за 7 дней» → «Звонков за 7 дней»
- «Обзвонено за 30 дней» → «Звонков за 30 дней»
- «Сегодня» (ждут) → «Ожидают сегодня»
- «В течение недели» (ждут) → «Ожидают на неделе»
- «В течение месяца» (ждут) → «Ожидают в месяце»

### 9. Перенос статистики на домашнюю страницу
Статистика (девять показателей + индикаторы по организациям) переносится
с `/dashboard` на `/` (домашняя страница). После логина пользователь
сразу видит статистику под hero-баннером. Таблица организаций остаётся
на `/dashboard`.

Архитектура:
- `HomeController::index()` — загружает статистику (`dashboardStats()` +
  `organizationCounts()`) и передаёт в шаблон; для гостей рендерит только
  hero (без статистики).
- `HomeController::dashboard()` — продолжает рендерить таблицу организаций
  с поиском и сортировкой; статистика НЕ дублируется на `/dashboard`.
- `home/index.html.twig` — hero + секция статистики (для `app.user`).
- `security.yaml` — `default_target_path: app_home` (уже настроено;
  после логина редирект на `/`).

### 10. Индекс БД
Составной индекс `(organization_id, made_at)` на таблицу `call` для
оптимизации коррелированных `NOT EXISTS` подзапросов и существующего
`dashboardStats()`. Добавляется в основную миграцию (не в продакшне).

### 11. Консистентность области доступа
Все новые запросы принимают один и тот же набор `organizationIds` от
контроллера, что и `dashboardStats()`/таблица организаций — индикаторы лежат
в той же области доступа по построению (ADR-0007). Отдельные проверки прав
не вводятся.

## Alternatives considered

- **ROW_NUMBER() для топ-5 организаций** — отклонена: оконные функции
  не используются нигде в кодовой базе; GROUP BY + NOT EXISTS проще.
- **Агрегация в PHP** — отклонена: нарушает требование «без PHP-агрегации».
- **Один запрос для dashboardStats и organizationCounts** — отклонена:
  комбинация SUM(CASE) и COUNT(DISTINCT) с NOT EXISTS в одном запросе
  делает его нечитаемым.
- **Раскрывающийся список организаций** — отклонена: пользователь
  запросил навигацию на панель организаций.
- **NOT EXISTS для просроченных** — отклонена: просроченные — независимая
  категория, организация может быть и в просроченных, и в звонках.
