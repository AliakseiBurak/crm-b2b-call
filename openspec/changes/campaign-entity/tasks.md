## 1. Доменный слой — сущность Campaign

- [ ] 1.1 Расширить сущность `Campaign`: добавить `subject` (string), `body` (text), `status` (enum: draft/ready/launched/failed/archived, default draft), `launchedAt` (nullable datetime), `createdAt`
- [ ] 1.2 Создать сущность `CampaignAttachment` с полями: `campaign` (FK), `filename`, `storageKey`, `mimeType` (nullable), `size` (nullable), `createdAt`
- [ ] 1.3 Настроить каскад: удаление `Campaign` удаляет вложения (onDelete CASCADE) и файлы в storage
- [ ] 1.4 Клонирование кампаний (`cloneFrom`): копирование полей, вложений (метаданные, файлы общие) и опционально получателей

## 2. Репозитории

- [ ] 2.1 Базовый репозиторий `CampaignRepository`

## 3. Миграция БД

- [ ] 3.1 Миграция: создание таблицы `campaign` (name, subject, preview_text, body, status ENUM, launched_at, created_at)
- [ ] 3.2 Миграция: создание таблицы `campaign_attachment` (id, campaign_id FK, filename, storage_key, mime_type, size, created_at)
- [ ] 3.3 Миграция: создание таблицы `campaign_recipient` (id, campaign_id FK, organization_id FK, contact_id FK nullable, created_at, UNIQUE on campaign_id+organization_id)

## 4. Формы

- [ ] 4.1 Форма кампании: name, subject, preview_text, body (textarea), status (enum select), recipients (org + contact), attachments (file upload + delete)
- [ ] 4.2 Валидация: `name`, `subject` и `body` обязательны

## 5. Контроллеры

- [ ] 5.1 Обновить `CampaignController`: создание/редактирование доступны admin и managers
- [ ] 5.2 Добавить эндпоинты загрузки/удаления вложений (storage)
- [ ] 5.3 Добавить действие «Запустить» (ручной запуск — проставить `launchedAt`, статус → launched)
- [ ] 5.4 Удаление кампании (каскадное удаление вложений)
- [ ] 5.5 Добавить ручных адресатов (campaign_recipient): добавление, удаление, замена с подтверждением
- [ ] 5.6 Клонирование архивированных кампаний

## 6. Шаблоны Twig

- [ ] 6.1 Форма кампании: тема, превью, текст, статус, вложения, адресаты (с JS-фильтрацией контактов по организации)
- [ ] 6.2 Карточка кампании: тема, превью, текст, статус, вложения, адресаты, кнопки запуска/редактирования/удаления/клонирования
- [ ] 6.3 Подтверждение удаления кампании
- [ ] 6.4 Подтверждение замены адресата

## 7. Загрузка файлов

- [ ] 7.1 Сохранение файлов вложений в файловое хранилище/object storage по `storageKey`
- [ ] 7.2 Удаление файла из storage при удалении вложения/кампании

## 8. Спецификации и дизайн

- [ ] 8.1 Обновить `openspec/specs/campaigns/spec.md` и delta spec
- [ ] 8.2 Обновить `openspec/design/er.md` (Campaign, CampaignAttachment, CampaignRecipient)
- [ ] 8.3 Обновить `openspec/changes/campaign-entity/design.md`
