## Purpose

Определяет процедуру создания пользователя системы администратором: какие поля
принимаются при создании, как валидируется роль и как обеспечивается доступ к
операции создания.

## ADDED Requirements

### Requirement: Создавать пользователя может только администратор
The system SHALL allow creating a user only to an authenticated administrator.
Managers and anonymous users SHALL NOT be permitted to create users.

#### Scenario: Администратор создаёт пользователя
- **WHEN** аутентифицированный администратор открывает форму создания пользователя и отправляет валидные данные
- **THEN** система создаёт нового пользователя

#### Scenario: Менеджер не может создавать пользователей
- **WHEN** аутентифицированный менеджер пытается открыть форму или выполнить запрос создания пользователя
- **THEN** система отклоняет операцию с ошибкой доступа

#### Scenario: Анонимный пользователь не может создавать пользователей
- **WHEN** неаутентифицированный пользователь пытается выполнить запрос создания пользователя
- **THEN** система возвращает ошибку аутентификации

### Requirement: Email обязателен при создании пользователя
The system SHALL require the `email` field when creating a user and SHALL
reject creation with a missing or invalid email. The email SHALL be unique
across all users. The user will set their password later via password
reset; no password is set during creation.

#### Scenario: Создание пользователя с email
- **WHEN** администратор создаёт пользователя с указанием email
- **THEN** пользователь создаётся с указанным email

#### Scenario: Отклонение создания без email
- **WHEN** администратор создаёт пользователя и не указывает email
- **THEN** система отклоняет создание с ошибкой валидации

#### Scenario: Отклонение создания с существующим email
- **WHEN** администратор создаёт пользователя с email, который уже существует в системе
- **THEN** система отклоняет создание с ошибкой уникальности

#### Scenario: Отклонение создания с некорректным email
- **WHEN** администратор создаёт пользователя с email "not-an-email"
- **THEN** система отклоняет создание с ошибкой валидации

### Requirement: Роль обязательна при создании пользователя
The system SHALL require the `role` field (`admin` or `manager`) when creating
a user and SHALL reject creation with a missing or invalid role. The role set
SHALL be fixed (ADR-0009).

#### Scenario: Создание пользователя с ролью manager
- **WHEN** администратор создаёт пользователя и указывает роль manager
- **THEN** пользователь создаётся с ролью manager

#### Scenario: Создание пользователя с ролью admin
- **WHEN** администратор создаёт пользователя и указывает роль admin
- **THEN** пользователь создаётся с ролью admin

#### Scenario: Отклонение создания без роли
- **WHEN** администратор создаёт пользователя и не указывает роль
- **THEN** система отклоняет создание с ошибкой валидации

#### Scenario: Отклонение произвольной роли
- **WHEN** администратор создаёт пользователя и указывает роль supervisor
- **THEN** система отклоняет создание с ошибкой валидации

### Requirement: Поля имени и фамилии необязательны
The system SHALL accept the `name` and `surname` fields as optional at user
creation. The fields MAY be empty or omitted; creation SHALL succeed without
them.

#### Scenario: Создание пользователя с именем и фамилией
- **WHEN** администратор создаёт пользователя "Мария Смирнова" с указанием имени и фамилии
- **THEN** пользователь создаётся с сохранёнными значениями name="Мария" и surname="Смирнова"

#### Scenario: Создание пользователя без имени и фамилии
- **WHEN** администратор создаёт пользователя, не указывая имя и фамилию
- **THEN** пользователь создаётся, а поля name и surname остаются пустыми

#### Scenario: Создание пользователя с именем без фамилии
- **WHEN** администратор создаёт пользователя, указывая только имя
- **THEN** пользователь создаётся с указанным именем и пустой фамилией

### Requirement: Персональная группа создаётся только для менеджера
The system SHALL auto-create a personal `user-<id>-group` for a created user
with role `manager` (ADR-0005). For a created user with role `admin`, the
personal group SHALL NOT be created (ADR-0008).

#### Scenario: Создание менеджера создаёт персональную группу
- **WHEN** администратор создаёт пользователя с ролью manager
- **THEN** система автоматически создаёт персональную группу `user-<id>-group` и связывает её с новым пользователем

#### Scenario: Создание администратора не создаёт группу
- **WHEN** администратор создаёт пользователя с ролью admin
- **THEN** персональная группа `user-<id>-group` не создаётся
