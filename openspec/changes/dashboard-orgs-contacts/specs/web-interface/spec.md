## MODIFIED Requirements

### Requirement: Таблицы
The system SHALL render data tables (organizations, contacts, calls) with
width 100%, text at 16px Roboto Condensed, zebra striping in `#e3f1f6` for
odd rows and `#e5f5fb` for even rows, without cell borders. Hovering a data
row SHALL highlight it with a shade of the same blue family distinct from
both zebra stripes (`#cfe6f2`), instead of removing the row color. Table
headers SHALL be bold 16px in `#5a5a5a` with bottom padding, and the table
SHALL have a bottom margin of 3rem. The contact name column SHALL be bold,
and the phone column SHALL be rendered in orange `#d66a2b` as a clickable
link.

#### Scenario: Зебра-таблица списка контактов
- **WHEN** пользователь открывает список контактов
- **THEN** строки таблицы окрашены попеременно в `#e3f1f6` и `#e5f5fb`
- **AND** между строками и ячейками нет линий рамок
- **AND** текст ячеек выполнен шрифтом Roboto Condensed 16px

#### Scenario: Наведение подсвечивает строку
- **WHEN** пользователь наводит курсор на строку данных таблицы
- **THEN** строка подсвечивается оттенком `#cfe6f2` того же голубого семейства, что и полосы зебры
- **AND** базовый цвет строки не исчезает и заменяется на оттенок семейства (а не на прозрачный)
- **AND** аккордеонная строка раскрытия (вторая строка организации) при наведении не подсвечивается

#### Scenario: Выделение ключевых данных в таблице
- **WHEN** в таблице отображаются контакты
- **THEN** имя контакта в первом столбце — жирное
- **AND** телефон — оранжевая `#d66a2b` кликабельная ссылка
- **AND** остальные столбцы — обычный серый текст `#5a5a5a`

### Requirement: Кнопки
The system SHALL render primary action buttons as pills with border-radius
30px: filled with the orange gradient `#d66a2b → #e09a68`, white bold text
at 18px, insensitive to hover. Secondary buttons SHALL be white pills with
orange text `#d66a2b`, a 2px orange border and a hover state that inverts
them (orange background, white text). The «all items» link SHALL be bold
18px with a trailing `>>>`. Clickable `tel:` phone links SHALL work when
the interface is opened in mobile browsers and in-app WebViews by
triggering the system dialer. Contact email SHALL be a clickable `mailto:`
link that opens the system mail client. There SHALL be NO dedicated «Позвонить» button — a phone call SHALL be initiated by clicking the phone number link itself.

#### Scenario: Звонок кликом по номеру телефона
- **WHEN** пользователь нажимает на номер телефона на карточке контакта
- **THEN** открывается системный звонильщик с этим номером
- **AND** на карточке нет отдельной кнопки «Позвонить»

#### Scenario: Нажатие на email открывает почтовый клиент
- **WHEN** пользователь нажимает на email на карточке контакта
- **THEN** открывается системный почтовый клиент с адресом в поле получателя

#### Scenario: Кнопка «Позвонить» на карточке контакта
- **WHEN** пользователь наводит курсор на карточку контакта
- **THEN** на карточке нет кнопки «Позвонить» — звонок инициируется кликом по номеру телефона
- **AND** первичные кнопки-пилюли остаются оранжевым градиентом с белым текстом при наведении

### Requirement: Контактные карточки
The system SHALL render contact cards on the `#f5f6f6` section background
as white cards without borders, rounding or shadows, stretching to the
column width with a minimum width of 300px and content-defined height.
Each card SHALL highlight only the essential data: the contact name in
Roboto Condensed 20px bold in blue `#20799e` with a left border of 3px,
and the phone number in bold orange `#d66a2b` as a clickable `tel:` link.
Cards SHALL NOT contain icons or images. Secondary data (position, email,
notes) SHALL be rendered in gray `#5a5a5a` at 15px; the email SHALL be a
clickable `mailto:` link. The card SHALL NOT contain a call or
«Позвонить» action button — phone and email are opened by clicking the data
itself; a placeholder «Изменить» action button MAY be placed within the
card boundaries (bottom-left) for the dashboard contacts section.
The card MAY be used via Twig embed with a `card_footer` block.

#### Scenario: Карточка контакта выделяет имя, телефон и email
- **WHEN** пользователь открывает карточку контакта
- **THEN** карточка белая, без рамки, без скругления и без тени
- **AND** имя контакта — синее `#20799e` bold с синей левой полосой 3px
- **AND** телефон — оранжевый `#d66a2b` bold и является ссылкой для звонка
- **AND** вторичные данные (должность, email) — серые `#5a5a5a`
- **AND** email является ссылкой, открывающей почтовый клиент
- **AND** внизу карточки нет кнопки «Позвонить» и других кнопок звонка; кнопка-заглушка «Изменить» допустима в границах карточки (слева внизу)

#### Scenario: Карточка контакта выделяет имя и телефон
- **WHEN** пользователь открывает карточку контакта
- **THEN** карточка белая, без рамки, без скругления и без тени
- **AND** имя контакта — синее `#20799e` bold с синей левой полосой 3px
- **AND** телефон — оранжевый `#d66a2b` bold и является ссылкой для звонка
- **AND** вторичные данные (должность, email, заметка) — серые `#5a5a5a`
- **AND** электронная почта является ссылкой, открывающей почтовый клиент
- **AND** внизу карточки нет кнопки «Позвонить» и других кнопок звонка; звонок инициируется кликом по номеру телефона; кнопка-заглушка «Изменить» допустима в границах карточки (слева внизу)

#### Scenario: Отсутствие иконок в карточке
- **WHEN** пользователь просматривает карточку контакта
- **THEN** карточка не содержит иконок и изображений
- **AND** акцент сделан только на имени, телефоне и email

#### Scenario: Бейдж цены только на карточках предложений
- **WHEN** карточка контакта отображается рядом с карточкой предложения курса
- **THEN** контактная карточка не содержит бейдж цены