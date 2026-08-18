## Why

Стек проекта заявляет Webpack Encore (см. `project.md` — Technology Stack), а
изменение `web-interface-design` требует SCSS-токенов, компонентных стилей
и самохостед-шрифтов, собираемых через Encore. В репозитории при этом нет
`package.json`, `webpack.config.js`, `assets/` и `public/build` — сборки
фронтенд-активов не существует. Изменение закрывает этот инфраструктурный
пробел, чтобы дизайн-систему было куда компилировать.

## What Changes

- Создаётся `package.json` со скриптами `dev`, `watch`, `build` и
  зависимостями: `@symfony/webpack-encore`, `sass-loader`, `sass`,
  `webpack`, `webpack-cli`, `webpack-dev-server`, `@fontsource/roboto`,
  `@fontsource/roboto-condensed`.
- Создаётся `webpack.config.js` (Encore): точка входа `./assets/app.js`,
  SCSS через sass-loader, вывод в `public/build` с публичным путём
  `/build`.
- Создаётся каркас активов `assets/app.js` (точка входа, импорт стилей) и
  `assets/scss/app.scss`. Файлы дизайн-системы (`_tokens.scss`, базовые и
  компонентные стили) сюда **не** добавляются — их создаёт
  `web-interface-design`.
- В `base.html.twig` подключаются скомпилированные стили и скрипты через
  `encore_entry_link_tags()` / `encore_entry_script_tags()`.
- Обеспечивается Node.js в окружении разработки (контейнер/хост) для
  `npm ci` и сборки.
- Скрипты/стейлы подключаются корректно и в development, и в production.

Изменение относится только к инфраструктуре сборки: поведение возможностей
CRM не меняется, поэтому в `.openspec.yaml` включён `skip_specs: true` —
spec-дельты не создаются (как в `initial-docker-setup`).

## Capabilities

### New Capabilities
Нет — чисто инструментальное изменение (tooling); `skip_specs: true`.

### Modified Capabilities
Нет.

## Impact

- `package.json`, `package-lock.json`, `webpack.config.js`, `assets/`,
  `public/build/` (закоммичивается ли `build/` — решает оутпут-настройка;
  по умолчанию артефакт сборки не коммитится).
- `templates/base.html.twig` — только подключение бандлов активов,
  содержимое страниц не меняется.
- Окружение: Node.js в php-контейнере (compose) или на хосте разработчика.
- nginx отдаёт `public/build/*` (в `public/` — уже отдаётся).
- Зависимость: `web-interface-design` (токены, SCSS, шрифты) применяется
  поверх этой сборки.