## Context

Реализована отправка рассылок через фоновую команду (`mailing-service`). Прогресс
отправки, смена статуса и toast-уведомления требуют единой инфраструктуры
уведомлений с серверными событиями (SSE), диспатчем и страницей истории.

Текущая реализация SSE (два кастомных контроллера: per-campaign и global) заменяется
generic-системой, куда любая часть приложения может диспатчить события.

## Goals / Non-Goals

**Goals:**
- Единый SSE-endpoint `GET /notifications/stream` для всех уведомлений.
- Диспатч через `NotificationService` — любой сервис может отправлять уведомления.
- Сущность `Notification` для хранения и отображения на странице истории.
- Прогресс-счётчики (X из Y) обновляются в реальном времени на всех страницах.
- Toast-уведомления при смене статуса рассылки.
- Страница уведомлений с пагинацией и бейджем непрочитанных.
- Автоочистка старых уведомлений (cron, TTL 180 дней).

**Non-Goals:**
- Группировка уведомлений (в будущем).
- Push-уведомления (mobile, email) — только SSE + UI.

## Decisions

### 1. Диспатч вместо опроса
**Решение**: NotificationService принимает вызовы `dispatch(userId, type, title, data)`
от любой части приложения (контроллеры, команды, сервисы). Контроллер SSE не опрашивает
БД — он читает из in-memory канала, заполняемого при диспатче.
**Почему**: декаплинг — отправитель не знает о деталях SSE-доставки; легче добавлять
новые типы уведомлений; нет polling-overhead.

### 2. Хранение уведомлений в БД
**Решение**: сущность `Notification` с полями: `id`, `userId`, `type`, `title`, `data` (JSON),
`readAt` (nullable), `createdAt`. TTL 180 дней; автоочистка через cron-команду.
**Почему**: страница уведомлений требует хранения; `readAt` позволяет отслеживать
непрочитанные; JSON-поле `data` гибко для разных типов событий.

### 3. In-memory канал для SSE
**Решение**: при диспатче `NotificationService` сохраняет уведомление в БД и
добавляет его в `SplQueue` (или аналогичную структуру) per user ID. SSE-контроллер
читает из этого канала. Канал не персистентен — при потере соединения пользователь
увидит уведомления при переподключении (из БД, через страницу уведомлений).
**Почему**: простая реализация без Redis/AMQP; совместимость с текущей
инфраструктурой (cron + long-polling).

### 4. Broadcast для прогресс-событий
**Решение**: события `campaign.progress` и `campaign.status` диспатчатся всем
пользователям (broadcast), а не конкретному пользователю. Каждый подключённый
клиент получает эти события.
**Почему**: список рассылок виден всем пользователям; любой оператор должен
видеть прогресс отправки.

### 5. SSE-клиент в base.html.twig
**Решение**: в `base.html.twig` один EventSource, подключающийся к
`/notifications/stream`. Клиент маршрутизирует события по `type`:
- `campaign.progress` → обновление счётчиков X из Y на всех страницах
- `campaign.status` → обновление статусов + toast-уведомления
Для передачи данных между компонентами используются `CustomEvent`.
**Почему**: единая точка подключения; базовый шаблон доступен на всех страницах.

### 6. Страница уведомлений
**Решение**: маршрут `GET /notifications`, контроллер `NotificationController`.
Список в обратном хронологическом порядке, непрочитанные выделяются.
При открытии страницы все непрочитанные помечаются прочитанными.
Бейдж непрочитанных в навигации обновляется через SSE.
**Почему**: пользователь должен видеть историю уведомлений и количество
непрочитанных.

## SSE (детали)

### Endpoint `GET /notifications/stream`
- `EventStreamResponse` (Symfony 7.4). Генератор `yield new ServerEvent(...)`.
- Контроллер читает из in-memory канала `NotificationService`.
- Типы событий: `campaign.progress`, `campaign.status`, `notification.new` (для бейджа).
- Заголовки: `Content-Type: text/event-stream`, `Cache-Control: no-cache`,
  `X-Accel-Buffering: no`.
- Heartbeat-комментарий каждые 2с при простое.

### Клиентский JS (base.html.twig)
- Единый `EventSource('/notifications/stream')`.
- Маршрутизация по `type`:
  - `campaign.progress` → `CustomEvent('sse:campaign.progress')` → обновление счётчиков
  - `campaign.status` → `CustomEvent('sse:campaign.status')` → обновление статусов + toast
  - `notification.new` → обновление бейджа непрочитанных
- Toast: auto-dismiss 8с, закрытие по клику, анимация slide-in.

### Интеграция с CampaignSendCommand
- После обработки каждого получателя: `NotificationService->dispatch()`
  с типом `campaign.progress`.
- При смене статуса рассылки: `NotificationService->dispatch()`
  с типом `campaign.status`.

## Migration Plan

1. Миграция: таблица `notification` (`id`, `user_id`, `type`, `title`, `data`,
   `read_at`, `created_at`).
2. Сервис `NotificationService`: диспатч, in-memory канал, запросы.
3. Контроллер `NotificationController`: SSE + страница.
4. Интеграция с `CampaignSendCommand`: диспатч событий.
5. Клиентский JS: `base.html.twig` подключается к `/notifications/stream`.
6. Удаление `CampaignStreamController` и `CampaignNotifyController`.
7. Команда `app:notification:cleanup` + cron.
