## Context

Рассылки создаются и запускаются в изменении `campaign-entity`: у `Campaign` есть
`subject`, `previewText`, `body` (модель токенов), `status`
(`draft`/`ready`/`launched`/`failed`/`archived`), `launchedAt`, `failedAt`,
вложения и адресаты (`CampaignRecipient`). Реальной отправки нет. Это изменение
реализует отправку согласно **ADR-0010** и решениям, согласованным с пользователем:

- Per-letter статусы по ADR-0010 хранятся **на `CampaignRecipient`** (это и есть outbox).
- Отправка — **фоновой командой (worker)**, опрашивающей БД; никаких Symfony
  Events-триггеров запуска (триггер — статус `ready`→`launched`).
- Шаблон/тема/превью **хранятся на самой рассылке** (`Campaign`). Пользователь
  редактирует их напрямую на рассылке.

## Goals / Non-Goals

**Goals:**
- `MailingService` отправляет письма по SMTP (Symfony Mailer) по одному.
- Фоновая команда `app:campaign:send` опрашивает БД и обрабатывает `pending` получателей кампаний со статусом `launched`.
- Периодический запуск — Symfony Scheduler (`SendCampaignBatch` каждую минуту) + `messenger:consume scheduler_default`.
- Поле `failureReason` на `Campaign` с понятным описанием сбоя; email администратору.
- Per-letter статусы на `CampaignRecipient`: `pending → sending → {delivered|bounced|failed}`, `+ opened` (tracking-pixel).

**Non-Goals:**
- (none)

## Decisions

### 1. `CampaignRecipient` как outbox (per-letter статус)
**Решение**: расширить `CampaignRecipient` полями: `status`
(`pending|sending|delivered|bounced|failed|opened`), `sentAt`, `errorMessage`, `trackingToken`.
Отдельная таблица записей отправки не создаётся.
**Почему**: пользователь решил хранить per-letter статусы на получателе рассылки.

> **Статистика в списке рассылок** (новое требование): колонка «Статистика»
> («x из y») вычисляется из `CampaignRecipient` — x = число получателей со
> статусом `delivered` или `opened`, y = общее число получателей. Это **производное**
> значение, отдельная колонка в БД не заводится (при росте нагрузки возможен
> денормализованный счётчик, но пока не нужен).

> **Tracking-pixel**: в HTML письма встраивается `<img>` на
> `GET /t/{trackingToken}.png`. Endpoint отдаёт прозрачную картинку 1×1 и
> помечает получателя `opened` (только из статуса `delivered`). Токен уникален
> для каждого `CampaignRecipient`.

### 2. Фоновая команда вместо событий
**Решение**: убрать `CampaignLaunchedEvent`. Запуск —
это перевод статуса `ready`→`launched` (вручную). Команда
`app:campaign:send` опрашивает БД: `Campaign.status = launched` +
`CampaignRecipient.status IN ('pending', 'failed' WHERE retry_at <= NOW())`.
Лимит — `MAILING_BATCH_SIZE` (из .env, по умолчанию 10) на все кампании глобально.
Команда принимает опциональный параметр `--limit`, который переопределяет `MAILING_BATCH_SIZE` на один запуск (например, `app:campaign:send --limit=20`).
Команда может запускаться периодически через **Symfony Scheduler** (`SendCampaignBatch` каждую минуту; consumer `messenger:consume scheduler_default` управляется окружением вне Docker Compose). В development consumer запускается вручную по необходимости. Для предотвращения
параллельного запуска нескольких экземпляров используется lock file.
**Почему**: получатели создаются до запуска (и как результат звонка), поэтому event-триггер избыточен. Scheduler заменяет внешний cron: тот же poll-цикл, но внутри приложения.

### 3. Индивидуальная отправка с фиксацией статуса
**Решение**: каждое письмо отправляется отдельно; статус **каждого**
`CampaignRecipient` пишется сразу после попытки. Ошибка одного получателя
не влияет на остальных. На организацию — **одно** письмо: указанный контакт
с email → только TO; иначе первый email контактов организации TO, остальные CC.
Вложения кампании прикрепляются к письму. `MailingService` получает
`CampaignRepository` и `CampaignRecipientRepository` через DI.
**Почему**: простая логика, максимальная изоляция ошибок, нет зависимости от размера
батча или rate limits SMTP-сервера.

