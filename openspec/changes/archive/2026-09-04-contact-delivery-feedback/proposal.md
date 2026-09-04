## Why

Контакт содержит неиспользуемые поля (`contactType`, `contactPerson`), усложняющие сущность без пользы. Ошибки доставки рассылок (`failed` / `bounced`) плохо видны менеджеру: нет сводки на контакте и организации, нет сброса статуса, в таблице адресатов нет навигации к org/contact. Организацию без e-mail можно добавить в рассылку, хотя отправка заведомо провалится.

## What Changes

- **BREAKING**: Удаление полей `contactType` (enum `ContactType`) и `contactPerson` из сущности `Contact`. Поле `position` (должность) сохраняется. Миграция удаляет колонки `contact_type` и `contact_person`. ADR-0002 и Purpose в `contacts` обновляются при архивации.
- Таблица ошибок рассылок на форме редактирования **контакта**: получатели со статусами `failed`/`bounced`, где `contact` = данный контакт, рассылка не `archived`.
- Таблица ошибок рассылок на форме редактирования **организации**: все получатели этой организации со статусами `failed`/`bounced` (org-wide и с контактом), рассылка не `archived`; колонка контакта со ссылкой или "—".
- Сброс получателя: один маршрут `POST /campaigns/{id}/recipients/{recipientId}/reset` для `failed` и `bounced`. Для `bounced` перед POST — страница подтверждения с предупреждением об отказе почтового сервера. Сброс очищает `errorMessage`, `retryCount`, `retryAt` и ставит статус `pending`.
- На дашборде: отметка на карточке контакта, если у контакта есть хотя бы один `bounced` в неархивной рассылке.
- Кнопка «Организация» на форме редактирования контакта → `GET /organizations/{id}/edit`.
- Кликабельные ссылки в таблице адресатов: Организация → `/organizations/{id}/edit`, Контакт → `/contacts/{id}/edit`.
- Список связанных контактов в форме редактирования организации.
- Доменное правило рассылки: организация без доставляемого e-mail (ни у одного контакта) SHALL NOT стать адресатом — на всех путях создания получателя (ручное добавление, массовое, результат звонка).

## Capabilities

### New Capabilities

<!-- none -->

### Modified Capabilities

- `contacts`: удаление `contactType`/`contactPerson`, сохранение `position`, уточнение описания полей
- `contacts/crud`: удаление полей из форм (включая модалки), кнопка «Организация», таблица ошибок по контакту, сброс через confirm для bounced
- `organizations/crud`: список связанных контактов; таблица всех ошибок рассылок организации (org-wide и по контактам)
- `campaigns`: навигация в таблице адресатов; единый reset; доменный запрет адресата без e-mail
- `dashboard`: отметка bounced e-mail на карточке контакта

## Impact

- `src/Entity/Contact.php`, `src/Entity/Enum/ContactType.php` — удаление type/person
- `src/Controller/ContactController.php`, `CampaignController.php`, organization form controller
- Шаблоны: `contact/form`, модалки контакта, `campaign/recipients`, `organization/form`, dashboard contact card
- `assets/js/contact-modal.js` — убрать поля type/person
- Доменная проверка e-mail при создании `CampaignRecipient` (все entry points)
- `migrations/` — удаление колонок
- `openspec/design/er.md` — убрать `contact_type` / `contact_person` (при реализации)
- При архивации: `adr/0002-contact-organization-relation.md`, Purpose в `openspec/specs/contacts/spec.md`
