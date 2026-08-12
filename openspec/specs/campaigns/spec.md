# Campaigns

Модуль рассылок: заранее созданные кампании, отправка писем организациям
(отмеченным во время обзвона или выбранным вручную), отслеживание статусов и
отписок. Отправка — outbox через SMTP (см. ADR-0010).

## Purpose

Рассылки: создание кампаний с шаблоном (токены + встроенные курсы),
формирование адресатов из результатов звонков или вручную, отправка через
outbox и отслеживание статуса каждого письма (sent/delivered/bounced/opened).

## Requirements

### Requirement: Создание рассылки
The system SHALL let the administrator and managers create campaigns with a
name, a communication template, and optional courses, before the calls start.
A campaign SHALL NOT be bound to a single organization.

#### Scenario: Создание рассылки
- **WHEN** администратор создаёт рассылку "Новые курсы" с шаблоном "Знакомство с курсами"
- **THEN** рассылка "Новые курсы" появляется в списке рассылок

#### Scenario: Рассылка без курсов
- **WHEN** менеджер создаёт рассылку "Приглашение на вебинар" с шаблоном без курсов
- **THEN** рассылка создаётся, а письмо формируется без встроенных курсов

### Requirement: Формирование адресатов
The system SHALL form campaign recipients from organizations marked with the
campaign result during calls and/or from organizations selected manually for a
standalone campaign. A manager SHALL add only organizations from their access
scope as recipients (`adr/0007`).

#### Scenario: Адресаты из обзвона
- **WHEN** организации "ООО Ромашка" и "ООО А" отмечены рассылкой "Новые курсы" во время обзвона
- **THEN** эти организации включены в список получателей рассылки "Новые курсы"

#### Scenario: Адресаты для standalone-рассылки
- **WHEN** менеджер создаёт standalone-рассылку "Акция"
- **AND** вручную выбирает организации "ООО Ромашка" и "ООО Б"
- **THEN** получателями рассылки являются только выбранные организации

#### Scenario: Менеджер не может добавить недоступную организацию адресатом
- **WHEN** в системе существует организация "ООО Конкурент", отсутствующая в области доступа менеджера
- **AND** менеджер пытается добавить её адресатом standalone-рассылки
- **THEN** система отклоняет запрос с ошибкой 403
- **AND** организация не включается в получатели

### Requirement: Генерация письма по шаблону
The system SHALL generate each email from the campaign template by filling
tokens (`{{contact_name}}`, `{{organization_name}}`) and the embedded courses.

#### Scenario: Подстановка имени организации
- **WHEN** рассылка "Новые курсы" отправляется организации "ООО Ромашка"
- **THEN** в письме вместо токена `{{organization_name}}` подставлено "ООО Ромашка"

#### Scenario: Встроенные курсы в письме
- **WHEN** рассылка "Новые курсы" содержит курс "Python для анализа данных"
- **AND** система отправляет письмо организации "ООО Ромашка"
- **THEN** в письмо включено описание курса и прикреплён PDF с этим курсом

### Requirement: Отправка через outbox
The system SHALL store an outbound email record in the database for each
recipient, and a separate worker command SHALL perform the actual sending
through SMTP. Email sending SHALL NOT depend on message queues or external task
brokers.

#### Scenario: Запись письма в базе данных
- **WHEN** рассылка "Новые курсы" запущена по организации "ООО Ромашка"
- **THEN** в базе данных создаётся запись «письмо к отправке» для организации "ООО Ромашка" со статусом pending

#### Scenario: Отдельная команда отправляет письма
- **WHEN** в базе данных есть записи «письмо к отправке» для рассылки "Новые курсы"
- **AND** запускается команда отправки писем
- **THEN** письма отправляются через SMTP-сервер получателям

### Requirement: Статусы писем и ход рассылки
The system SHALL track each email status: pending, sent, delivered, bounced,
opened; the manager SHALL see the status of each email and the overall progress
of the campaign.

#### Scenario: Доставлено
- **WHEN** письмо рассылки передано на SMTP получателю
- **AND** SMTP подтверждает доставку
- **THEN** статус письма становится delivered

#### Scenario: Технический отказ
- **WHEN** письмо рассылки отправлено контакту с недостоверным адресом
- **AND** SMTP возвращает ошибку доставки
- **THEN** статус письма становится bounced

#### Scenario: Письмо прочитано
- **WHEN** получатель открывает письмо рассылки
- **AND** система получает запрос на tracking-pixel письма
- **THEN** статус письма становится opened

#### Scenario: Прогресс рассылки
- **WHEN** в рассылке "Новые курсы" 10 писем и 7 из них отправлено
- **AND** менеджер открывает карточку рассылки
- **THEN** он видит статус каждого письма и прогресс "7 из 10"

#### Scenario: Менеджер видит статусы только писем своего доступа
- **WHEN** в рассылке есть письма организации, отсутствующей в области доступа менеджера
- **AND** менеджер открывает карточку рассылки
- **THEN** он не видит письма и статусы недоступной организации
- **AND** прогресс рассылки рассчитывается по видимым менеджеру письмам

### Requirement: Обработка отписки
The system SHALL process unsubscribe requests and SHALL exclude unsubscribed
contacts from later campaigns.

#### Scenario: Отписка контакта от рассылок
- **WHEN** контакт "Иван Петров" получил рассылку "Новые курсы"
- **AND** он переходит по ссылке отписки
- **THEN** контакт "Иван Петров" исключается из последующих рассылок