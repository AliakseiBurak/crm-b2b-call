## 1. Бутстрап сборки

- [x] 1.1 Создать `package.json` (npm init -y) с скриптами `dev` (`encore dev-server`), `watch` (`encore dev --watch`), `build` (`encore production`)
- [x] 1.2 Установить зависимости: `@symfony/webpack-encore`, `sass-loader`, `sass`, `webpack`, `webpack-cli`, `webpack-dev-server`, `@fontsource/roboto`, `@fontsource/roboto-condensed`; зафиксировать `package-lock.json`
- [x] 1.3 Создать `webpack.config.js` (Encore 7 — ESM, `"type": "module"` в `package.json`): `setOutputPath('public/build')`, `setPublicPath('/build')`, `addEntry('app', './assets/app.js')`, `enableSassLoader()`, `splitEntryChunks()`, `enableSingleRuntimeChunk()`, `cleanupOutputBeforeBuild()`, `enableSourceMaps(!isProduction)`, `enableVersioning()`, production/development режимы из Encore
- [x] 1.4 Обеспечить Node.js в php-контейнере: multistage `COPY --from=node:24-bookworm-slim` в `Dockerfile` (node/npm/npx, libstdc++), проверка `node --version && npm --version` при сборке образа

## 2. Каркас активов и подключение

- [x] 2.1 Создать `assets/app.js` — точка входа, импорт шрифтов `@fontsource` (cyrillic/latin, 400/700) и `./scss/app.scss`
- [x] 2.2 Создать `assets/scss/app.scss` — пустая точка входа стилей (без файлов дизайн-системы; их добавляет `web-interface-design`)
- [x] 2.3 В `templates/base.html.twig` добавить `{{ encore_entry_link_tags('app') }}` (секция stylesheets) и `{{ encore_entry_script_tags('app') }}` (перед `</body>`); добавлена PHP-зависимость `symfony/webpack-encore-bundle ^2.4` в `composer.json` + `config/packages/webpack_encore.yaml`

## 3. Сборка и режимы

- [x] 3.1 Выполнить `npm run build`; убедиться, что `public/build/` содержит `entrypoints.json`, `app.js`, `app.css` (с хэшами)
- [x] 3.2 Проверить страницу `https://b2b-crm.local`: `<link rel="stylesheet">` (app.css) и `<script>` (runtime.js, app.js) на `/build/*` — все три актива возвращают 200
- [x] 3.3 Проверить dev-режим: `encore dev-server` компилирует, `watch` реагирует на изменение `assets/scss/app.scss`
- [x] 3.4 Проверить поведение без сборки (перенос `public/build` + curl): страница возвращает 500 — бандл работает в `strict_mode` по умолчанию и требует `entrypoints.json`; после restore — 200. Откат-путь «страница без стилей» НЕ предусмотрен: порядок `npm ci` → `npm run build` гарантируется entrypoint (D6), деградация намеренно не поддерживается
- [x] 3.5 Убедиться, что `public/build/` не попадает в git (добавлены `/public/build/` и `/node_modules/` в `.gitignore`)
- [x] 3.6 Проверить сборку из контейнера: после `docker compose build php` и пересоздания контейнера entrypoint выполнил `npm ci` + `npm run build` (entrypoints.json собран в контейнере, `webpack compiled successfully` в логах); `node_modules/` и `public/build` принадлежат пользователю `app` (uid 1000); страница отдаёт `/build/*` (200); `make exec` открывает shell пользователя `app` (алиас на `docker compose exec --user app php bash`)

## 4. Верификация

- [x] 4.1 `npm run build` завершается без ошибок и предупреждений; бандлы CSS/JS валидны (Entrypoint app: runtime + app.css + app.js)
- [x] 4.2 Прогон e2e smoke (вход администратора, открытие списка) после подключения бандлов — функциональность не сломалась: 4/4 passed (главная 200, вход админом, вход менеджером, неверный пароль)
- [x] 4.3 Проверка, что стили дизайн-системы (`_tokens.scss` и др.) отсутствуют в репо — они появятся только в `web-interface-design` (граница D7)