### 4. Retry для transient ошибок
**Решение**: при SMTP timeout или 4xx получатель помечается `failed` с
`retry_count` и `retry_at` (exponential backoff + jitter). Максимум 3 попытки;
после этого — permanent failure. Поля: `retry_count` (int, default 0),
`retry_at` (timestamp, nullable). Worker при следующем цикле подбирает
получателей с `status = 'failed'` и `retry_at <= NOW()` и `retry_count < 3`.
**Почему**: transient ошибки (timeout, temporary rejection) исправимы; permanent
ошибки (5xx, нет адреса) — нет. Exponential backoff + jitter предотвращают
thundering herd.

### 5. Шаблон хранится на самой рассылке
**Решение**: тема письма (`subject`), прехедер (`previewText`) и текст (`body`) —
часть сущности `Campaign` (campaign-entity). Пользователь редактирует их напрямую
на рассылке. `MailingService` при отправке читает **поля самой рассылки** и
подставляет токены `{{greeting}}` / `{{contact_name}}` / `{{organization_name}}`
в тему, превью и тело. Превью вставляется в HTML как скрытый preheader
(не заголовок `X-Preview`).
**Почему**: рассылка — самостоятельная сущность; шаблон не привязан к компании
и не копируется извне. Отредактированный шаблон сразу готов к отправке.

### 6. Обработка ошибок, `failed` и `failureReason`
**Решение**: при неустранимой ошибке → `Campaign.status = failed`, `failedAt`
заполняется, `failureReason` (текст на русском: что сломалось и как исправить), email
администратору. Уведомления отправляются **всем администраторам** системы —
`MailingService` получает их из БД через `UserRepository::findAdmins()` (запрос
пользователей с `role = 'admin'`). Повторный запуск: кнопка «Сбросить» → `ready`,
`failureReason` очищается; затем «Запустить» → `launched`, обработка `pending`
продолжается. Эскалация кампании в `failed` **не** выполняется, пока есть
получатели `pending`/`sending` или retriable `failed` (`retry_count < 3`,
`retry_at` задан).
**Почему**: рассылка не должна «висеть» в `processing`; пользователь должен понять и
исправить. Transient retry не должен обрываться эскалацией кампании.

### 7. Получатель без адреса
**Решение**: нет email у организации/контакта → `CampaignRecipient.status = failed`,
`errorMessage` содержит описание ошибки (например, «Отсутствует email-адрес организации»);
остальные продолжаются; если недоставляемы все → эскалация в `failed`.
На странице Адресаты отображается красное сообщение об ошибке под адресатом (только при
наличии `errorMessage`, без отдельной колонки).
**Почему**: звонок может быть без контакта (`calls/spec.md:16` — контакт опционален);
пользователь должен видеть причину, по которой организация не получит письмо.

## Worker / scheduler

- Symfony Scheduler каждую минуту диспатчит `SendCampaignBatch` (`App\Schedule`); handler вызывает
  `CampaignSendProcessor` (тот же путь, что `php bin/console app:campaign:send`).
  Каждый запуск обрабатывает до `MAILING_BATCH_SIZE` получателей
  (global, из .env, по умолчанию 10) со статусом `pending` или `failed` с `retry_at <= NOW()` и
  `retry_count < 3`. Параметр `--limit` позволяет переопределить размер батча на один ручной запуск.
  Lock file предотвращает параллельные запуски (команда и scheduler).
  Consumer: `php bin/console messenger:consume scheduler_default`; отдельный compose-сервис не требуется. В development consumer запускается вручную по необходимости, в production процессом управляет инфраструктура окружения.

## Migration Plan

1. Миграция: добавить `failureReason` (text nullable) в `campaign`; расширить `campaign_recipient`
   полями `status`, `sent_at`, `error_message` (text nullable), `tracking_token`,
   `retry_count` (int, default 0), `retry_at` (timestamp nullable).
2. Пакеты: `symfony/mailer` (есть).
3. Команда `app:campaign:send` + Symfony Scheduler; `MAILING_BATCH_SIZE` в .env.
4. Rollback: откат миграции; рассылка возвращается к поведению «только статус».
