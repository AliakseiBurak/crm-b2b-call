## MODIFIED Requirements

### Requirement: Палитра интерфейса и семантика цветов
The system SHALL render the interface in a light theme using the softened
palette with fixed semantic roles: orange `#d66a2b` is the action color
(primary action buttons, callable phones, CTA accents) with the gradient
`#d66a2b → #e09a68` for primary action buttons; green `#5e9e47` is the
success and status color (successful results, active states) and SHALL NOT
be used for headings; blue `#20799e` is the navigation and information
color (headings, card titles, links) with the gradient
`#20799e → #419cbe` for blue section bands; the green gradient
`#55964a → #478540` SHALL be used for section bands, the statistics band,
footer and price badges. Body text SHALL be gray `#5a5a5a`, secondary text
`#b5b5b5`, table stripes `#e3f1f6` and `#e5f5fb`, card section background
`#f5f6f6`, discount and error red gradient `#d9554f → #c13f3a`. The design
SHALL NOT use dark theme variants and SHALL NOT use box shadows anywhere.

#### Scenario: Оранжевый градиент на первичной кнопке
- **WHEN** пользователь открывает welcome page
- **THEN** первичная кнопка призыва к действию имеет вычисленный фон
  линейного градиента от `#d66a2b` к `#e09a68`
- **AND** текст кнопки белый

#### Scenario: Семантика зелёного — только статусы
- **WHEN** результат звонка «Договорились» отображается в списке звонков
- **THEN** значение статуса окрашено в зелёный `#5e9e47`
- **AND** заголовки секций и карточек зелёным не окрашиваются

#### Scenario: Отсутствие теней
- **WHEN** пользователь просматривает любую страницу интерфейса
- **THEN** ни один элемент не имеет CSS box-shadow

### Requirement: Типографика рабочего инструмента
The system SHALL use Roboto for body text at 15px with color `#5a5a5a` and
Roboto Condensed for headings, table text and emphasized figures. Headings
SHALL render in Roboto Condensed bold: `h1` 32px, `h2` 28px (24px below
576px viewport) with a blue left border of `0.19em` in color `#20799e` and
left padding `0.3em`, `h3` 22px (20px below 576px). Table text SHALL
render at 16px Roboto Condensed. Card titles SHALL render at 20px Roboto
Condensed bold. Dashboard statistics SHALL use figures of 56px bold.
Heading color modifiers SHALL be available: green `#5e9e47`, orange
`#d66a2b`, blue `#20799e`.

#### Scenario: Заголовок секции с синей полосой
- **WHEN** на странице отображается заголовок `h2` секции
- **THEN** он выполнен шрифтом Roboto Condensed 28px bold
- **AND** слева от текста — синяя полоса `#20799e` шириной `0.19em`
- **AND** текст заголовка окрашен в `#20799e`

#### Scenario: Крупные числа статистики
- **WHEN** на дашборде отображается блок статистики (например, «Обзвонено сегодня», «Ждут обзвона»)
- **THEN** число набрано 56px bold белым цветом
- **AND** подпись к числу набрана 20px bold белым цветом

### Requirement: Адаптивная компоновка
The system SHALL lay out content in a centered container with maximum
width 1360px on viewports of 1400px and wider, and SHALL support the
breakpoints 576px, 768px, 992px, 1200px and 1400px. Full-width sections
SHALL be rendered as colored bands spanning the entire viewport, while
their content stays inside the container. Working sections (lists, tables,
forms) SHALL sit on a white background; card sections SHALL sit on the
light background `#f5f6f6`; colored bands SHALL be reserved for the
welcome page hero, the dashboard statistics band and the
footer.

#### Scenario: Ширина контейнера на широком экране
- **WHEN** пользователь открывает страницу на экране шириной 1440px
- **THEN** основное содержимое страницы занимает не более 1360px по центру

#### Scenario: Рабочая секция на белом фоне
- **WHEN** пользователь открывает список контактов
- **THEN** список отображается на белом фоне
- **AND** ни одна рабочая секция списка не имеет градиентной заливки

