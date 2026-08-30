## MODIFIED Requirements

### Requirement: Создание рассылки
The system SHALL let the administrator and managers create campaigns with a name, an email subject, an optional preview text (preheader), an email body with tokens, and optional attachments. A campaign SHALL NOT be bound to a single organization. The campaign's email subject, preview text, body, and attachments SHALL be stored on the campaign itself. A newly created campaign's `status` SHALL default to `draft`. After saving, the user SHALL be redirected to the campaign list.

#### Scenario: Создание рассылки
- **WHEN** администратор создаёт рассылку "Новые курсы" с темой "Приглашаем на курсы 2026", текстом "{{greeting}}! Приглашаем вас на курсы." и вложениями
- **THEN** рассылка "Новые курсы" появляется в списке рассылок
- **AND** её тема, превью, текст и вложения сохраняются на самой рассылке
- **AND** пользователь перенаправляется на список рассылок

#### Scenario: Тема письма рассылки
- **WHEN** администратор создаёт рассылку "Акция" с темой "Скидки недели"
- **THEN** тема "Скидки недели" сохраняется и используется как тема письма при отправке

#### Scenario: Рассылка без курсов
- **WHEN** менеджер создаёт рассылку "Приглашение на вебинар" с текстом и вложениями
- **THEN** рассылка создаётся, а письмо формируется без вложений

### Requirement: Статусы рассылки
The system SHALL support the following campaign statuses: `draft`, `ready`, `launched`, `failed`, `archived`. Each status SHALL have a localized label. The `failed` status SHALL be a technical status set exclusively by the MailingService when a sending error occurs; users SHALL NOT be able to set `failed` manually via the form. Users SHALL be able to reset a `failed` campaign to `ready` for re-launch. The `failedAt` timestamp SHALL be recorded when a campaign transitions to `failed`. The `launchedAt` timestamp SHALL be recorded when a campaign transitions to `launched`.

#### Scenario: Черновик
- **WHEN** менеджер создаёт новую рассылку
- **THEN** её статус по умолчанию — `draft` («Черновик»)

#### Scenario: Ошибка фиксирует время
- **WHEN** рассылка переходит в статус `failed`
- **THEN** поле `failedAt` заполняется текущим временем

### Requirement: Формирование адресатов
The system SHALL provide a dedicated recipients page for each campaign, accessible from the campaign list and show page. Each organization SHALL have at most one recipient per campaign (unique constraint on `campaign_id`, `organization_id`). A recipient MAY specify a contact; when a contact is set, the email SHALL be sent to that contact's email address instead of the organization. Recipients SHALL be editable for all campaign statuses except `archived`; for archived campaigns, the recipients list SHALL be view-only (no add/remove). When a recipient already exists for an organization, the system SHALL prompt the user with a replacement confirmation; on confirmation, the existing recipient is removed and a new one is created. If the campaign has been launched (`launchedAt` is not null), the replacement SHALL trigger a re-send to the organization and the `replacementCount` SHALL be incremented. If the campaign has not been launched yet, the replacement SHALL NOT increment the counter. The system SHALL display a warning on the replacement confirmation page when the campaign has been launched, informing the user that the replacement will trigger a re-send. The system SHALL support bulk-adding all accessible organizations as recipients at once. Each recipient SHALL track a `replacementCount` field showing the number of replacements performed while the campaign was active.

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

#### Scenario: Адресаты для standalone-рассылки
- **WHEN** менеджер создаёт standalone-рассылку «Акция» со статусом «Готова»
- **AND** вручную выбирает организации «ООО Ромашка» и «ООО Б»
- **THEN** получателями рассылки являются только выбранные организации

#### Scenario: Адресат — конкретный контакт
- **WHEN** менеджер добавляет адресата standalone-рассылки «Акция» для организации «ООО Ромашка» и выбирает контакт «Иван Петров» с email ivan@romashka.ru
- **THEN** получателем рассылки является контакт «Иван Петров» организации «ООО Ромашка»
- **AND** письмо отправляется на адрес ivan@romashka.ru

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

