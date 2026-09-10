## ADDED Requirements

### Requirement: Данные прогресса рассылок
The system SHALL provide `GET /campaigns/statuses` returning for every campaign its `campaignId`, `delivered` and `total`, derived from `CampaignRecipient` statuses (`delivered` — recipients with status `delivered` or `opened`, `total` — all recipients of the campaign). The system SHALL provide `GET /campaigns/{id}/recipients/statuses` returning `recipientId` and `status` for every recipient of that campaign. Both endpoints SHALL be accessible to authenticated users only and SHALL NOT be cached by intermediaries.

#### Scenario: Получение статистики всех рассылок
- **WHEN** клиент запрашивает `GET /campaigns/statuses`
- **THEN** ответ содержит для каждой рассылки её `campaignId`, `delivered` и `total`
- **AND** `delivered` — число получателей со статусом `delivered` или `opened`, `total` — число всех получателей рассылки

#### Scenario: Получение статусов адресатов
- **WHEN** клиент запрашивает `GET /campaigns/123/recipients/statuses`
- **THEN** ответ содержит `recipientId` и `status` каждого адресата рассылки 123

#### Scenario: Несуществующая рассылка
- **WHEN** клиент запрашивает `GET /campaigns/999/recipients/statuses` для несуществующей рассылки
- **THEN** система возвращает ошибку 404

### Requirement: Обновление статистики в реальном времени
The system SHALL update the «Статистика» column («x из y») in the campaigns list table without a page reload, by polling `GET /campaigns/statuses` every 1.9 seconds. A cell SHALL be re-rendered only when its value changed.

#### Scenario: Статистика обновляется без перезагрузки
- **WHEN** рассылка обрабатывается фоновой командой
- **AND** менеджер находится на странице списка рассылок
- **THEN** колонка «Статистика» соответствующей рассылки обновляется автоматически без перезагрузки страницы

#### Scenario: Статистика отражает доставку
- **WHEN** у рассылки 10 получателей, 7 из них со статусом `delivered` или `opened`
- **AND** менеджер находится на странице списка рассылок
- **THEN** в колонке «Статистика» показано «7 из 10»

### Requirement: Обновление статусов адресатов в реальном времени
The system SHALL update the per-letter status badge of each recipient on the «Адресаты» page without a page reload, by polling `GET /campaigns/{id}/recipients/statuses` of that campaign every 1.1 seconds. A row SHALL be re-rendered only when its status changed. Statuses of recipients of other campaigns SHALL NOT affect the page.

#### Scenario: Статус адресата обновляется без перезагрузки
- **WHEN** фоновая команда меняет статус получателя (например, `pending` → `delivered`)
- **AND** менеджер находится на странице «Адресаты» этой рассылки
- **THEN** бейдж статуса этого адресата обновляется автоматически без перезагрузки страницы

#### Scenario: Статусы других рассылок не влияют на страницу
- **WHEN** менеджер открыл страницу «Адресаты» рассылки 123
- **AND** фоновая команда меняет статусы адресатов другой рассылки
- **THEN** статусы адресатов на открытой странице не изменяются
