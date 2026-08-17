# Tasks: initial-docker-setup

## 1. Docker-инфраструктура

- [ ] 1.1 Создать `compose.yaml` с сервисами `php`, `nginx`, `mysql`, `mailpit` (сеть compose, фиксированные теги образов)
- [ ] 1.2 Создать `Dockerfile` от `php:8.2-fpm`: расширения `pdo_mysql`, `intl`, `zip`, `opcache`; бинарь Composer; фиксированный uid приложения
- [ ] 1.3 Создать конфиг `nginx` (default.conf): root на `public/`, fastcgi-проксирование на `php:9000`, обработка статики
- [ ] 1.4 Настроить сервис `mysql` (MySQL 8.x, конкретный тег): healthcheck, named volume для `/var/lib/mysql`, `MYSQL_DATABASE/USER/PASSWORD` из `.env`
- [ ] 1.5 Настроить сервис `mailpit`: SMTP `:1025`, web UI `:8025`
- [ ] 1.6 Создать `.env` (локально, вне git) и `.env.example` (в git): порты хоста, креды БД, `DATABASE_URL`, `MAILER_DSN`; проброшенные порты читаются из переменных

## 2. Symfony-приложение и зависимости

- [ ] 2.1 Инициализировать пустой проект Symfony (совместимый с PHP 8.2+), добавить реквизиты в `composer.json`
- [ ] 2.2 Подключить Doctrine ORM, `doctrine/doctrine-migrations-bundle`, Symfony Mailer; настроить `DATABASE_URL` и `MAILER_DSN` в `.env`
- [ ] 2.3 Подключить `doctrine/doctrine-fixtures-bundle` (dev)
- [ ] 2.4 Убедиться, что приложение отвечает через `nginx` (health-роут, страница без ошибок)

## 3. Схема БД, fixtures и запуск

- [ ] 3.1 Создать начальную миграцию со схемой ядра (users, organization_groups, organizations, contacts, calls, join-таблицы членства/назначений — по `openspec/design/er.md`)
- [ ] 3.2 Создать fixtures: admin и менеджеры (с автосозданием `user-<id>-group`), custom-группы, организации, контакты, запланированные звонки
- [ ] 3.3 Создать entrypoint-скрипт `php`-контейнера: `composer install` → `doctrine:migrations:migrate --no-interaction` → опционально `doctrine:fixtures:load` (идемпотентно)
- [ ] 3.4 Создать Makefile-таргеты: `up`, `migrate`, `fixtures`, `down`
- [ ] 3.5 Написать README: `docker compose up` как единственная команда запуска, порядок пересоздания окружения

## 4. Проверка окружения

- [ ] 4.1 Проверить подъём окружения с чистого клона одной командой; повторный запуск миграций/fixtures идемпотентен
- [ ] 4.2 Проверить отправку тестового письма через Symfony Mailer и его отображение в Mailpit UI (`:8025`)
- [ ] 4.3 Проверить fixtures: вход под admin и менеджером, наличие групп, организаций, контактов и запланированных звонков