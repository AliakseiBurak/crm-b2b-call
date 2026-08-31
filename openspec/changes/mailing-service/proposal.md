## Why

Рассылки (`campaigns`) создаются и запускаются, но реальной отправки писем нет. Операторам нужен сервис, который по-настоящему рассылает письма по SMTP (фоновая команда опрашивает БД), показывает живой прогресс через SSE и реагирует на изменения статуса рассылки. Без этого запуск рассылки — только установка флага/статуса.

## What Changes

- Добавлен `MailingService`, отправляющий письма рассылки по SMTP (Symfony Mailer) по одному; вызывается фоновой командой `app:campaign:send`, а не событиями.
- Ожидающие письма отправляются фоновой командой `app:campaign:send`, которая опрашивает БД на наличие `CampaignRecipient` со статусом `pending`; письмо, добавленное в рассылку уже в статусе `launched` и обрабатывается командой.
- Отправка управляется через фоновую команду (`app:campaign:send`), опрашивающую БД; `MailingService` вызывается командой, а не подписывается на события.
- На каждое отправленное письмо показывается синхронное уведомление на сайте через нативный SSE (`EventStreamResponse`) и счётчик «обработано X из Y» на странице рассылки.
- Per-letter статусы на `CampaignRecipient` (`pending` → `sending` → `delivered`/`bounced`/`failed`, + `opened` через tracking-pixel) по ADR-0010; `CampaignRecipient` выступает как outbox.
- При отсутствии email-адреса у организации/контакта `CampaignRecipient` помечается статусом `failed` с описанием ошибки в поле `errorMessage`. На странице Адресаты отображается красное сообщение об ошибке под адресатом (только при его наличии, без отдельной колонки).
- Сервис `MailingService` строится по принципам SOLID со всеми зависимостями через dependency injection и установкой необходимых пакетов и конфигурации приложения.

## Capabilities

### New Capabilities

(none — изменение существующей возможности `campaigns`)

### Modified Capabilities

- `campaigns`: поведение отправки писем через фоновую команду, realtime через SSE и счётчик отправленных писем.

## Impact

- Поле `failureReason` (text, nullable) на `Campaign`; заполняется при переходе в `failed`, очищается при повторном запуске.
- Сущность `CampaignRecipient`: расширена полями per-letter статуса (`pending`/`sending`/`delivered`/`bounced`/`failed`/`opened`, `sentAt`, `errorMessage`, `trackingToken`) — это outbox по ADR-0010. Поле `errorMessage` содержит описание ошибки (например, «Отсутствует email-адрес организации»); отображается красным сообщением под адресатом на странице Адресаты. Статус `opened` фиксируется через tracking-pixel.
- Новый сервис `App\Service\MailingService` (SOLID, DI): `MailerInterface`, репозитории `Campaign`/`CampaignRecipient`.
- Фоновая команда `app:campaign:send` опрашивает БД; запускается под supervisor/cron.
- Realtime через нативный SSE (`EventStreamResponse`, Symfony 7.4) — endpoint `GET /campaigns/{id}/stream`; без выделенного сервера.
- Зависимости: `symfony/mailer` (есть); SSE встроен в Symfony 7.4; при необходимости — установка и конфигурация необходимых пакетов.
- Шаблоны: счётчик и индикация живого прогресса на странице рассылки, UI уведомлений через SSE.
