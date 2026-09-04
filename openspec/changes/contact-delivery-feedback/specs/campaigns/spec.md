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

### Requirement: Сброс получателя failed или bounced в pending
The system SHALL provide `POST /campaigns/{id}/recipients/{recipientId}/reset` that resets a recipient with status `failed` or `bounced` to `pending` and clears `errorMessage`, `retryCount`, and `retryAt`. The same route SHALL be used from the recipients page, the contact error table, and the organization error table. Access SHALL follow the same scope rules as other recipient operations.

#### Scenario: Успешный сброс failed получателя
- **WHEN** менеджер с доступом к организации адресата сбрасывает получателя со статусом `failed`
- **THEN** статус получателя становится `pending`
- **AND** поля `errorMessage`, `retryCount`, `retryAt` очищаются
- **AND** получатель будет обработан при следующем цикле фоновой команды

#### Scenario: Успешный сброс bounced получателя
- **WHEN** менеджер с доступом к организации адресата подтверждает сброс получателя со статусом `bounced`
- **THEN** статус получателя становится `pending`
- **AND** поля `errorMessage`, `retryCount`, `retryAt` очищаются

#### Scenario: Получатель не найден
- **WHEN** менеджер пытается сбросить получателя с несуществующим recipientId
- **THEN** система возвращает ошибку 404 "Адресат не найден"

#### Scenario: Получатель не принадлежит рассылке
- **WHEN** менеджер пытается сбросить получателя, который не принадлежит данной рассылке
- **THEN** система возвращает ошибку 404 "Адресат не найден"

#### Scenario: Отказ вне области доступа
- **WHEN** менеджер пытается сбросить получателя организации вне своей области доступа
- **THEN** система отклоняет запрос с ошибкой 403

### Requirement: Организация без e-mail не может стать адресатом
The system SHALL NOT create a `CampaignRecipient` when the organization has no deliverable email among its contacts. The rule SHALL apply to every creation path: manual add, bulk add of all organizations, and mailing action from a call result. When rejected from the recipients UI, the system SHALL redirect back with a flash error. When a specific contact without email is chosen but the organization has another contact with email, the recipient MAY be created and the system SHALL show a flash that mail will go to the organization address.

#### Scenario: Добавление организации без e-mail
- **WHEN** менеджер выбирает организацию "ООО Ромашка" и нажимает "Добавить"
- **AND** у организации нет e-mail ни в одном из контактов
- **THEN** система перенаправляет обратно на страницу адресатов
- **AND** отображается flash-сообщение об ошибке: «У организации отсутствует e-mail. Добавьте e-mail контакту перед добавлением в рассылку.»
- **AND** организация не добавляется в адресаты

#### Scenario: Массовое добавление пропускает организации без e-mail
- **WHEN** менеджер нажимает «Выбрать все организации»
- **AND** среди доступных есть организация без e-mail у контактов
- **THEN** организации без e-mail не добавляются в адресаты
- **AND** организации с e-mail добавляются

#### Scenario: Добавление организации с e-mail у контакта
- **WHEN** менеджер выбирает организацию "ООО Ромашка" и нажимает "Добавить"
- **AND** у организации есть контакт "Иван Петров" с e-mail "ivan@example.com"
- **THEN** организация успешно добавляется в адресаты

#### Scenario: Добавление контакта без e-mail при наличии e-mail у организации
- **WHEN** менеджер выбирает организацию "ООО Ромашка" и контакт "Мария Смирнова" (без e-mail)
- **AND** у организации есть контакт с e-mail "info@romashka.ru"
- **THEN** контакт добавляется в адресаты
- **AND** отображается flash-уведомление: «У контакта «Мария Смирнова» отсутствует e-mail. Письмо будет отправлено организации: info@romashka.ru»

#### Scenario: Результат звонка не создаёт адресата без e-mail
- **WHEN** менеджер выбирает рассылку для звонка по организации без e-mail у контактов
- **AND** сохраняет звонок
- **THEN** адресат в рассылке не создаётся
- **AND** система сообщает об отсутствии e-mail у организации
