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
Используется `@symfony/webpack-encore` (v7, ESM — `package.json` со
`"type": "module"`, конфиг `webpack.config.js`) — штатный инструмент стека
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
cyrillic/latin, начертания 400/700) импортируются в `assets/app.js`
(wepback-css, а не через sass-loader — надёжнее для css-файлов пакета),
`font-display: swap`, font-face генерирует пакет. Альтернатива —
ручные `@font-face` с локальными woff2: отклонена, @fontsource даёт
готовые подмножества и переменные начертания.

### D5. Подключение через Twig-хелперы
В `templates/base.html.twig` — `encore_entry_link_tags('app')` в
`stylesheets` и `encore_entry_script_tags('app')` в конце `body`. Хелперы
отдают `<link>`/`<script>` с абсолютными путями `/build/...` и корректно
работают и в production (entrypoints.json), и в dev (`encore dev-server`).

### D6. Контейнер несёт ответственность за сборку
Сборка живёт в php-контейнере: Node.js 24 (LTS, совместим с Encore 7)
добавляется в образ multistage-copy из `node:24-bookworm-slim` (та же
база — debian bookworm, бинарно совместимо, php:8.5-fpm-bookworm), а
entrypoint при старте выполняет `npm ci` (если `node_modules` ещё нет)
и `npm run build` — по аналогии с composer install и миграциями. Хост
от сборки освобождён; версии фиксируются в `package-lock.json` и в теге
node-образа. Dev-режим (`encore dev-server`/`watch`, hot reload)
остаётся опцией хоста разработчика. Интерактивный вход — `make exec`
(`docker compose exec --user app php bash`, пользователь `app`, uid/gid
1000 как на хосте); после сборки entrypoint приводит права
`node_modules`/`public/build` к `app` (`chown -R app:app`), чтобы
артефакты были записываемы из интерактивной сессии и принадлежали
хост-пользователю.

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
|  Сборка (start, php-контейнер): npm ci && npm run build         |
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
- [Сборка на каждом старте контейнера замедляет подъём] → Encore
  компилирует инкрементально (секунды); на хосте сборка не требуется
  вообще — это и есть мотивация решения D6.
- [base.html.twig без сборки] → без `npm run build` страница возвращает
  500 (бандл в `strict_mode` по умолчанию требует `entrypoints.json`; факт
  проверен переносом `public/build`). Мягкая деградация «без стилей»
  намеренно не поддержана — порядок «сборка → запуск» гарантирован
  entrypoint (D6), а тихий пропуск активов маскирует ошибку деплоя.
- [Конфликт с web-interface-design в assets/] → граница по файлам (D7):
  каркас здесь, дизайн-файлы там.

## Migration Plan

1. `package.json` (в т.ч. `"type": "module"`), зависимости, `webpack.config.js`.
2. Каркас `assets/` (app.js, app.scss), Node 24 в php-образ (D6).
3. Подключение в `base.html.twig` (D5).
4. Верификация dev + production сборок.
5. Откат: git-revert; без бандла страницы работают без стилей (функциональность CRM не затронута).

## Open Questions

Отсутствуют.