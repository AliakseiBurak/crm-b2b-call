## MODIFIED Requirements

### Requirement: Статусы рассылки
The system SHALL support the following campaign statuses: `draft`, `ready`, `launched`, `failed`, `archived`. Each status SHALL have a localized label. `launchedAt` SHALL be recorded when transitioning to `launched`; `failedAt` SHALL be recorded when transitioning to `failed`. A campaign SHALL store a `failureReason` (text, nullable) describing the last processing failure; it SHALL be set when the campaign transitions to `failed` and cleared when the campaign is launched again. `launched` means the campaign is active and being processed by the worker; `failed` means an unrecoverable error occurred during processing; `archived` is a manual archive state for list display.

#### Scenario: Черновик
- **WHEN** менеджер создаёт новую рассылку
- **THEN** её статус по умолчанию — `draft` («Черновик»)

#### Scenario: Ошибка фиксирует время
- **WHEN** рассылка переходит в статус `failed`
- **THEN** поле `failedAt` заполняется текущим временем
- **AND** поле `failureReason` содержит понятное описание причины сбоя

#### Scenario: Переход в обработку
- **WHEN** фоновая команда начинает отправку писем запущенной рассылки
- **THEN** статус рассылки остаётся `launched`

## ADDED Requirements

#### Scenario: Получатели создаются до запуска
- **WHEN** менеджер создаёт рассылку и добавляет организации-получатели
- **AND** затем нажимает «Запустить»
- **THEN** получатели уже существуют как записи `CampaignRecipient` до момента запуска

### Requirement: Отправка писем (фоновая обработка, MailingService)
The system SHALL provide a `MailingService` invoked by a background command (`app:campaign:send` or similar) that polls for campaigns in `launched` status having `CampaignRecipient` rows with status `pending` or `failed` with `retry_at <= NOW()` and `retry_count < 3`. The worker SHALL process up to `MAILING_BATCH_SIZE` recipients globally (configurable via .env, default 10) per iteration and be invoked periodically via cron. The service SHALL read the email subject, preview text and body from the **campaign's own stored fields**, fill tokens `{{greeting}}`, `{{contact_name}}`, `{{organization_name}}`, and send via SMTP (Symfony Mailer). The system SHALL send one email per recipient organization. If a recipient specifies a contact with an email address, the email SHALL be sent to that contact only (TO). If a recipient specifies a contact without an email address, the email SHALL be sent to the first email address of the organization (TO) with all remaining organization email addresses in CC. If no contact is specified, the email SHALL be sent to the first email address of the organization (TO) with all remaining organization email addresses in CC. Each recipient SHALL be processed independently; a failure for one recipient SHALL NOT affect others. When the campaign starts processing its status remains `launched`; on an unrecoverable error it becomes `failed`.

#### Scenario: Обработка запущенной рассылки фоновой командой
- **WHEN** фоновая команда запускается
- **AND** находит запущенную рассылку с получателями в статусе `pending`
- **THEN** она отправляет письма этим получателям и обновляет их статусы

#### Scenario: Индивидуальная фиксация ошибки
- **WHEN** конкретный получатель не отправляется (ошибка SMTP)
- **THEN** только этот получатель помечается ошибкой/отказом, а остальные продолжают обрабатываться

#### Scenario: Источник шаблона — поля рассылки
- **WHEN** фоновая команда формирует письмо получателю
- **THEN** она использует тему, превью и текст, сохранённые на самой рассылке

### Requirement: Статус получателя рассылки (per-letter)
The system SHALL track per-recipient send status on `CampaignRecipient` with the values `pending` → `sending` → (`delivered` | `bounced` | `failed`), plus `opened`. `pending` — создан, ещё не отправлен; `sending` — передан в обработку (SMTP); `delivered`/`bounced`/`failed` — результат SMTP-отправки; `opened` — по tracking-pixel. For transient errors (SMTP timeout, 4xx) the recipient SHALL be marked `failed` with `retry_count` and `retry_at` (exponential backoff + jitter, max 3 retries). After max retries the failure becomes permanent. This implements per-letter statuses from ADR-0010.

#### Scenario: Начальный статус получателя
- **WHEN** создаётся запись `CampaignRecipient`
- **THEN** её статус равен `pending`

