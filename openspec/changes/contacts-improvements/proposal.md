## Why

Контакт содержит неиспользуемые поля (`contactType`, `contactPerson`), усложняющие сущность без пользы. Одновременно отсутствует связь между контактом и ошибками рассылок: если письмо получателю не доставлено (статус `failed` или `bounced`), менеджер не видит этого на странице контакта и не может исправить ситуацию. Таблица адресатов рассылки не предоставляет навигации — ссылки на организацию и контакт отсутствуют.

## What Changes

- **BREAKING**: Удаление полей `contactType` (enum `ContactType`) и `contactPerson` из сущности `Contact`. Миграция удаляет колонки из БД.
- Таблица ошибок рассылок на форме редактирования контакта: получатели со статусами `failed` и `bounced`, где contact = данный контакт. Для `failed` — кнопка «Сбросить» (возврат в `pending`). Для `bounced` — кнопка «Сбросить» ведёт на страницу подтверждения с предупреждением: письмо отклонено почтовым сервером, нужно остановить рассылку, изменить e-mail контакта, затем сбросить статус.
- Кнопка «Организация» на форме редактирования контакта — переход на `GET /organizations/{id}/edit`.
- Кликабельные ссылки в таблице адресатов: Организация → `/organizations/{id}/edit`, Контакт → `/contacts/{id}/edit`.
- Список связанных контактов в форме редактирования организации.
- Маршрут `POST /campaigns/{id}/recipients/{recipientId}/reset` для сброса получателя из `failed` и `bounced` в `pending`.
- Валидация при добавлении адресата: проверка наличия e-mail у организации (через контакты) перед добавлением в рассылку.

## Capabilities

### Modified Capabilities

- `contacts`: удаление полей `contactType` и `contactPerson`, изменение описания сущности
- `contacts/crud`: удаление полей из формы, добавление кнопки «Организация» и таблицы ошибок на страницу редактирования
- `campaigns`: добавление маршрута сброса получателя, ссылок на contact и organization в таблице адресатов

## Impact

- `src/Entity/Contact.php` — удаление `contactType`, `contactPerson`, связанных методов
- `src/Entity/Enum/ContactType.php` — удаление файла
- `src/Controller/ContactController.php` — обновление `applyRequest()`, удаление обработки удалённых полей
- `src/Controller/CampaignController.php` — новый `resetRecipient()` action, валидация e-mail в `addRecipient()`
- `templates/contact/form.html.twig` — удаление полей, добавление таблицы ошибок и кнопки «Организация»
- `templates/campaign/recipients.html.twig` — кликабельные ссылки
- `templates/organization/form.html.twig` — список контактов
- `migrations/` — новая миграция для удаления колонок
