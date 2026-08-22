## ADDED Requirements

### Requirement: Таблица организаций на панели
The system SHALL render on the dashboard, below the statistics blocks, a
table of organizations with columns for the organization name, industry,
date of the last completed call and date of the next scheduled call. The
last call date SHALL be derived from the latest `Call.made_at` of the
organization, the next call date SHALL be derived from the nearest future
`Call.scheduled_at`. Organizations SHALL be listed within the user's access
scope, in the sort order selected by the user.

#### Scenario: Список организаций с датами звоноков
- **WHEN** в области доступа пользователя существуют организации с завершёнными и запланированными звонками
- **AND** пользователь открывает дашборд
- **THEN** в таблице отображаются названия организаций
- **AND** для каждой организации отображаются сфера деятельности, дата последнего завершённого звонка и дата ближайшего запланированного звонка

#### Scenario: Организация без звонков
- **WHEN** в области доступа пользователя существует организация, у которой нет ни одного звонка
- **AND** пользователь открывает дашборд
- **THEN** в строке организации вместо дат звоноков отображаются заглушки «—»

### Requirement: Контакты организации на панели
The system SHALL let the user expand an organization row on the dashboard
to reveal the contact cards of that organization. The system SHALL render
contact cards with the contact name, the phone as a clickable `tel:` link
and the email as a clickable `mailto:` link. A card SHALL also render the
non-empty `Contact.notes` of the contact under a «Заметка» label. The
cards SHALL NOT contain call or other action buttons.

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

### Requirement: Все звонки организации
The system SHALL render in the expanded section of an organization the note
of its last call: the note SHALL be taken from the non-empty `Call.notes`
of the latest call of the organization, where the latest call SHALL be
resolved by the maximum effective date `COALESCE(made_at, scheduled_at)`;
when several calls share the same maximum effective date, the latest call
SHALL be the one with the greatest `Call.id`. The note SHALL be displayed
together with the contact of that call (contact
name, phone as a `tel:` link and email as a `mailto:` link). The system
SHALL provide a «Все звонки» expandable list that reveals all calls
of the organization with the date of each call, newest first. A row of a
call with non-empty `Call.notes` SHALL show the date, the note text and the
contact of that call (contact name, phone as a `tel:` link and email as a
`mailto:` link). A row of a call without notes SHALL show the date and,
when the call has a contact, that contact (contact name, phone as a `tel:`
link and email as a `mailto:` link) — no note text. Expansion of the list
SHALL NOT re-fetch data or reload the page.

#### Scenario: Заметка последнего звонка
- **WHEN** у организации есть звонки с заметками
- **AND** пользователь раскрывает строку организации
- **THEN** в раскрытой секции отображается заметка последнего по времени звонка
- **AND** рядом с заметкой отображается контакт звонка (имя, телефон как ссылка для звонка, email как ссылка для почты)
- **AND** звонки без заметок не участвуют в выборе последней заметки

#### Scenario: Все звонки
- **WHEN** пользователь кликает по «Все звонки» в раскрытой секции организации
- **THEN** раскрывается список всех звонков организации с датой каждого
- **AND** каждая строка списка с непустой заметкой содержит дату, текст заметки и контакт этого звонка (имя, телефон как ссылка для звонка, email как ссылка для почты)
- **AND** строка списка звонка без заметки содержит дату и контакт звонка, если он есть (имя, телефон как ссылка для звонка, email как ссылка для почты) — без текста заметки
- **AND** строки отсортированы от новых к старым
- **AND** повторный клик по «Все звонки» скрывает список

#### Scenario: У организации нет заметок
- **WHEN** все звонки организации не имеют заметок
- **AND** пользователь раскрывает строку организации
- **THEN** блок «Последний звонок» не показывается
- **AND** в списке «Все звонки» отображаются строки звонков с датой и контактом, если он есть — без текста заметки
- **WHEN** у организации совсем нет звонков
- **THEN** секция звонков не показывается вовсе
- **AND** раскрытая секция показывает только контакты организации

### Requirement: Поиск по организациям и контактам
The system SHALL provide a search field above the organization table that
filters the table by organization name and by contact data (contact name,
phone, email) of the organization's contacts. The search SHALL be
case-insensitive and applied immediately as the user types.

#### Scenario: Поиск по названию организации
- **WHEN** пользователь вводит в поле поиска текст, совпадающий с названием одной из организаций
- **THEN** в таблице остаются только организации, в названии которых встречается введённый текст
- **AND** остальные организации скрыты

