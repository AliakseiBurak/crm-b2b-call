## 1. Бутстрап сборки

- [ ] 1.1 Создать `package.json` (npm init -y) с скриптами `dev` (`encore dev-server`), `watch` (`encore dev --watch`), `build` (`encore production`)
- [ ] 1.2 Установить зависимости: `@symfony/webpack-encore`, `sass-loader`, `sass`, `webpack`, `webpack-cli`, `webpack-dev-server`, `@fontsource/roboto`, `@fontsource/roboto-condensed`; зафиксировать `package-lock.json`
- [ ] 1.3 Создать `webpack.config.js`: `Encore.setOutputPath('public/build')`, `setPublicPath('/build')`, `addEntry('app', './assets/app.js')`, `enableSassLoader()`, `SplitEntryChunks`, production/development режимы из Encore
- [ ] 1.4 Обеспечить Node.js в окружении (php-контейнер compose или хост): `npm ci` выполняется без ошибок

## 2. Каркас активов и подключение

- [ ] 2.1 Создать `assets/app.js` — точка входа, импорт `./scss/app.scss`
- [ ] 2.2 Создать `assets/scss/app.scss` — пустая точка входа стилей (без файлов дизайн-системы; их добавляет `web-interface-design`)
- [ ] 2.3 В `templates/base.html.twig` добавить `{{ encore_entry_link_tags('app') }}` (секция stylesheets) и `{{ encore_entry_script_tags('app') }}` (перед `</body>`)

## 3. Сборка и режимы

- [ ] 3.1 Выполнить `npm run build`; убедиться, что `public/build/` содержит `entrypoints.json`, `app.js`, `app.css` (с хэшами)
- [ ] 3.2 Проверить страницу `https://b2b-crm.local`: `<link rel="stylesheet">` и `<script>` на `/build/*` возвращают 200
- [ ] 3.3 Проверить dev-режим: `encore dev-server` (или `watch`) реагирует на изменение `assets/scss/app.scss`, сборка обновляется
- [ ] 3.4 Убедиться, что без `npm run build` страница не падает (хелперы рендерят пусто) — базовый шаг отката
- [ ] 3.5 Убедиться, что `public/build/` не попадает в git (добавить в `.gitignore`, если отсутствует)

## 4. Верификация

- [ ] 4.1 `npm run build` завершается без ошибок и предупреждений; CSS/JS валидны (проверка через браузер/curl)
- [ ] 4.2 Прогон e2e smoke (вход администратора, открытие списка) после подключения бандлов — функциональность не сломалась
- [ ] 4.3 Проверка, что стили дизайн-системы (`_tokens.scss` и др.) отсутствуют в репо — они появятся только в `web-interface-design` (граница D7)