### Requirement: Welcome page
The system SHALL render the welcome page with a hero banner: background
image, primary headline, an uppercase accent slogan on a colored band, and
a call-to-action button «Начать работу» that opens the dashboard.

#### Scenario: Герой-баннер welcome page
- **WHEN** пользователь открывает welcome page
- **THEN** в верхней части отображается баннер с фоновым изображением
- **AND** под заголовком отображается слоган прописными буквами на цветной ленте
- **AND** ниже отображается кнопка «Начать работу» с оранжевым градиентом

### Requirement: Кнопки
The system SHALL render primary action buttons as pills with border-radius
30px: filled with the orange gradient `#d66a2b → #e09a68`, white bold text
at 18px, insensitive to hover. Secondary buttons SHALL be white pills with
orange text `#d66a2b`, a 2px orange border and a hover state that inverts
them (orange background, white text). The «all items» link SHALL be bold
18px with a trailing `>>>`. Clickable `tel:` phone links SHALL work when
the interface is opened in mobile browsers and in-app WebViews by
triggering the system dialer.

#### Scenario: Кнопка «Позвонить» на карточке контакта
- **WHEN** пользователь наводит курсор на кнопку «Позвонить» на карточке контакта
- **THEN** кнопка остаётся оранжевым градиентом с белым текстом

### Requirement: Формы
The system SHALL render form inputs without borders or background, with a
bottom underline of 2px and transparent background; the underline color
SHALL be `#d66a2b` on light backgrounds and white on colored bands, and
the underline SHALL thicken to 4px while the field is focused. Input text
and placeholders SHALL use the underline color variant, font Roboto
Condensed 16px, full field width. Submit buttons SHALL be pills with
border-radius 30px: primary — orange gradient with white text; secondary —
light gray background `#e8e8e7` with bold text. Forms SHALL render inline
on their page and SHALL NOT open in a modal window.

#### Scenario: Поле формы с нижним подчёркиванием
- **WHEN** пользователь открывает форму на светлом фоне
- **THEN** каждое текстовое поле отображается без рамки и фона
- **AND** под полем — линия толщиной 2px цвета `#d66a2b`
- **AND** текст и плейсхолдер поля окрашены в `#d66a2b`

#### Scenario: Фокус поля утолщает линию
- **WHEN** пользователь устанавливает курсор в поле формы
- **THEN** линия под полем утолщается с 2px до 4px
- **AND** цвет линии не меняется

### Requirement: Шапка и подвал
The system SHALL render a white header: the logo «B2B Call CRM» on the left
as a link to the home page, navigation links in the top row, and — for
authenticated users — action buttons «Создать организацию» and «Создать
контакт» on the right. Guest navigation SHALL contain the «Войти» link and
authenticated navigation SHALL contain the «Панель» and «Выйти» links. The
footer SHALL render on the green gradient `#55964a → #478540` with white
text: company block, menu links and contact information.

#### Scenario: Навигация в шапке
- **WHEN** пользователь открывает страницу с шапкой
- **THEN** логотип «B2B Call CRM» находится слева и ведёт на главную страницу
- **AND** пункты навигации отображаются в верхней строке на белом фоне
- **AND** для вошедшего пользователя справа отображается кнопка «Создать организацию»
- **AND** для вошедшего пользователя справа отображается кнопка «Создать контакт»

#### Scenario: Подвал на зелёном градиенте
- **WHEN** пользователь прокручивает страницу до подвала
- **THEN** подвал отображается на зелёном градиенте `#55964a → #478540`
- **AND** текст подвала белый

## REMOVED Requirements

### Requirement: Модальные окна
**Reason**: Модальное окно «Заказать звонок» с формой заявки удалено; формы
отображаются встроенно на своих страницах, модальный компонент в интерфейсе
больше не используется.
**Migration**: Кнопка «Заказать звонок» и CTA-пара «Позвонить»/«Создать»
удалены из шапки и welcome page; при необходимости форма заявки размещается
встроенно в страницу.