#### Scenario: Замена адресата до запуска рассылки
- **WHEN** рассылка «Акция» не запущена (`launchedAt` равен null) и имеет адресата «ООО Ромашка»
- **AND** менеджер заменяет адресата на другой контакт
- **THEN** система отображает страницу подтверждения замены без предупреждения о повторной отправке
- **AND** при подтверждении `replacementCount` НЕ увеличивается

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

### Requirement: Генерация письма по шаблону
The system SHALL generate each email from the campaign's stored subject, preview text, and body by filling tokens (`{{greeting}}`, `{{contact_name}}`, `{{organization_name}}`). The `{{greeting}}` token SHALL resolve to "Уважаемый(ая) {contact_name}" when a contact is set, or "Уважаемые сотрудники {organization_name}" otherwise.

#### Scenario: Приветствие с контактом
- **WHEN** рассылка «Новые курсы» отправляется организации «ООО Ромашка» контакту «Иван Петров»
- **AND** текст письма содержит токен `{{greeting}}`
- **THEN** в письме вместо токена `{{greeting}}` подставлено "Уважаемый(ая) Иван Петров"

#### Scenario: Приветствие без контакта
- **WHEN** рассылка «Новые курсы» отправляется организации «ООО Ромашка» без указания контакта
- **AND** текст письма содержит токен `{{greeting}}`
- **THEN** в письме вместо токена `{{greeting}}` подставлено "Уважаемые сотрудники ООО Ромашка"

#### Scenario: Подстановка имени организации
- **WHEN** рассылка «Новые курсы» содержит текст с токеном `{{organization_name}}`
- **AND** рассылка отправляется организации «ООО Ромашка»
- **THEN** в письме вместо токена `{{organization_name}}` подставлено "ООО Ромашка"

#### Scenario: Встроенные курсы в письме
- **WHEN** рассылка содержит текст и вложения
- **AND** система формирует письмо
- **THEN** в письмо включается текст и вложения рассылки; встраивание курсов не входит в это изменение и рассматривается в сервисе MailingService

#### Scenario: Тема письма
- **WHEN** рассылка «Новые курсы» имеет тему «Приглашаем на курсы»
- **AND** рассылка отправляется организации «ООО Ромашка»
- **THEN** тема отправленного письма содержит «Приглашаем на курсы»

## ADDED Requirements

### Requirement: Вложения рассылки
The system SHALL let the administrator and manager attach one or more files to a campaign on both create and edit pages. Attached files SHALL be sent as email attachments when the campaign is launched. Multiple files SHALL be uploadable in a single form submission.

#### Scenario: Добавление вложений
- **WHEN** администратор редактирует рассылку «Новые курсы»
- **AND** загружает файлы «брошюра.pdf» и «прайс.xlsx»
- **THEN** оба файла сохраняются как вложения рассылки

#### Scenario: Удаление вложения
- **WHEN** администратор удаляет вложение «брошюра.pdf» у рассылки «Новые курсы»
- **THEN** файл удаляется из вложений рассылки

#### Scenario: Вложения при создании
- **WHEN** менеджер создаёт новую рассылку и выбирает файлы для загрузки
- **THEN** файлы сохраняются после создания рассылки

### Requirement: Запуск рассылки
The system SHALL support launching a campaign manually (administrator clicks launch). Launching SHALL set the campaign's `status` to `launched` and record `launchedAt`; actual sending is performed by a separate service. A launch button SHALL be available on the campaign card and as a quick action in the campaign list table, but ONLY when the campaign status is `ready`.

#### Scenario: Ручной запуск
- **WHEN** администратор открывает карточку рассылки «Новые курсы» со статусом `ready`
- **AND** нажимает кнопку «Запустить»
- **THEN** рассылка помечается запущенной

#### Scenario: Запуск из списка
- **WHEN** администратор нажимает кнопку ▶ в строке рассылки со статусом `ready`
- **THEN** рассылка запускается и статус меняется на `launched`

#### Scenario: Остановка из списка
- **WHEN** администратор нажимает кнопку ■ в строке рассылки со статусом `launched`
- **THEN** статус рассылки меняется на `ready`