#### Scenario: Поиск по контакту
- **WHEN** пользователь вводит в поле поиска имя, телефон или email контакта
- **THEN** в таблице остаются только организации, у которых есть контакт с совпадением
- **AND** контакты совпавших организаций остаются доступными для раскрытия

#### Scenario: Поиск без совпадений
- **WHEN** пользователь вводит в поле поиска текст, не встречающийся ни в одной организации или контакте
- **THEN** таблица пуста
- **AND** отображается сообщение об отсутствии результатов

#### Scenario: Очистка поиска через крестик в поле
- **WHEN** в поле поиска есть текст (таблица отфильтрована)
- **AND** пользователь нажимает нативный крестик очистки поля поиска (`type="search"`)
- **THEN** поле очищается и таблица снова показывает все организации области доступа
- **AND** в строке URL больше нет параметра `q`

### Requirement: Сортировка таблицы организаций
The system SHALL let the user sort the organization table by organization
name, industry, last call date and next call date. By default, without a
sort parameter, the table SHALL be sorted by organization name in ascending
order. The sortable column headers SHALL render as plain clickable headers
with the currently applied sort direction (and column) marked visually.
Sorting SHALL be toggled by clicking the corresponding header; the first
click applies ascending order, the second click descending order, both
within the enabled column sort directions.

#### Scenario: Сортировка по названию
- **WHEN** пользователь открывает дашборд без параметров сортировки
- **THEN** организации отсортированы по названию (А–Я)
- **WHEN** пользователь кликает по заголовку «Название» таблицы организаций
- **THEN** организации сортируются по названию (А–Я, затем Я–А при повторном клике)
- **AND** направление сортировки отражается в заголовке

#### Scenario: Сортировка по дате следующего звонка
- **WHEN** пользователь кликает по заголовку «Следующий звонок»
- **THEN** организации сортируются по дате следующего звонка от ближайшей к самой поздней
- **AND** организации без запланированных звонков располагаются в конце списка

### Requirement: Область доступа списка организаций
The system SHALL render the organization table on the dashboard within the
user's access scope: an administrator SHALL see all organizations, while a
manager SHALL see only the organizations of their own group
(`user-<id>-group`) and of the custom groups assigned to them (`adr/0007`).
The dashboard SHALL NOT render organizations outside the user's access
scope.

#### Scenario: Менеджер видит только свои организации
- **WHEN** в системе существуют организации в области доступа менеджера и вне неё
- **AND** менеджер открывает дашборд
- **THEN** в таблице отображаются только организации области доступа менеджера
- **AND** организации вне области доступа не отображаются

#### Scenario: Администратор видит все организации
- **WHEN** администратор открывает дашборд
- **THEN** в таблице отображаются все организации системы

### Requirement: Кнопки действий на панели
The system SHALL render action buttons on the dashboard: an «Изменить»
button per organization row, an «Добавить звонок» button in the expanded
section of an organization, an «Изменить» button per call row of the
«Все звонки» list, an «Добавить контакт» button in the contacts section
of an expanded organization, and an «Изменить» button on each contact
card. The buttons SHALL link to the corresponding management routes.
Those routes SHALL NOT be implemented yet: the requests to them
SHALL be answered with the standard 404 not-found response until the
separate management change is implemented.

#### Scenario: Изменение организации
- **WHEN** пользователь кликает по кнопке «Изменить» в строке организации
- **THEN** браузер переходит по ссылке на страницу редактирования этой организации
- **AND** сервер отвечает 404, так как страница редактирования ещё не реализована

#### Scenario: Добавление звонка
- **WHEN** пользователь кликает по кнопке «Добавить звонок» в раскрытой секции организации, в том числе когда звонков нет
- **THEN** браузер переходит по ссылке на страницу создания звонка этой организации
- **AND** сервер отвечает 404, так как страница создания звонка ещё не реализована

#### Scenario: Изменение звонка
- **WHEN** пользователь кликает по кнопке «Изменить» в строке звонка списка «Все звонки»
- **THEN** браузер переходит по ссылке на страницу редактирования этого звонка
- **AND** сервер отвечает 404, так как страница редактирования звонка ещё не реализована

#### Scenario: Добавление контакта
- **WHEN** пользователь кликает по кнопке «Добавить контакт» в раскрытых контактах организации, в том числе когда контактов нет
- **THEN** браузер переходит по ссылке на страницу создания контакта этой организации
- **AND** сервер отвечает 404, так как страница создания контакта ещё не реализована

#### Scenario: Изменение контакта
- **WHEN** пользователь кликает по кнопке «Изменить» на карточке контакта
- **THEN** браузер переходит по ссылке на страницу редактирования этого контакта
- **AND** сервер отвечает 404, так как страница редактирования контакта ещё не реализована
