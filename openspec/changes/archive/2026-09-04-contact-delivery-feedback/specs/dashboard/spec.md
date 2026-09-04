## MODIFIED Requirements

### Requirement: Контакты организации на панели
The system SHALL let the user expand an organization row on the dashboard
to reveal the contact cards of that organization. The system SHALL render
contact cards with the contact name, the phone as a clickable `tel:` link
and the email as a clickable `mailto:` link. A card SHALL also render the
non-empty `Contact.notes` of the contact under a «Заметка» label. When the
contact has at least one `CampaignRecipient` with status `bounced` in a
non-archived campaign, the card SHALL show a visible mark indicating a
bounced email. The cards SHALL NOT contain call or other action buttons.

#### Scenario: Раскрытие контактов организации
- **WHEN** пользователь на панели кликает по строке организации, у которой есть контакты
- **THEN** под строкой отображаются карточки всех контактов этой организации
- **AND** каждая карточка содержит имя, телефон как кликабельную ссылку, email как кликабельную ссылку и кнопку «Изменить»-заглушку
- **AND** при заполненной заметке контакта карточка содержит строку «Заметка: …»
- **AND** на карточке нет кнопки «Позвонить» и других кнопок звонка
- **AND** повторный клик по строке скрывает контакты

#### Scenario: Организация без контактов
- **WHEN** пользователь раскрывает строку организации, у которой нет контактов
- **THEN** под строкой отображается только кнопка «Добавить контакт»
- **AND** никакие карточки контактов и сообщения не показываются

#### Scenario: Отметка bounced e-mail на карточке контакта
- **WHEN** у контакта "Иван Петров" есть получатель рассылки со статусом `bounced`
- **AND** рассылка не в статусе `archived`
- **AND** пользователь раскрывает контакты организации на дашборде
- **THEN** на карточке контакта "Иван Петров" отображается отметка об отказе e-mail

#### Scenario: Нет отметки без bounced
- **WHEN** у контакта нет получателей со статусом `bounced` в неархивных рассылках
- **AND** пользователь раскрывает контакты организации на дашборде
- **THEN** на карточке контакта отметка об отказе e-mail отсутствует

#### Scenario: Архивный bounced не даёт отметку
- **WHEN** у контакта есть получатель со статусом `bounced` только в рассылке со статусом `archived`
- **AND** пользователь раскрывает контакты организации на дашборде
- **THEN** на карточке контакта отметка об отказе e-mail отсутствует
