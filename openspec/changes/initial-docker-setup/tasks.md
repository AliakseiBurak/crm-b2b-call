# Tasks: initial-docker-setup

## 1. Docker-инфраструктура

- [x] 1.1 Создать `compose.yaml` с сервисами `php`, `nginx`, `mysql`, `mailpit`, `e2e` (сеть compose, фиксированные теги образов)
- [x] 1.2 Создать `Dockerfile` от `php:8.5-fpm`: расширения `pdo_mysql`, `intl`, `zip`, `opcache`; бинарь Composer; `COPY docker/gen-certs.sh` и entrypoint; фиксированный uid приложения
- [x] 1.3 Создать конфиг `nginx` (default.conf): root на `public/`, fastcgi-прокирование на `php:9000`, **TLS-only на порту 443** (серверный сертификат из volume `/certs`, без HTTP-локации)
- [x] 1.4 Настроить сервис `mysql` (MySQL 8.x, конкретный тег): healthcheck, named volume для `/var/lib/mysql`, `MYSQL_DATABASE/USER/PASSWORD` из `.env`
- [x] 1.5 Настроить сервис `mailpit`: SMTP `:1025`, web UI `:8025`
- [x] 1.6 Создать `.env` (локально, вне git) и `.env.example` (в git): порты хоста (443 и др.), креды БД, `DATABASE_URL`, `MAILER_DSN`
- [x] 1.7 Создать `docker/gen-certs.sh`: генерация CA (`ca.crt`/`ca.key`) и серверного сертификата с SAN `b2b-crm.local, localhost, 127.0.0.1, host.docker.internal`, срок 10 лет; идемпотентно (не перезаписывает существующие файлы)
- [x] 1.8 Подключить named volume `/certs` к `php` (генерация) и `nginx` (использование); генерация при первом старте через entrypoint

## 2. Symfony-приложение и зависимости

- [x] 2.1 Инициализировать пустой проект Symfony 7.4 LTS (PHP 8.5, Doctrine ORM 3.x), добавить реквизиты в `composer.json`
- [x] 2.2 Подключить Doctrine ORM, `doctrine/doctrine-migrations-bundle`, Symfony Mailer; настроить `DATABASE_URL` и `MAILER_DSN` в `.env`
- [x] 2.3 Подключить `doctrine/doctrine-fixtures-bundle` (dev)
- [x] 2.5 Перевести сущности на асимметричную видимость свойств (`public private(set)`, запись только из класса; геттеры убраны, мутабельные поля — методы-сеттеры; интерфейсные методы Security остаются)
- [ ] 2.4 Убедиться, что приложение отвечает через `nginx` (health-роут, страница без ошибок)

## 3. Схема БД, fixtures и запуск

- [x] 3.1 Создать начальную миграцию со схемой ядра (users, organization_groups, organizations, contacts, calls, join-таблицы членства/назначений — по `openspec/design/er.md`)
- [x] 3.2 Создать fixtures: admin и менеджеры (с автосозданием `user-<id>-group`), custom-группы, организации, контакты, запланированные звонки; учётные данные `admin@b2b-crm.loc`/`admin123` и `manager@b2b-crm.loc`/`manager123`
- [x] 3.3 Создать entrypoint-скрипт `php`-контейнера: `composer install` → `gen-certs.sh` (при первом старте) → `doctrine:migrations:migrate --no-interaction` → опционально `doctrine:fixtures:load` (идемпотентно)
- [x] 3.4 Создать Makefile-таргеты: `up`, `migrate`, `fixtures`, `down`, `e2e`
- [x] 3.5 Написать README: точные инструкции — запись `127.0.0.1 b2b-crm.local` в `/etc/hosts` (Linux), копирование `ca.crt` в `/usr/local/share/ca-certificates/` + `update-ca-certificates`, адрес `https://b2b-crm.local`, порядок пересоздания окружения и предупреждение о `down -v` (потеря сертификатов → переустановка CA; смена имени хоста требует удалить volume `certs`)

## 4. Проверка окружения (Playwright e2e)

- [x] 4.1 Создать `e2e/` с зависимостями (@playwright/test, TypeScript) и `playwright.config.ts` (baseURL из `BASE_URL`, по умолчанию `https://b2b-crm.local`; `ignoreHTTPSErrors: true` всегда)
- [x] 4.2 Настроить сервис `e2e` в compose (образ Playwright, резолвит `b2b-crm.local` через Docker DNS, mount `e2e/`) и таргет `make e2e`
- [x] 4.3 Написать smoke-тесты: главная страница отвечает 200; вход администратором (`admin@b2b-crm.loc`); вход менеджером (`manager@b2b-crm.loc`); неверный пароль → ошибка и отсутствие сессии
- [x] 4.4 Обеспечить запуск из контейнера OpenCode: `BASE_URL=https://host.docker.internal make e2e` (DNS/хостов нет, стандартный HTTPS-порт проброшен)
- [ ] 4.5 Проверить оба способа запуска (`make e2e` и из OpenCode), повторный запуск миграций/fixtures идемпотентен
