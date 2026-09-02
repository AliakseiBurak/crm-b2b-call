## 1. Модель и миграция

- [ ] 1.1 Создать миграцию: таблица `notification` (`id` INT AUTO_INCREMENT PK,
  `user_id` INT NOT NULL FK→`user`, `type` VARCHAR(100) NOT NULL,
  `title` VARCHAR(255) NOT NULL, `data` JSON NOT NULL,
  `read_at` DATETIME NULLABLE, `created_at` DATETIME NOT NULL;
  индексы: `idx_notification_user` (`user_id`), `idx_notification_created` (`created_at`))
- [ ] 1.2 Создать сущность `App\Entity\Notification` с映射 Doctrine

## 2. NotificationRepository

- [ ] 2.1 Создать `App\Repository\NotificationRepository`:
  `getUnreadCount(int $userId): int`
- [ ] 2.2 `getRecent(int $userId, int $offset, int $limit): array` — пагинированный
  список уведомлений в обратном хронологическом порядке
- [ ] 2.3 `markAllAsRead(int $userId): void` — установить `readAt = NOW()`
  для всех непрочитанных уведомлений пользователя

## 3. NotificationService

- [ ] 3.1 Создать `App\Service\NotificationService` с DI: `EntityManagerInterface`,
  in-memory канал (`SplQueue` per user ID через `SplObjectStorage` или массив)
- [ ] 3.2 `dispatch(int $userId, string $type, string $title, array $data): void` —
  сохранить в БД, добавить в in-memory канал
- [ ] 3.3 `getChannel(int $userId): \SplQueue` — вернуть канал для чтения SSE-контроллером
- [ ] 3.4 `getUnreadCount(int $userId): int` — делегирует в репозиторий
- [ ] 3.5 `getRecent(int $userId, int $page, int $perPage): array` — делегирует в репозиторий
- [ ] 3.6 `markRead(int $userId): void` — делегирует в репозиторий

## 4. NotificationController

- [ ] 4.1 Создать `App\Controller\NotificationController` с DI: `NotificationService`
- [ ] 4.2 `stream()` — маршрут `GET /notifications/stream`, `EventStreamResponse`,
  читает из in-memory канала, yield `ServerEvent` по типам (`campaign.progress`,
  `campaign.status`, `notification.new`), heartbeat каждые 2с, заголовки
  `X-Accel-Buffering: no`
- [ ] 4.3 `index()` — маршрут `GET /notifications`, страница уведомлений с пагинацией,
  вызывает `markRead()` при открытии

## 5. Интеграция с CampaignSendCommand

- [ ] 5.1 Добавить `NotificationService` в DI `CampaignSendCommand`
- [ ] 5.2 После обработки каждого получателя: dispatch `campaign.progress`
  (всем пользователям: broadcast) с данными `{ campaignId, processed, total, delivered }`
- [ ] 5.3 При смене статуса рассылки: dispatch `campaign.status` (broadcast)
  с данными `{ campaignId, status, label, failureReason }`

## 6. Клиентский JS (base.html.twig)

- [ ] 6.1 Подключить `EventSource('/notifications/stream')` в `base.html.twig`
- [ ] 6.2 Маршрутизация событий по `type`: `campaign.progress` → CustomEvent,
  `campaign.status` → CustomEvent + toast, `notification.new` → бейдж
- [ ] 6.3 Toast-уведомления: auto-dismiss 8с, закрытие по клику, анимация slide-in
- [ ] 6.4 Бейдж непрочитанных в навигации: обновляется при `notification.new`

## 7. Обновление шаблонов кампаний

- [ ] 7.1 `campaign/show.html.twig`: удалить per-campaign EventSource, слушать
  `sse:campaign.progress` и `sse:campaign.status` с фильтрацией по campaignId
- [ ] 7.2 `campaign/index.html.twig`: слушать `sse:campaign.progress` и
  `sse:campaign.status` для обновления колонок «Статистика» и «Статус»

## 8. Страница уведомлений

- [ ] 8.1 Создать `templates/notification/index.html.twig`: список уведомлений
  с пагинацией, непрочитанные выделяются жирным/фоном
- [ ] 8.2 Стили: `assets/scss/pages/notifications.scss`

## 9. Автоочистка

- [ ] 9.1 Создать `App\Command\NotificationCleanupCommand`:
  удаление уведомлений старше `NOTIFICATION_TTL_DAYS` (default 180);
  команда принимает опциональный аргумент `--days=N` для переопределения TTL
- [ ] 9.2 Добавить `NOTIFICATION_TTL_DAYS` в `.env` (default 180) и `.env.example`

## 10. Удаление старого SSE

- [ ] 10.1 Удалить `src/Controller/CampaignStreamController.php`
- [ ] 10.2 Удалить `src/Controller/CampaignNotifyController.php`
- [ ] 10.3 Удалить SSE-код из `templates/base.html.twig` (toast container,
  connectGlobalSSE, slideIn CSS)
- [ ] 10.4 Удалить SSE-клиент из `templates/campaign/show.html.twig`
- [ ] 10.5 Удалить `sse:campaigns` listener из `templates/campaign/index.html.twig`

## 11. Конфигурация и документация

- [ ] 11.1 Добавить `NOTIFICATION_TTL_DAYS` (default 180) в `.env.example`
- [ ] 11.2 Добавить cron-задачу `app:notification:cleanup` (ежедневно)
- [ ] 11.3 Обновить `openspec/specs/notifications/spec.md` при архивировании
