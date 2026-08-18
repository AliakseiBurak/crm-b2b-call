## Context

Symfony 7.x + Twig приложение без фронтенд-сборки: `package.json`,
`webpack.config.js`, `assets/` и `public/build` отсутствуют (см.
`proposal.md` — Why). Сборка нужна изменению `web-interface-design`
(SCSS-токены, компонентные стили, шрифты @fontsource). Локальное
окружение — compose (php, mysql, nginx, mailpit, e2e), сайт на
`https://b2b-crm.local` (изменение `initial-docker-setup`).

## Goals / Non-Goals

**Goals:**
- Воспроизводимая сборка CSS/JS из SCSS/JS исходников в `public/build`.
- Одинаково корректное подключение активов в development и production.
- Самохостед-шрифты (Roboto, Roboto Condensed) без внешних CDN.
- Минимальный каркас, не предвосхищающий файлы дизайн-системы
  (`web-interface-design` создаёт `_tokens.scss`, базовые и компонентные
  стили сам).

**Non-Goals:**
- Не создаются стили дизайн-системы (их объём — `web-interface-design`).
- Не меняются страницы и их разметка (только подключение бандлов).
- Без изменений БД, API, сущностей, правил доступа.

## Decisions

### D1. Webpack Encore как обёртка webpack
Используется `@symfony/webpack-encore` (v2) — штатный инструмент стека
(заявлен в `project.md`), дающий предсказуемую конфигурацию: хэши
имён, entrypoints-манифест, единый runtime-чанк. Альтернатива — голый
webpack/vite: отклонена, Encore декларирован стеком и генерирует
`entrypoints.json`, который читает Twig-хелпер.

### D2. Одна точка входа `assets/app.js`, SCSS через sass-loader
`assets/app.js` импортирует `assets/scss/app.scss` (sass-loader +
mini-css-extract в проде, HMR в dev). Все страницы используют один бандл —
на текущем объёме приложения раздельные чанки избыточны (splitChunks
включается при необходимости).

### D3. Вывод в `public/build` с публичным путём `/build`
`Encore.setOutputPath('public/build')`, `setPublicPath('/build')`; nginx
уже отдаёт `public/`, поэтому CSS/JS/l10n отдаются без дополнительных
правил. `public/build` не коммитится (артефакт сборки).

### D4. Шрифты через @fontsource
`@fontsource/roboto` и `@fontsource/roboto-condensed` (woff2, подмножества
cyrillic/latin), импортируются в SCSS, `font-display: swap`. Альтернатива —
ручные `@font-face` с локальными woff2: отклонена, @fontsource даёт
готовые подмножества и переменные начертания.

### D5. Подключение через Twig-хелперы
В `templates/base.html.twig` — `encore_entry_link_tags('app')` в
`stylesheets` и `encore_entry_script_tags('app')` в конце `body`. Хелперы
отдают `<link>`/`<script>` с абсолютными путями `/build/...` и корректно
работают и в production (entrypoints.json), и в dev (`encore dev-server`).

### D6. Node в окружении разработки
`npm ci` и сборка выполняются в php-контейнере compose (Node добавляется
в образ) либо на хосте с Node LTS. Версии фиксируются в
`package-lock.json`; сборка при каждом старте окружения — `npm run build`
в entrypoint/команде запуска.

### D7. Разделение каркаса и дизайн-системы
Каркас (`app.js`, пустой `app.scss`) создаётся здесь; `_tokens.scss`,
`base.scss`, `components/*.scss` — строго в `web-interface-design`, чтобы
не было конфликтов параллельных изменений.

## Architecture

Container-level (ASCII, lightweight C4; предположения: проектируемая
сборка, формат ASCII):

```text
+----------------------------------------------------------------+
|                        Web App (Symfony)                        |
|                                                                |
|  +-------------+       +----------------+      +------------+  |
|  | assets/     |       | webpack Encore |      | base.html  |  |
|  |  app.js     | ----> | (webpack.config| ---> | .twig      |  |
|  |  scss/      |  sass |  .js + sass)   |      | encore_entry| |
|  |  app.scss   |       +----------------+      | _link_tags/ | |
|  +-------------+              |                | _script_tags| |
|                               v                +------------+  |
|                     +-----------------+             ^          |
|                     | public/build/   |-------------+ (HTTP /build/*)
|                     | app.js app.css  |    nginx отдаёт public/
|                     | entrypoints.json|                     |
|                     +-----------------+                     |
|                                                                |
|  Шрифты: @fontsource/roboto{, -condensed} пакетам = woff2       |
|  (cyrillic+latin) в SCSS, font-display: swap                   |
|  Сборка: npm ci && npm run build (php-контейнер или хост)       |
+----------------------------------------------------------------+
```

Контейнерная диаграмма: исходники активов компилируются Encore в
`public/build`; nginx отдаёт `public/`; Twig-хелперы подключают
`/build/*` по entrypoints-манифесту; шрифты встраиваются в сборку.

## Risks / Trade-offs

- [Версии Node/Encore в контейнере] → фиксированные версии в
  package-lock, `npm ci`; Node LTS в образе.
- [Кэш Encore при смене конфигурации] → `npm run build -- --mode=production`
  чистит бандл; при подозрении на устаревший `public/build` — удаление
  каталога перед сборкой.
- [Пути активов при другом публичном пути] → `/build` соответствует
  корню сайта; при смене хоста/подпапки правится `setPublicPath` + .env.
- [base.html.twig без сборки] → без `npm run build` страница отдаётся
  без стилей, но не падает (хелперы рендерят пустоту) — порядок
  «сборка → запуск» фиксируется в tasks.
- [Конфликт с web-interface-design в assets/] → граница по файлам (D7):
  каркас здесь, дизайн-файлы там.

## Migration Plan

1. `package.json`, зависимости, `webpack.config.js`.
2. Каркас `assets/` (app.js, app.scss), Node в окружении.
3. Подключение в `base.html.twig` (D5).
4. Верификация dev + production сборок.
5. Откат: git-revert; без бандла страницы работают без стилей (функциональность CRM не затронута).

## Open Questions

Отсутствуют.