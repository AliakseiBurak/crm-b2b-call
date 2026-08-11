# Integrations

Внешние интеграции: подключение телефонных систем, интеграция с API обучающих
платформ, использование email-сервисов (SMTP, SendGrid и др.), безопасный доступ
к API через OAuth2/JWT и хранение файлов (записей звонков, сертификатов) в
S3/ MinIO.

## Purpose

Внешние интеграции: телефонные системы, API обучающих платформ, email-сервисы
(SMTP, SendGrid), безопасный доступ через OAuth2/JWT и хранение файлов в
объектном хранилище S3/MinIO.

## Requirements

### Requirement: Подключение к телефонным системам
The system SHALL connect to telephony systems to schedule calls and retrieve
call recordings.

#### Scenario: Подключение телефонной системы
- **WHEN** администратор настроил интеграцию с телефонной системой
- **AND** система получает запись разговора из телефонной системы
- **THEN** запись сохраняется по ссылке в карточке звонка

### Requirement: Интеграция с API обучающих платформ
The system SHALL integrate with learning platform APIs to fetch data on
courses, completed programs, and certificates.

#### Scenario: Получение данных платформы
- **WHEN** интеграция с обучающей платформой настроена
- **AND** система запрашивает сведения о завершённом курсе организации "ООО Ромашка"
- **THEN** в системе отображается пройденный курс и ссылка на сертификат

### Requirement: Отправка email через внешние сервисы
The system SHALL send campaigns through external email services (SMTP,
SendGrid, etc.).

#### Scenario: Отправка письма через внешний сервис
- **WHEN** интеграция с email-сервисом настроена
- **AND** система отправляет рассылку по группе контактов
- **THEN** письма передаются через внешний email-сервис доставки

### Requirement: Безопасный доступ к API
API access SHALL be secured via OAuth2/JWT, and sensitive data SHALL be
encrypted.

#### Scenario: Авторизация запроса к API
- **WHEN** клиент не имеет действительного токена
- **AND** клиент обращается к API без токена
- **THEN** API отклоняет запрос с ошибкой авторизации

### Requirement: Хранение файлов в облачном хранилище
The system SHALL store call recordings and certificates in object storage
(S3/MinIO) and SHALL provide links to them.

#### Scenario: Сохранение записи звонка
- **WHEN** запись разговора получена из телефонной системы
- **AND** система помещает её в объектное хранилище
- **THEN** карточка звонка содержит ссылку на сохранённую запись
