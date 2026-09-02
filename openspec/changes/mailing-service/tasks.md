## 1. Модель и статус рассылки

- [ ] 1.0 Создать миграцию: добавить `failure_reason` (text nullable) в `campaign`; расширить `campaign_recipient` полями `status`, `sent_at`, `error_message` (text nullable), `tracking_token`, `retry_count` (int, default 0), `retry_at` (timestamp nullable)
- [ ] 1.1 Добавить поле `failureReason` (text, nullable) в `Campaign`; заполняется при переходе в `failed`, очищается при повторном запуске
- [ ] 1.2 Расширить `CampaignRecipient` полями per-letter статуса: `status` (`pending|sending|delivered|bounced|failed|opened`), `sentAt`, `errorMessage`, `trackingToken`, `retryCount` (int, default 0), `retryAt` (timestamp, nullable) — это и есть outbox

## 2. Фоновая команда (worker)

- [ ] 2.1 Создать консольную команду `app:campaign:send`, которая опрашивает БД: `Campaign.status = launched` и `CampaignRecipient.status IN ('pending', 'failed' WHERE retry_at <= NOW() AND retry_count < 3)`; лимит — `MAILING_BATCH_SIZE` (из .env, default 50)
- [ ] 2.2 Использовать lock file для предотвращения параллельного запуска нескольких экземпляров команды
- [ ] 2.3 Обрабатывать получателей по одному; обновлять `CampaignRecipient.status` индивидуально после каждой попытки
- [ ] 2.4 При SMTP timeout или 4xx — `failed` с `retryCount++` и `retryAt` (exponential backoff + jitter); при `retryCount >= 3` — permanent failure
- [ ] 2.5 При неустранимой ошибке (5xx, нет адреса) — permanent `failed` без retry; при отсутствии доставляемых адресов у всех — эскалация в `failed` с `failureReason`, email администратору
- [ ] 2.6 Настроить запуск команды через cron (периодический запуск, lock file предотвращает параллельные экземпляры)

## 3. MailingService

- [ ] 3.1 Создать `MailingService` (SOLID, DI): `MailerInterface`, репозитории `Campaign`/`CampaignRecipient`
- [ ] 3.2 Читать тему/превью/текст из **полей рассылки**, подставлять токены `{{greeting}}`/`{{contact_name}}`/`{{organization_name}}`; разворачивать организацию в контакты с email (или указанный контакт)
- [ ] 3.3 Отправка через SMTP (symfony/mailer); обновлять `CampaignRecipient.status` (pending→sending→delivered/bounced/failed); при timeout/4xx — `failed` с `retryCount++` и `retryAt`; при отсутствии адреса — permanent `failed` с `errorMessage`
- [ ] 3.4 Endpoint tracking-pixel: при запросе `GET /t/{trackingToken}.png` помечать получателя `opened`

## 5. Toast-уведомления

> Реализуются в OpenSpec change `notification-system`.

## 6. Счётчик и UI

- [ ] 6.1 Добавить на страницу рассылки счётчик «обработано X из Y» на основе `CampaignRecipient.status`
- [ ] 6.2 Отображать статусы `launched`/`failed` на странице рассылки
- [ ] 6.3 Добавить в таблицу списка рассылок колонку «Статистика» («x из y»: доставлено `delivered` / всего получателей), значение производное от `CampaignRecipient`
- [ ] 6.4 На странице Адресаты отображать красное сообщение об ошибке под адресатом при наличии `errorMessage` (без отдельной колонки)

## 7. Обработка ошибок и конфигурация

- [ ] 7.1 При неустранимой ошибке ставить `failed` + понятный `failureReason`, email администратору; повторный запуск очищает `failureReason` и продолжает `pending`
- [ ] 7.2 Убедиться, что `symfony/mailer` настроен (SMTP)
- [ ] 7.3 Добавить `MAILING_BATCH_SIZE` в .env (default 50)

## 8. Документация

- [ ] 8.1 Обновить `openspec/specs/campaigns/spec.md` при архивировании
- [ ] 8.2 Обновить `openspec/design/er.md`: `CampaignRecipient` получает поля статуса отправки (`status`, `sentAt`, `errorMessage`, `trackingToken`, `retryCount`, `retryAt`); `Campaign` — `failureReason`