#### Scenario: Пометка доставки, отказа или ошибки
- **WHEN** фоновая команда передаёт письмо в SMTP и получает результат
- **THEN** статус получателя становится `delivered`, `bounced` или `failed`

#### Scenario: Повторная попытка при transient ошибке
- **WHEN** SMTP возвращает timeout или 4xx
- **THEN** статус получателя становится `failed` с `retry_count` и `retry_at` (exponential backoff + jitter)
- **AND** при следующем цикле worker обработает этого получателя повторно (если `retry_count < 3` и `retry_at <= NOW()`)

#### Scenario: Permanent failure после исчерпания попыток
- **WHEN** `retry_count` достиг 3
- **THEN** ошибка становится permanent, получатель остаётся `failed` без повторных попыток

#### Scenario: Пометка прочтения
- **WHEN** запрашивается tracking-pixel конкретного получателя
- **THEN** его статус становится `opened`

### Requirement: Счётчик отправленных писем
The system SHALL display on the campaign page a counter of processed recipients versus total recipients based on `CampaignRecipient.status`.

#### Scenario: Отображение счётчика
- **WHEN** пользователь открывает карточку рассылки в процессе отправки
- **THEN** на странице показан счётчик «обработано X из Y»

### Requirement: Статистика доставки в списке рассылок
The system SHALL show a statistics column in the campaigns list table displaying "x из y", where x is the number of the campaign's recipients with status `delivered` and y is the total number of recipients of the campaign. The value SHALL be derived from `CampaignRecipient` statuses (count of `delivered` vs total recipients).

#### Scenario: Колонка статистики в списке
- **WHEN** менеджер открывает список рассылок
- **THEN** в таблице есть колонка «Статистика» со значением «x из y», где x — число получателей со статусом `delivered`, y — общее число получателей рассылки

#### Scenario: Статистика отражает доставку
- **WHEN** у рассылки 10 получателей и 7 из них доставлены (статус `delivered`)
- **THEN** в колонке «Статистика» показано «7 из 10»

### Requirement: Обработка ошибок отправки
The system SHALL, on an unrecoverable error during processing (например, неверная конфигурация SMTP, отсутствие доставляемого адреса у всех получателей, ошибка формирования письма), установить статус рассылки `failed`, записать понятное и действие-ориентированное описание в `failureReason` (что сломалось и как исправить) и отправить email-уведомление администратору сайта. Пользователь исправляет проблему и запускает рассылку заново (статус возвращается в `launched`, `failureReason` очищается, команда продолжает обработку оставшихся `pending` получателей).

#### Scenario: Переход в статус ошибка
- **WHEN** во время отправки возникает неустранимая ошибка
- **THEN** статус рассылки становится `failed`
- **AND** поле `failureReason` содержит понятное сообщение для пользователя

#### Scenario: Уведомление администратора
- **WHEN** рассылка переходит в статус `failed`
- **THEN** администратору сайта отправляется email-уведомление об ошибке с описанием из `failureReason`

#### Scenario: Повторный запуск после исправления
- **WHEN** пользователь исправляет ошибку и запускает рассылку заново
- **THEN** статус возвращается в `launched`, `failureReason` очищается, и фоновая команда продолжает обработку оставшихся получателей со статусом `pending`

### Requirement: Получатель без адреса доставки
The system SHALL handle a `CampaignRecipient` whose organization (and specified contact, if any) has no email: the recipient is marked `failed` with an `errorMessage` describing the cause (e.g., "Отсутствует email-адрес организации") and processing continues with the other recipients; это не прерывает рассылку целиком (но если недоставляемы все получатели, возможна эскалация в `failed` согласно "Обработка ошибок отправки").

#### Scenario: Рассылка по организации без контактного email
- **WHEN** у организации-получателя (и указанного контакта) нет email
- **THEN** этот получатель помечается `failed` с `errorMessage`, а остальные получатели продолжают обрабатываться

#### Scenario: Отображение ошибки на странице Адресаты
- **WHEN** менеджер открывает страницу адресатов рассылки
- **AND** у адресата есть `errorMessage`
- **THEN** под строкой адресата отображается красное сообщение об ошибке из `errorMessage`
- **AND** если `errorMessage` отсутствует, сообщение об ошибке не отображается