#### Scenario: Запуск недоступен для черновика
- **WHEN** рассылка имеет статус `draft`
- **THEN** кнопка «Запустить» не отображается на карточке и в списке
- **AND** в списке нет действий для этой строки (как для archived)

#### Scenario: Остановка запущенной рассылки
- **WHEN** администратор нажимает кнопку ■ на карточке рассылки со статусом `launched`
- **THEN** статус рассылки меняется на `ready`
- **AND** рассылка может быть запущена повторно

#### Scenario: Сброс failed-рассылки
- **WHEN** рассылка имеет статус `failed`
- **AND** администратор нажимает «Сбросить» на карточке
- **THEN** статус рассылки меняется на `ready`
- **AND** становится доступна кнопка «Запустить»

#### Scenario: Клонирование рассылки
- **WHEN** менеджер открывает карточку рассылки со статусом `ready`, `launched`, `failed` или `archived`
- **THEN** отображается кнопка «Клонировать» с тремя вариантами: «Без адресатов», «С адресатами», «С адресатами и контактами»
- **AND** при нажатии создаётся новая рассылка со статусом `draft`, копией темы, превью, текста, вложений (метаданные, файлы в storage общие)
- **AND** к названию добавляется суффикс «(копия)»

#### Scenario: Клонирование с адресатами без контактов
- **WHEN** менеджер выбирает «С адресатами» и нажимает «Клонировать»
- **THEN** копируются адресаты (организации) без контактов

#### Scenario: Клонирование с адресатами и контактами
- **WHEN** менеджер выбирает «С адресатами и контактами» и нажимает «Клонировать»
- **THEN** копируются адресаты с их контактами

#### Scenario: Клонирование недоступно для черновика
- **WHEN** рассылка имеет статус `draft`
- **THEN** кнопка «Клонировать» не отображается

#### Scenario: Статус после запуска
- **WHEN** рассылка «Новые курсы» запускается
- **THEN** её `status` устанавливается в `launched`
- **AND** `launchedAt` фиксируется

### Requirement: Удаление рассылки
The system SHALL let the administrator delete a campaign from the edit form. Deletion SHALL remove the campaign, its attachments (files from storage), and its recipients.

#### Scenario: Удаление с карточки
- **WHEN** администратор нажимает «Удалить» на форме редактирования рассылки
- **THEN** система отображает страницу подтверждения удаления

#### Scenario: Подтверждение удаления
- **WHEN** администратор подтверждает удаление рассылки
- **THEN** рассылка, её вложения и адресаты удаляются

### Requirement: Список рассылок и сортировка
The system SHALL display campaigns in a sortable table with columns: Name (with quick actions), Status, anonymous column (with "Адресаты" button), Subject. Sorting SHALL be available on name, status, and subject columns via clickable headers with ascending/descending indicators. Archived campaigns SHALL always appear at the bottom of the list regardless of sort order. The updated campaign SHALL be highlighted after save.

#### Scenario: Сортировка по столбцам
- **WHEN** менеджер кликает по заголовку столбца «Название»
- **THEN** список пересортировывается по названию (ASC/DESC переключается кликом)

#### Scenario: Архивные внизу
- **WHEN** в списке есть рассылки со статусом `archived`
- **THEN** они отображаются внизу списка независимо от выбранной сортировки

#### Scenario: Подсветка обновлённой
- **WHEN** менеджер сохраняет рассылку
- **THEN** он перенаправляется на список, где обновлённая рассылка подсвечена

#### Scenario: Визуальные индикаторы статусов
- **WHEN** рассылка имеет статус `failed`
- **THEN** её строка в списке имеет красную подсветку фона
- **AND** в колонке «Название» отображается индикатор ✕ (красный квадрат)

- **WHEN** рассылка имеет статус `launched`
- **THEN** в колонке «Название» отображается индикатор ■ (оранжевый квадрат)

- **WHEN** рассылка имеет статус `draft` или `ready`
- **THEN** в колонке «Название» отображается кнопка ▶ (зелёный квадрат) для запуска

- **WHEN** рассылка имеет статус `archived`
- **THEN** её строка в списке затемнена (greyout)
