## Why

Рассылки и другие события приложения происходят без уведомления пользователей.
Операторы не видят прогресс отправки в реальном времени, не получают уведомлений
о смене статуса рассылок, не имеют центральной страницы уведомлений.
Каждая реализация SSE (рассылки, будущие группы и т.д.) дублирует инфраструктуру.

Нужна единая система уведомлений с диспатчем, серверными событиями (SSE) и
страницей истории, куда любая часть приложения может отправлять события.

## What Changes

- Единый SSE-endpoint `GET /notifications/stream` — серверные события для всех
  подключённых клиентов, пользовательская область видимости (только свои уведомления).
- Сущность `Notification` (Doctrine): `type`, `title`, `data` (JSON), `readAt`
  (nullable), `createdAt`. Хранит уведомления для отображения на странице.
- Сервис `NotificationService` — API диспатча (`dispatch()`), запросы
  (`getUnreadCount()`, `getRecent()`), пометка прочтения (`markRead()`),
  in-memory канал для SSE-доставки.
- Страница уведомлений `GET /notifications` — список всех уведомлений
  пользователя с пагинацией, непрочитанные выделяются.
- Интеграция с `CampaignSendCommand`: после обработки каждого получателя
  диспатчатся события `campaign.progress` и `campaign.status`.
- Автоочистка: команда `app:notification:cleanup` удаляет старые уведомления
  (TTL 180 дней по умолчанию, параметр команды позволяет задать количество дней).
- Клиентский JS в `base.html.twig`: подключение к `/notifications/stream`,
  маршрутизация событий по типу, обновление DOM, toast-уведомления,
  бейдж непрочитанных.

## Capabilities

### New Capabilities

- `notifications` — единая система уведомлений: диспатч, SSE-доставка, страница
  истории, автоочистка.

### Modified Capabilities

- `campaigns` — прогресс-счётчики и статусы обновляются через систему уведомлений
  (вместо кастомных SSE-контроллеров).

## Impact

- Новая сущность `Notification` и миграция БД.
- Новый сервис `App\Service\NotificationService`.
- Новый контроллер `App\Controller\NotificationController` (SSE + страница).
- Новая команда `App\Command\NotificationCleanupCommand`.
- Новый шаблон `templates/notification/index.html.twig`.
- Удалены `CampaignStreamController` и `CampaignNotifyController`.
- Обновлены `base.html.twig`, `campaign/show.html.twig`, `campaign/index.html.twig`.
- Обновлена `CampaignSendCommand` — диспатч событий после обработки получателя.
- Добавлена переменная окружения `NOTIFICATION_TTL_DAYS`.
