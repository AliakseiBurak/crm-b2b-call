## 1. Доменный слой — сущность Campaign

- [x] 1.1 Расширить сущность `Campaign`: добавить `subject` (string), `body` (text), `status` (enum: draft/ready/launched/failed/archived, default draft), `launchedAt` (nullable datetime), `createdAt`
- [x] 1.2 Создать сущность `CampaignAttachment` с полями: `campaign` (FK), `filename`, `storageKey`, `mimeType` (nullable), `size` (nullable), `createdAt`
- [x] 1.3 Настроить каскад: удаление `Campaign` удаляет вложения (onDelete CASCADE) и файлы в storage
- [x] 1.4 Клонирование кампаний (`cloneFrom`): копирование полей, вложений (метаданные, файлы общие) и опционально получателей

## 2. Репозитории

- [x] 2.1 Базовый репозиторий `CampaignRepository`

## 3. Миграция БД

- [x] 3.1 Миграция: создание таблицы `campaign` (name, subject, preview_text, body, status ENUM, launched_at, created_at)
- [x] 3.2 Миграция: создание таблицы `campaign_attachment` (id, campaign_id FK, filename, storage_key, mime_type, size, created_at)
- [x] 3.3 Миграция: создание таблицы `campaign_recipient` (id, campaign_id FK, organization_id FK, contact_id FK nullable, created_at, UNIQUE on campaign_id+organization_id)

## 4. Формы

- [x] 4.1 Форма кампании: name, subject, preview_text, body (textarea), status (enum select), recipients (org + contact), attachments (file upload + delete)
- [x] 4.2 Валидация: `name`, `subject` и `body` обязательны

## 5. Контроллеры

- [x] 5.1 Обновить `CampaignController`: создание/редактирование доступны admin и managers
- [x] 5.2 Добавить эндпоинты загрузки/удаления вложений (storage)
- [x] 5.3 Добавить действие «Запустить» (ручной запуск — проставить `launchedAt`, статус → launched)
- [x] 5.4 Удаление кампании (каскадное удаление вложений)
- [x] 5.5 Добавить ручных адресатов (campaign_recipient): добавление, удаление, замена с подтверждением
- [x] 5.6 Клонирование архивированных кампаний

## 6. Шаблоны Twig

- [x] 6.1 Форма кампании: тема, превью, текст, статус, вложения, адресаты (с JS-фильтрацией контактов по организации)
- [x] 6.2 Карточка кампании: тема, превью, текст, статус, вложения, адресаты, кнопки запуска/редактирования/удаления/клонирования
- [x] 6.3 Подтверждение удаления кампании
- [x] 6.4 Подтверждение замены адресата

## 7. Загрузка файлов

- [x] 7.1 Сохранение файлов вложений в файловое хранилище/object storage по `storageKey`
- [x] 7.2 Удаление файла из storage при удалении вложения/кампании

## 8. Спецификации и дизайн

- [x] 8.1 Обновить `openspec/specs/campaigns/spec.md` и delta spec
- [x] 8.2 Обновить `openspec/design/er.md` (Campaign, CampaignAttachment, CampaignRecipient)
- [x] 8.3 Обновить `openspec/changes/campaign-entity/design.md`
