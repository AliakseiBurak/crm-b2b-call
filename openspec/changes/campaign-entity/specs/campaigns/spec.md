## MODIFIED Requirements

### Requirement: Создание рассылки
The system SHALL let the administrator and managers create campaigns with a name, an email subject, an email template, and optional attachments. A campaign SHALL NOT be bound to a single organization. The campaign's email subject, template, and attachments SHALL be stored on the campaign itself. A newly created campaign's `status` SHALL default to `draft`.

#### Scenario: Создание рассылки
- **WHEN** администратор создаёт рассылку "Новые курсы" с темой "Приглашаем на курсы 2026", шаблоном "Здравствуйте, {{organization_name}}!" и вложениями
- **THEN** рассылка "Новые курсы" появляется в списке рассылок
- **AND** её тема, шаблон и вложения сохраняются на самой рассылке

#### Scenario: Тема письма рассылки
- **WHEN** администратор создаёт рассылку "Акция" с темой "Скидки недели"
- **THEN** тема "Скидки недели" сохраняется и используется как тема письма при отправке

#### Scenario: Рассылка без курсов
- **WHEN** менеджер создаёт рассылку "Приглашение на вебинар" с шаблоном вложений
- **THEN** рассылка создаётся, а письмо формируется без вложений

### Requirement: Формирование адресатов
The system SHALL form campaign recipients from organizations marked with the campaign result (`mailing` and `refusal_mailing`) during calls, and/or from organizations selected manually for a standalone campaign. A manager SHALL add only organizations from their access scope as recipients (`adr/0007`).

#### Scenario: Адресаты из обзвона
- **WHEN** у рассылки «Новые курсы» включён флаг связи со звонками
- **AND** организации «ООО Ромашка» и «ООО А» имеют результат звонок «рассылка» с этой кампанией
- **THEN** эти организации включены в список получателей рассылки «Новые курки»

#### Scenario: Адресаты для standalone-рассылки
- **WHEN** менеджер создаёт standalone-рассылку «Акция»
- **AND** вручную выбирает организации «ООО Ромашка» и «ООО Б»
- **THEN** получателями рассылки являются только выбранные организации

#### Scenario: Менеджер не может добавить недоступную организацию адресатом
- **WHEN** в системе существует организация «ООО Конкурент», отсутствующая в области доступа менеджера
- **AND** менеджер пытается добавить её адресатом standalone-рассылки
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** организация не включается в получатели

### Requirement: Генерация письма по шаблону
The system SHALL generate each email from the campaign's stored subject and template by filling tokens (`{{contact_name}}`, `{{organization_name}}`).

#### Scenario: Подстановка имени организации
- **WHEN** рассылка «Новые курсы» содержит шаблон с токеном `{{organization_name}}`
- **AND** рассылка отправляется организации «ООО Ромашка»
- **THEN** в письме вместо токена `{{organization_name}}` подставлено "ООО Ромашка"

#### Scenario: Встроенные курсы в письме
- **WHEN** рассылка содержит шаблон и вложения
- **AND** система формирует письмо
- **THEN** в письмо включается шаблон и вложения рассылки; встраивание курсов не входит в это изменение и рассматривается в сервисе MailingService

#### Scenario: Тема письма
- **WHEN** рассылка «Новые курсы» имеет тему «Приглашаем на курсы»
- **AND** рассылка отправляется организации «ООО Ромашка»
- **THEN** тема отправленного письма содержит «Приглашаем на курсы»

## ADDED Requirements

### Requirement: Вложения рассылки
The system SHALL let the administrator and manager attach files to a campaign; attached files SHALL be sent as email attachments when the campaign is launched.

#### Scenario: Добавление вложения
- **WHEN** администратор редактирует рассылку «Новые курсы»
- **AND** загружает файл «брошюра.pdf»
- **THEN** файл сохраняется как вложение рассылки

#### Scenario: Удаление вложения
- **WHEN** администратор удаляет вложение «брошюра.pdf» у рассылки «Новые курсы»
- **THEN** файл удаляется из вложений рассылки

### Requirement: Связь рассылки со звонками и защита от удаления
The system SHALL allow a campaign to be linked to calls through call results. The system SHALL NOT delete a campaign while at least one call result references it.

#### Scenario: Удаление рассылки со связанными звонками заблокировано
- **WHEN** существует звонок с результатом «рассылка», ссылающимся на рассылку «Новые курсы»
- **AND** администратор пытается удалить рассылку «Новые курсы»
- **THEN** система отклоняет удаление с ошибкой
- **AND** рассылка остаётся в системе

#### Scenario: Удаление рассылки без связанных звонков
- **WHEN** рассылка «Акция» не имеет связанных результатов звонков
- **AND** администратор удаляет её
- **THEN** рассылка удаляется из системы

### Requirement: Запуск рассылки
The system SHALL support launching a campaign either manually (administrator clicks launch) or automatically when a call is marked completed with this campaign as its mailing result and the campaign's `auto_launch` flag is enabled. Launching SHALL set the campaign's `status` to `launched`; actual sending is performed by a separate service.

#### Scenario: Ручной запуск
- **WHEN** администратор открывает карточку рассылки «Новые курсы»
- **AND** нажимает кнопку «Запустить»
- **THEN** рассылка помечается запущенной

#### Scenario: Авто-запуск при завершении звонка
- **WHEN** менеджер завершает звонок с результатом «рассылка» и выбирает рассылку «Новые курсы» с включённым авто-запуском
- **AND** звонок отмечен как совершённый
- **THEN** рассылка «Новые курсы» помечается запущенной автоматически

#### Scenario: Статус после запуска
- **WHEN** рассылка «Новые курсы» запускается (вручную или автоматически)
- **THEN** её `status` устанавливается в `launched`
