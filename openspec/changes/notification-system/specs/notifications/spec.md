## ADDED Requirements

### Requirement: Диспатч уведомлений
The system SHALL provide a `NotificationService` that any part of the application can call to dispatch notifications to users. The service SHALL accept: `userId` (int), `type` (string), `title` (string), `data` (array). The service SHALL persist the notification to the database and push it to the in-memory channel for real-time SSE delivery.

#### Scenario: Диспатч события рассылки
- **WHEN** фоновая команда обрабатывает получателя рассылки
- **THEN** вызывается `NotificationService->dispatch()` с типом `campaign.progress` и данными `{ campaignId, processed, total, delivered }`
- **AND** уведомление сохраняется в БД и доступно на странице уведомлений

#### Scenario: Диспатч смены статуса
- **WHEN** рассылка переходит в статус `failed`
- **THEN** вызывается `NotificationService->dispatch()` с типом `campaign.status` и данными `{ campaignId, status, label, failureReason }`
- **AND** уведомление сохраняется в БД

### Requirement: SSE-доставка
The system SHALL provide a single SSE endpoint `GET /notifications/stream` that streams real-time events to authenticated users. Events SHALL be scoped to the authenticated user — only notifications dispatched to that user SHALL be delivered. The endpoint SHALL use Symfony's `EventStreamResponse`. The SSE controller SHALL read from the in-memory channel (populated by `NotificationService->dispatch()`) and yield events. Heartbeat comments SHALL be sent every 2 seconds when idle.

#### Scenario: Подключение к SSE
- **WHEN** пользователь открывает любую страницу сайта
- **AND** браузер подключается к `GET /notifications/stream`
- **THEN** по SSE приходят уведомления, предназначенные этому пользователю

#### Scenario: Прогресс-счётчик в реальном времени
- **WHEN** рассылка обрабатывается фоновой командой
- **AND** пользователь находится на странице списка рассылок
- **THEN** колонка «Статистика» обновляется автоматически через SSE без перезагрузки страницы

#### Scenario: Смена статуса в реальном времени
- **WHEN** рассылка переходит в статус `failed`
- **AND** пользователь находится на странице списка рассылок
- **THEN** статус рассылки обновляется автоматически через SSE без перезагрузки страницы

#### Scenario: Heartbeat
- **WHEN** нет новых уведомлений для отправки
- **THEN** SSE-контроллер отправляет heartbeat-комментарий каждые 2 секунды

### Requirement: Прогресс-счётчики (X из Y)
The system SHALL display "обработано X из Y" progress counters on the campaign detail page and "x из y" statistics in the campaigns list table. These counters SHALL update in real-time via the global SSE stream. The counters SHALL be updated for every recipient processed by the background worker, broadcast to all connected users.

#### Scenario: Счётчик на странице рассылки
- **WHEN** пользователь открывает карточку рассылки в процессе отправки
- **THEN** на странице показан счётчик «обработано X из Y»
- **AND** счётчик обновляется в реальном времени по мере обработки получателей

#### Scenario: Статистика в списке рассылок
- **WHEN** менеджер открывает список рассылок
- **THEN** в таблице есть колонка «Статистика» со значением «x из y»
- **AND** значение обновляется в реальном времени по мере обработки получателей

### Requirement: Toast-уведомления
The system SHALL display toast notifications (pop-up) on any page of the site when campaign status changes occur. Toasts SHALL appear for: campaign launched (info), campaign failed (error). Toasts SHALL auto-dismiss after 8 seconds and be dismissible by click. Toasts SHALL be driven by the global SSE stream (`GET /notifications/stream`).

#### Scenario: Уведомление о запуске рассылки
- **WHEN** рассылка переходит в статус `launched`
- **AND** пользователь находится на любой странице сайта
- **THEN** отображается toast-уведомление «Рассылка запущена: <название>»

#### Scenario: Уведомление об ошибке рассылки
- **WHEN** рассылка переходит в статус `failed`
- **AND** пользователь находится на любой странице сайта
- **THEN** отображается toast-уведомление «Ошибка рассылки: <название>: <failureReason>»

### Requirement: Страница уведомлений
The system SHALL provide a page `GET /notifications` that displays all notifications for the authenticated user in reverse chronological order. Unread notifications SHALL be visually distinct from read ones. The page SHALL support pagination. Clicking a notification SHALL mark it as read. A badge with the unread count SHALL be displayed in the site navigation.

#### Scenario: Просмотр списка уведомлений
- **WHEN** пользователь открывает страницу `/notifications`
- **THEN** отображается список всех уведомлений в обратном хронологическом порядке

#### Scenario: Непрочитанные уведомления
- **WHEN** уведомление ещё не просмотрено пользователем
- **THEN** оно отображается с визуальным отличием (жирный шрифт, другой фон)

#### Scenario: Пометка прочтения
- **WHEN** пользователь открывает страницу уведомлений
- **THEN** все непрочитанные уведомления помечаются как прочитанные (readAt = now)

#### Scenario: Бейдж непрочитанных
- **WHEN** пользователь находится на любой странице сайта
- **THEN** в навигации отображается бейдж с количеством непрочитанных уведомлений
- **AND** бейдж обновляется в реальном времени при новом уведомлении

### Requirement: Автоочистка уведомлений
The system SHALL provide a console command `app:notification:cleanup` that deletes old notifications. The command SHALL accept an optional `--days` argument to override the default TTL. The default TTL SHALL be `NOTIFICATION_TTL_DAYS` (default 180). The command SHALL be invoked periodically via cron.

#### Scenario: Очистка старых уведомлений
- **WHEN** команда `app:notification:cleanup` запускается
- **THEN** удаляются все уведомления старше `NOTIFICATION_TTL_DAYS` дней

#### Scenario: Очистка с указанием количества дней
- **WHEN** команда запускается с аргументом `--days=30`
- **THEN** удаляются все уведомления старше 30 дней
