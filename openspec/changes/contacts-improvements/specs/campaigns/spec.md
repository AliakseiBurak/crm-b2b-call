## ADDED Requirements

### Requirement: Навигация в таблице адресатов
The system SHALL display clickable links for Organization and Contact columns in the campaign recipients table, navigating to their respective edit pages.

#### Scenario: Ссылка на организацию
- **WHEN** менеджер открывает страницу адресатов рассылки
- **AND** у адресата есть организация "ООО Ромашка"
- **THEN** название организации отображается как кликабельная ссылка
- **AND** ссылка ведёт на `GET /organizations/{id}/edit` организации "ООО Ромашка"

#### Scenario: Ссылка на контакт
- **WHEN** менеджер открывает страницу адресатов рассылки
- **AND** у адресата указан контакт "Иван Петров"
- **THEN** имя контакта отображается как кликабельная ссылка
- **AND** ссылка ведёт на `GET /contacts/{id}/edit` контакта "Иван Петров"

#### Scenario: Контакт не указан
- **WHEN** менеджер открывает страницу адресатов рассылки
- **AND** у адресата контакт не указан (отправляется всей организации)
- **THEN** в колонке "Контакт" отображается прочерк "—"
- **AND** прочерк не является ссылкой

### Requirement: Сброс получателя из failed в pending
The system SHALL provide a route `POST /campaigns/{id}/recipients/{recipientId}/reset` that resets a `failed` recipient status to `pending`, clears `errorMessage`, `retryCount`, and `retryAt`.

#### Scenario: Успешный сброс failed получателя
- **WHEN** менеджер нажимает кнопку "Сбросить" для получателя со статусом `failed` на странице адресатов
- **THEN** статус получателя изменяется на `pending`
- **AND** поля `errorMessage`, `retryCount`, `retryAt` очищаются
- **AND** получатель будет обработан при следующем цикле фоновой команды

#### Scenario: Получатель не найден
- **WHEN** менеджер пытается сбросить получателя с несуществующим recipientId
- **THEN** система возвращает ошибку 404 "Адресат не найден"

#### Scenario: Получатель не принадлежит рассылке
- **WHEN** менеджер пытается сбросить получателя, который не принадлежит данной рассылке
- **THEN** система возвращает ошибку 404 "Адресат не найден"

### Requirement: Валидация наличия e-mail при добавлении адресата
The system SHALL validate that the organization or its contacts have at least one email address when adding a recipient to a campaign. If no email is available, the system SHALL redirect back with a flash error message.

#### Scenario: Добавление организации без e-mail
- **WHEN** менеджер выбирает организацию "ООО Ромашка" и нажимает "Добавить"
- **AND** у организации нет e-mail ни в одном из контактов
- **THEN** система перенаправляет обратно на страницу адресатов
- **AND** отображается flash-сообщение об ошибке: «У организации отсутствует e-mail. Добавьте e-mail контакту перед добавлением в рассылку.»
- **AND** организация не добавляется в адресаты

#### Scenario: Добавление организации с e-mail у контакта
- **WHEN** менеджер выбирает организацию "ООО Ромашка" и нажимает "Добавить"
- **AND** у организации есть контакт "Иван Петров" с e-mail "ivan@example.com"
- **THEN** организация успешно добавляется в адресаты
- **AND** происходит перенаправление на страницу адресатов

#### Scenario: Добавление контакта без e-mail
- **WHEN** менеджер выбирает организацию "ООО Ромашка" и контакт "Мария Смирнова" (без e-mail)
- **AND** у организации есть контакт с e-mail "info@romashka.ru"
- **THEN** контакт добавляется в адресаты
- **AND** отображается flash-уведомление: «У контакта «Мария Смирнова» отсутствует e-mail. Письмо будет отправлено организации: info@romashka.ru»
