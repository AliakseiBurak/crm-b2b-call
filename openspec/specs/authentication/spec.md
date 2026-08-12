# Authentication

Аутентификация построена на сессиях Symfony: `form_login` для браузерных
пользователей, `remember_me` для долгих сессий, `same_site` cookie.
API-эндпоинты всегда stateless и используют токен. В коде доступ проверяется
через `Security::getUser()`.

## Purpose

Единая модель аутентификации: сессионный вход через форму для UI и stateless
API с токенами, единые правила cookie и проверки доступа в коде.

## Requirements

### Requirement: Вход через форму (session-based auth)
The system SHALL authenticate UI users through `form_login` with session-based
cookies; the login form SHALL accept email and password, and successful
authentication SHALL start a server-side session.

#### Scenario: Успешный вход через форму
- **WHEN** незалогиненный пользователь открывает страницу входа
- **AND** вводит корректные email и пароль
- **THEN** пользователь оказывается аутентифицированным
- **AND** в браузере устанавливается session cookie

#### Scenario: Ошибка при неверном пароле
- **WHEN** незалогиненный пользователь вводит неверный пароль
- **THEN** система возвращает ошибку аутентификации
- **AND** сессия пользователя не создаётся

### Requirement: Remember me
The system SHALL support `remember_me` to keep the user authenticated after the
session expires; the remember-me cookie SHALL have `same_site` and security
flags configured, and re-authentication SHALL be required when the remember-me
cookie is invalid or missing.

#### Scenario: Вход с опцией запомнить меня
- **WHEN** пользователь входит через форму с опцией "запомнить меня"
- **THEN** система устанавливает remember-me cookie с флагом `SameSite`
- **AND** после истечения сессии пользователь остаётся аутентифицированным

#### Scenario: Невалидная remember-me cookie
- **WHEN** у пользователя есть невалидная или просроченная remember-me cookie
- **THEN** пользователь не аутентифицируется автоматически
- **AND** система требует повторный вход через форму

### Requirement: Stateless API
The system SHALL run API endpoints with `stateless: true` and SHALL NOT rely on
session cookies for them; API requests SHALL be authenticated with a token.

#### Scenario: API не использует сессию
- **WHEN** клиент API выполняет запрос с токеном
- **THEN** запрос проходит аутентификацию по токену без использования сессии
- **AND** ответ не зависит от cookies браузера

#### Scenario: API без токена отклоняется
- **WHEN** клиент API выполняет запрос без токена
- **THEN** система возвращает 401 Unauthorized

### Requirement: Проверка доступа через Security::getUser()
The system SHALL use `Security::getUser()` for access control in controllers,
and SHALL NOT rely on global or static state to determine the current user.

#### Scenario: Действие с неаутентифицированным пользователем
- **WHEN** анонимный пользователь обращается к защищённому маршруту
- **THEN** `Security::getUser()` возвращает null
- **AND** система перенаправляет на страницу входа или возвращает 401

#### Scenario: Роль пользователя учитывается в доступе
- **WHEN** пользователь с ролью manager обращается к защищённому маршруту
- **THEN** доступ определяется `Security::getUser()` и его ролью по модели
  доступа (`adr/0005–0008`)