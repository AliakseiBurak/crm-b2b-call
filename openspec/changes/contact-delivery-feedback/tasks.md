## 1. Миграция и сущность Contact

- [ ] 1.1 Создать миграцию: удалить колонки `contact_type` и `contact_person` из таблицы `contact`
- [ ] 1.2 Удалить `src/Entity/Enum/ContactType.php`
- [ ] 1.3 Обновить `src/Entity/Contact.php`: удалить `contactType`, `contactPerson` и связанные методы; сохранить `position`
- [ ] 1.4 Обновить `openspec/design/er.md`: убрать `contact_type` / `contact_person`

## 2. Контроллер и формы контакта

- [ ] 2.1 Обновить `ContactController::applyRequest()` / create: убрать `contact_type` и `contact_person`
- [ ] 2.2 Обновить `templates/contact/form.html.twig`: убрать поля type/person; оставить должность
- [ ] 2.3 Обновить модалки `_create_modal.html.twig`, `_edit_modal.html.twig` и `assets/js/contact-modal.js`
- [ ] 2.4 Обновить `templates/contact/_card.html.twig`: убрать data-атрибуты type/person
- [ ] 2.5 Добавить кнопку «Организация» → `GET /organizations/{id}/edit` на форме редактирования
- [ ] 2.6 Таблица ошибок на форме контакта: `CampaignRecipient` WHERE contact = this AND status IN (failed, bounced) AND campaign.status != archived
- [ ] 2.7 Кнопка «Сбросить» для failed — confirm-диалог → `POST …/reset`
- [ ] 2.8 Кнопка «Сбросить» для bounced — страница подтверждения → тот же `POST …/reset`

## 3. Сброс получателя (CampaignController)

- [ ] 3.1 Маршрут `POST /campaigns/{id}/recipients/{recipientId}/reset` для статусов `failed` и `bounced`
- [ ] 3.2 Логика: статус → `pending`; очистить `errorMessage`, `retryCount`, `retryAt`; 404/403 по правилам адресатов
- [ ] 3.3 GET-страница подтверждения для bounced с предупреждением о почтовом сервере (без отдельного confirm-POST)

## 4. Таблица адресатов и домен e-mail

- [ ] 4.1 Ссылки Организация / Контакт в `recipients.html.twig`
- [ ] 4.2 Доменная проверка: организация без e-mail у контактов не становится адресатом (add, bulk, call-result)
- [ ] 4.3 Flash при отказе add; flash при контакте без e-mail, но с e-mail у организации

## 5. Форма организации

- [ ] 5.1 Список контактов организации со ссылками на edit
- [ ] 5.2 Таблица ошибок организации: все recipients org со status failed/bounced, campaign != archived (включая contact); колонка контакта ("—" или ссылка); те же действия сброса; отмена bounced confirm → назад на org edit

## 6. Дашборд

- [ ] 6.1 Отметка на карточке контакта при наличии bounced в неархивной рассылке

## 7. Тесты

- [ ] 7.1 Обновить фикстуры и тесты, завязанные на `ContactType` / `contact_person`
- [ ] 7.2 Покрыть reset, e-mail gate, таблицы ошибок, dashboard mark

## 8. При архивации

- [ ] 8.1 Обновить ADR-0002: поля контакта без типа и контактного лица
- [ ] 8.2 Обновить Purpose в `openspec/specs/contacts/spec.md` (без type/person, с должностью) — после sync delta specs
