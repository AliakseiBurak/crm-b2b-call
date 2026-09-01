## 1. Миграция и сущность Contact

- [ ] 1.1 Создать миграцию: удалить колонки `contact_type` и `contact_person` из таблицы `contact`
- [ ] 1.2 Удалить `src/Entity/Enum/ContactType.php`
- [ ] 1.3 Обновить `src/Entity/Contact.php`: удалить свойства `contactType`, `contactPerson`, методы `setContactType()`, `setContactPerson()`, константу `ContactType` из конструктора

## 2. Контроллер Contact

- [ ] 2.1 Обновить `ContactController::applyRequest()`: удалить обработку `contact_type` и `contact_person`
- [ ] 2.2 Обновить `ContactController::new()` и `create()`: удалить передачу `contactType` в форму

## 3. Форма контакта

- [ ] 3.1 Обновить `templates/contact/form.html.twig`: удалить поля `contactType` и `contactPerson` из формы
- [ ] 3.2 Добавить кнопку «Организация» с ссылкой на `GET /organizations/{id}/edit` в форму редактирования контакта
- [ ] 3.3 Добавить таблицу ошибок рассылок на форму редактирования контакта: запрос `CampaignRecipient` WHERE contact = this AND status IN ('failed', 'bounced') AND campaign.status != 'archived'
- [ ] 3.4 Добавить кнопку «Сбросить» для `failed` получателей с confirm-диалогом
- [ ] 3.5 Добавить кнопку «Сбросить» для `bounced` получателей с ссылкой на страницу подтверждения

## 4. Сброс получателя (CampaignController)

- [ ] 4.1 Добавить маршрут `POST /campaigns/{id}/recipients/{recipientId}/reset` в `CampaignController`
- [ ] 4.2 Реализовать логику сброса: статус → `pending`, очистка `errorMessage`, `retryCount`, `retryAt`
- [ ] 4.3 Создать шаблон подтверждения сброса для `bounced` получателей с предупреждением: заголовок «Письмо отклонено почтовым сервером», текст с шагами (остановить рассылку, изменить e-mail, сбросить статус), примечание о повторном отказе
- [ ] 4.4 Добавить маршрут `POST /campaigns/{id}/recipients/{recipientId}/reset-confirm` для подтверждения сброса `bounced`

## 5. Таблица адресатов

- [ ] 5.1 Обновить `templates/campaign/recipients.html.twig`: сделать организацию кликабельной ссылкой на `/organizations/{id}/edit`
- [ ] 5.2 Сделать контакт кликабельной ссылкой на `/contacts/{id}/edit` (если контакт указан)

## 6. Валидация e-mail при добавлении адресата

- [ ] 6.1 Добавить проверку в `CampaignController::addRecipient()`: перед добавлением проверять, что у организации есть хотя бы один контакт с e-mail
- [ ] 6.2 При отсутствии e-mail перенаправлять обратно с flash-сообщением: «У организации отсутствует e-mail. Добавьте e-mail контакту перед добавлением в рассылку.»
- [ ] 6.3 Если добавлен контакт без e-mail, но у организации есть e-mail — показать flash-уведомление: «У контакта «{name}» отсутствует e-mail. Письмо будет отправлено организации: {email}»

## 7. Форма организации

- [ ] 6.1 Обновить `templates/organization/form.html.twig`: добавить список контактов организации под формой
- [ ] 6.2 Добавить ссылки на редактирование каждого контакта
