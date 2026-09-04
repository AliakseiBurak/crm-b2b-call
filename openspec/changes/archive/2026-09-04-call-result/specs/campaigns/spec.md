## MODIFIED Requirements

### Requirement: Формирование адресатов
The system SHALL provide a dedicated recipients page for each campaign, accessible from the campaign list and show page. Each organization SHALL have at most one recipient per campaign (unique constraint on `campaign_id`, `organization_id`). A recipient MAY specify a contact; when a contact is set, the email SHALL be sent to that contact's email address instead of the organization. Recipients SHALL be editable for all campaign statuses except `archived`; for archived campaigns, the recipients list SHALL be view-only (no add/remove). When a recipient already exists for an organization — including the same contact — the system SHALL prompt the user with a replacement confirmation; on confirmation, the existing recipient is removed and a new one is created. If the campaign has been launched (`launchedAt` is not null), the replacement SHALL trigger a re-send to the organization, the `replacementCount` SHALL be incremented, and the system SHALL show a flash that the email will be resent. If the campaign has not been launched yet, the replacement SHALL NOT increment the counter and SHALL NOT claim a resend. The system SHALL display a warning on the replacement confirmation page when the campaign has been launched, informing the user that the replacement will trigger a re-send. The system SHALL support bulk-adding all accessible organizations as recipients at once. Each recipient SHALL track a `replacementCount` field showing the number of replacements performed while the campaign was active.

#### Scenario: Страница адресатов
- **WHEN** менеджер открывает страницу адресатов рассылки «Акция»
- **THEN** он видит таблицу с колонками: Организация, Контакт, П/отправка, Действия

#### Scenario: Добавление адресата
- **WHEN** менеджер выбирает организацию «ООО Ромашка» и нажимает «Добавить»
- **THEN** организация добавляется как адресат рассылки

#### Scenario: Массовое добавление всех организаций
- **WHEN** менеджер нажимает «Выбрать все организации»
- **THEN** все доступные организации добавляются как адресаты
- **AND** уже существующие организации пропускаются

#### Scenario: Замена адресата организации
- **WHEN** рассылка «Акция» уже имеет адресата «ООО Ромашка» (без контакта)
- **AND** менеджер добавляет адресата для «ООО Ромашка» с контактом «Иван Петров»
- **THEN** система отображает страницу подтверждения замены
- **AND** при подтверждении текущий адресат удаляется и добавляется новый с контактом «Иван Петров»

#### Scenario: Замена адресата в запущенной рассылке
- **WHEN** рассылка «Акция» запущена (`launchedAt` не null) и имеет адресата «ООО Ромашка»
- **AND** менеджер заменяет адресата на другой контакт
- **THEN** система отображает предупреждение «Рассылка уже запущена. Замена адресата инициирует повторную отправку письма данной организации.»
- **AND** при подтверждении `replacementCount` текущего адресата увеличивается на 1
- **AND** новый адресат создаётся с увеличенным счётчиком
- **AND** система показывает flash о повторной отправке письма

#### Scenario: Повторное добавление того же контакта в запущенной рассылке
- **WHEN** рассылка «Акция» запущена и имеет адресата «ООО Ромашка» с контактом «Иван Петров»
- **AND** менеджер снова добавляет «ООО Ромашка» с контактом «Иван Петров»
- **THEN** система отображает страницу подтверждения замены с предупреждением о повторной отправке
- **AND** при подтверждении адресат заменяется
- **AND** `replacementCount` увеличивается на 1
- **AND** система показывает flash о повторной отправке письма

#### Scenario: Замена адресата до запуска рассылки
- **WHEN** рассылка «Акция» не запущена (`launchedAt` равен null) и имеет адресата «ООО Ромашка»
- **AND** менеджер заменяет адресата на другой контакт
- **THEN** система отображает страницу подтверждения замены без предупреждения о повторной отправке
- **AND** при подтверждении `replacementCount` НЕ увеличивается
- **AND** flash о повторной отправке не показывается

#### Scenario: Менеджер не может добавить недоступную организацию адресатом
- **WHEN** в системе существует организация «ООО Конкурент», отсутствующая в области доступа менеджера
- **AND** менеджер пытается добавить её адресатом standalone-рассылки
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** организация не включается в получатели

#### Scenario: Адресаты нельзя добавить для архивированной рассылки
- **WHEN** рассылка «Новые курсы» имеет статус `archived`
- **AND** менеджер пытается добавить или удалить адресата
- **THEN** система отклоняет запрос с сообщением «Адресаты недоступны для рассылки в статусе «В архиве»»

#### Scenario: Просмотр адресатов архивированной рассылки
- **WHEN** менеджер открывает страницу адресатов архивированной рассылки
- **THEN** он видит таблицу адресатов без кнопок удаления и формы добавления

#### Scenario: Адресаты доступны для черновика
- **WHEN** рассылка имеет статус `draft`
- **THEN** менеджер может добавлять и удалять адресатов через страницу адресатов

#### Scenario: Адресаты доступны для готовой рассылки
- **WHEN** рассылка имеет статус `ready`
- **THEN** менеджер может добавлять и удалять адресатов через страницу адресатов

#### Scenario: Адресаты доступны для запущенной рассылки
- **WHEN** рассылка имеет статус `launched`
- **THEN** менеджер может добавлять и удалять адресатов через страницу адресатов

#### Scenario: Адресаты доступны для рассылки с ошибкой
- **WHEN** рассылка имеет статус `failed`
- **THEN** менеджер может добавлять и удалять адресатов через страницу адресатов

#### Scenario: Переход на страницу адресатов из списка
- **WHEN** менеджер нажимает кнопку «Адресаты» в анонимной колонке таблицы рассылок
- **THEN** он перенаправляется на страницу адресатов соответствующей рассылки

#### Scenario: Переход на страницу адресатов из карточки
- **WHEN** менеджер нажимает кнопку «Адресаты» на карточке рассылки
- **THEN** он перенаправляется на страницу адресатов соответствующей рассылки

#### Scenario: Получатели создаются до запуска
- **WHEN** менеджер создаёт рассылку и добавляет организации-получатели
- **AND** затем нажимает «Запустить»
- **THEN** получатели уже существуют как записи `CampaignRecipient` до момента запуска
