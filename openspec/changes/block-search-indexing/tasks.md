## 1. HTTP-заголовок X-Robots-Tag

- [ ] 1.1 Создать event subscriber `SearchIndexingSubscriber` на событие `kernel.response`, добавляющий заголовок `X-Robots-Tag: noindex, nofollow` к каждому Response. Проверить: файл создан в `src/EventSubscriber/`, сервис автодискаверится Symfony.
- [ ] 1.2 Зарегистрировать subscriber как сервис (если не автодискаверится) и убедиться, что он применяется ко всем ответам. Проверить: `php bin/console debug:event-dispatcher kernel.response` показывает subscriber.

## 2. Мета-тег robots в Twig

- [ ] 2.1 Добавить `<meta name="robots" content="noindex, nofollow">` в секцию `<head>` базового шаблона `templates/base.html.twig`. Проверить: тег присутствует в файле в секции `<head>`.

## 3. Тесты

- [ ] 3.1 Написать functional test, проверяющий наличие заголовка `X-Robots-Tag: noindex, nofollow` в ответе на GET-запрос к странице входа (`/login`). Проверить: тест проходит (`php bin/phpunit --filter=testRobotsHeaderOnLogin`).
- [ ] 3.2 Написать functional test, проверяющий наличие `<meta name="robots" content="noindex, nofollow">` в HTML-ответе на страницу входа. Проверить: тест проходит.
- [ ] 3.3 Написать functional test, проверяющий наличие заголовка `X-Robots-Tag` в API-ответе (GET к любому API-эндпоинту). Проверить: тест проходит.

## 4. Верификация

- [ ] 4.1 Запустить полный набор тестов (`php bin/phpunit`) и убедиться, что все тесты проходят, включая новые и существующие. Проверить: 0 ошибок, 0 падений.
- [ ] 4.2 Проверить через `curl -I` (или аналог), что заголовок `X-Robots-Tag: noindex, nofollow` присутствует в ответе на запрос к приложению. Проверить: заголовок виден в выводе.
