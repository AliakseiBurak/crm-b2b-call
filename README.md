# B2B Call CRM — локальное окружение (Docker)

Docker-окружение для разработки: PHP 8.5 FPM, nginx (TLS), MySQL 8.4, Mailpit,
Playwright e2e. Приложение доступно только по HTTPS: `https://b2b-crm.loc:8443`.

Учётные данные fixtures:

| Роль      | Email                    | Пароль      |
|-----------|--------------------------|-------------|
| Админ     | `admin@b2b-crm.loc`      | `admin123`  |
| Менеджер  | `manager@b2b-crm.loc`    | `manager123`|

## Требования

- Linux, Docker с Compose v2 (`docker compose version`), `make`
- Порты: 8443 (HTTPS), 3306 (MySQL), 8025 (Mailpit UI) — свободны

## Первый запуск

1. Настроить окружение (один раз):

   ```bash
   cp .env.example .env
   echo "127.0.0.1 b2b-crm.loc" | sudo tee -a /etc/hosts
   ```

2. Поднять контейнеры:

   ```bash
   make up
   ```

   При первом старте контейнер `php` сам выполнит: `composer install`,
   генерацию CA и серверного сертификата в volume `/certs`, миграции
   (`doctrine:migrations:migrate`) и загрузку fixtures (`ENABLE_FIXTURES=1`).

3. Довериться CA в системном хранилище (Linux, один раз):

   ```bash
   docker compose exec php cat /certs/ca.crt > /tmp/b2b-crm-ca.crt
   sudo mv /tmp/b2b-crm-ca.crt /usr/local/share/ca-certificates/b2b-crm-ca.crt
   sudo update-ca-certificates
   ```

   Если браузер всё равно не доверяет сертификату — импортируйте `ca.crt`
   вручную в хранилище браузера (Chrome: `chrome://settings/security` →
   «Управление сертификатами»).

4. Открыть `https://b2b-crm.loc:8443` (логин см. выше). Mailpit UI —
   `http://localhost:8025`.

## Повседневные команды

| Команда                 | Действие                                           |
|-------------------------|----------------------------------------------------|
| `make up`               | Поднять все сервисы (`php`, `nginx`, `mysql`, `mailpit`) |
| `make down`             | Остановить сервисы (данные БД сохраняются)         |
| `make migrate`          | Применить миграции                                 |
| `make fixtures`         | Перезагрузить fixtures                             |
| `make e2e`              | Запустить Playwright smoke-тесты (профиль `e2e`)   |

## E2E-тесты

Сервис `e2e` (образ Playwright) запускается в сети compose и открывает
`https://b2b-crm.loc:8443` (внутри сети имя резолвится Docker DNS):

```bash
make e2e
```

URL по умолчанию можно переопределить переменной `BASE_URL` — shell-переменная
перекрывает значение из `.env`:

```bash
BASE_URL=https://host.docker.internal:8443 make e2e
```

Этот вариант нужен, когда у хоста нет записи `b2b-crm.loc` в `/etc/hosts`,
а порт 8443 проброшен через `host.docker.internal` (например, docker поднят
на другом хосте).

### Запуск из контейнера OpenCode

В контейнере OpenCode docker обычно недоступен, поэтому `make e2e` выполняется
на хосте. Если в OpenCode-контейнере есть Node и доступен `host.docker.internal:8443`:

```bash
cd e2e
npm install
npx playwright install chromium
BASE_URL=https://host.docker.internal:8443 npm test
```

## Пересоздание окружения (сброс)

Данные БД и сертификаты живут в named volumes (`mysql-data`, `certs`).

⚠️ **`docker compose down -v` удаляет БД И сертификаты.** После него:

- БД и fixtures будут пересозданы автоматически при старте;
- CA станет новым — нужно заново повторить шаг 3 из «Первого запуска»
  (иначе браузеры и curl будут ругаться на сертификат).

Полный сброс:

```bash
make down
docker volume rm b2b-crm_certs b2b-crm_mysql-data
make up
```

## Полезное

- Логи: `docker compose logs -f php nginx`
- Оболочка в контейнере: `docker compose exec php bash`
- Включить/выключить fixtures при старте: `ENABLE_FIXTURES=0/1` в `.env`
  (после изменения — `docker compose up -d --force-recreate php`)
