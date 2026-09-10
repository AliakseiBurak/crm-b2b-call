## Purpose

Запрет индексации приложения B2B Call CRM поисковыми системами на уровне HTTP-ответов и HTML-документов, чтобы гарантировать, что внутренний бизнес-инструмент с персональными данными клиентов не попадёт в результаты поиска.

## Requirements

### Requirement: HTTP-заголовок X-Robots-Tag на всех ответах
The system SHALL include the HTTP header `X-Robots-Tag: noindex, nofollow` in every response, regardless of the route, controller, or template used. The header SHALL be present on web pages, API responses, and any other HTTP endpoints.

#### Scenario: Заголовок на странице входа
- **WHEN** незалогиненный пользователь запрашивает страницу входа
- **THEN** ответ содержит заголовок `X-Robots-Tag: noindex, nofollow`

#### Scenario: Заголовок на защищённой странице
- **WHEN** аутентифицированный пользователь запрашивает дашборд
- **THEN** ответ содержит заголовок `X-Robots-Tag: noindex, nofollow`

#### Scenario: Заголовок на API-ответе
- **WHEN** клиент выполняет запрос к API-эндпоинту
- **THEN** ответ содержит заголовок `X-Robots-Tag: noindex, nofollow`

#### Scenario: Заголовок на странице без авторизации
- **WHEN** незалогиненный пользователь запрашивает любую страницу приложения
- **THEN** ответ содержит заголовок `X-Robots-Tag: noindex, nofollow`

### Requirement: Мета-тег robots в базовом шаблоне Twig
The system SHALL include a `<meta name="robots" content="noindex, nofollow">` tag in the `<head>` section of every HTML page rendered through the base Twig template. This serves as a redundant layer of protection for crawlers that process HTML but ignore HTTP headers.

#### Scenario: Мета-тег на странице входа
- **WHEN** пользователь открывает страницу входа
- **THEN** в секции `<head>` HTML-документа присутствует тег `<meta name="robots" content="noindex, nofollow">`

#### Scenario: Мета-тег на дашборде
- **WHEN** аутентифицированный пользователь открывает дашборд
- **THEN** в секции `<head>` HTML-документа присутствует тег `<meta name="robots" content="noindex, nofollow">`

#### Scenario: Мета-тег на странице списков
- **WHEN** пользователь открывает список контактов, организаций или звонков
- **THEN** в секции `<head>` HTML-документа присутствует тег `<meta name="robots" content="noindex, nofollow">`

#### Scenario: Мета-тег на welcome page
- **WHEN** пользователь открывает welcome page
- **THEN** в секции `<head>` HTML-документа присутствует тег `<meta name="robots" content="noindex, nofollow">`
