## 1. Доменный слой — сущность Campaign

- [ ] 1.1 Расширить сущность `Campaign`: добавить `subject` (string), `template` (text), `status` (enum: draft/launched/archived, default draft), `linkCalls` (bool), `autoLaunch` (bool), `launchedAt` (nullable datetime), `createdAt`
- [ ] 1.2 Создать сущность `CampaignAttachment` с полями: `campaign` (FK), `filename`, `storageKey`, `mimeType` (nullable), `size` (nullable), `createdAt`
- [ ] 1.3 Настроить каскад: удаление `Campaign` удаляет вложения (onDelete CASCADE) и файлы в storage
- [ ] 1.4 Защита удаления: запрет удаления при наличии `CallResult` с `campaign_id` (проверка в сервисе/контроллере)

## 2. Репозитории

- [ ] 2.1 Добавить в `CampaignRepository` метод `hasLinkedCallResults(Campaign)` для проверки защиты удаления
- [ ] 2.2 Добавить метод `findForLaunch()` (кампании с `launched_at` null для авто-запуска)

## 3. Миграция БД

- [ ] 3.1 Миграция: изменение таблицы `campaign` — добавить `template` (text), `status` (enum draft/launched/archived, default draft), `link_calls`, `auto_launch`, `launched_at`
- [ ] 3.2 Миграция: создание таблицы `campaign_attachment` (id, campaign_id FK, filename, storage_key, mime_type, size, created_at)

## 4. Формы Symfony

- [ ] 4.1 Обновить форму `CampaignType`: поля name, subject, template (textarea), status (enum select: draft/launched/archived), attachments (collection/file), `linkCalls` (checkbox), `autoLaunch` (checkbox)
- [ ] 4.2 Валидация: `name`, `subject` и `template` обязательны

## 5. Контроллеры

- [ ] 5.1 Обновить `CampaignController`: создание/редактирование доступны admin и managers
- [ ] 5.2 Добавить эндпоинты загрузки/удаления вложений (storage)
- [ ] 5.3 Добавить действие «Запустить» (ручной запуск — проставить `launchedAt`)
- [ ] 5.4 Защита удаления: при наличии связанных `CallResult` вернуть ошибку
- [ ] 5.5 Проверка области доступа (`adr/0007`) для standalone-адресатов

## 6. Авто-запуск (хук)

- [ ] 6.1 В изменении `call-result-entity` (при создании `CallResult` типа mailing/refusal_mailing): если `Campaign.autoLaunch` — проставить `launchedAt`
- [ ] 6.2 Логика хука реализуется в `call-result-entity`; здесь — только поле `autoLaunch` и контракт

## 7. Шаблоны Twig

- [ ] 7.1 Карточка кампании: поля шаблона и вложений, флаги `linkCalls`/`autoLaunch`, кнопка «Запустить»
- [ ] 7.2 Индикация запущенности (`launchedAt`) и количества связанных звонков
- [ ] 7.3 Сообщение при попытке удаления с связанными звонками
- [ ] 7.4 Список вложений в форме редактирования с действием удаления

## 8. Загрузка файлов

- [ ] 8.1 Сохранение файлов вложений в файловое хранилище/object storage по `storageKey`
- [ ] 8.2 Удаление файла из storage при удалении вложения/кампании

## 9. Примирение спецификации campaigns

- [ ] 9.1 Обновить `openspec/specs/campaigns/spec.md` при архивации: шаблон и вложения на Campaign, добавлены link_calls/auto_launch/защита/запуск (delta уже в specs/campaigns/spec.md)

## 10. Обновление документации

- [ ] 10.1 Обновить `openspec/design/er.md`: добавить Campaign + CampaignAttachment и Campaign ↔ CallResult
