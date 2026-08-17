#!/usr/bin/env bash
set -euo pipefail

cd /app

echo "[entrypoint] Установка зависимостей..."
if [ -f vendor/autoload.php ]; then
    echo "[entrypoint] vendor/ уже установлен, composer install пропущен."
else
    COMPOSER_ALLOW_SUPERUSER=1 composer install --no-interaction --prefer-dist --no-progress
fi

echo "[entrypoint] Проверка сертификатов (первый старт — генерация)..."
gen-certs

echo "[entrypoint] Миграции..."
php bin/console doctrine:migrations:migrate --no-interaction

if [ "${ENABLE_FIXTURES:-1}" = "1" ]; then
    echo "[entrypoint] Fixtures..."
    php bin/console doctrine:fixtures:load --no-interaction
else
    echo "[entrypoint] ENABLE_FIXTURES != 1, fixtures пропущены."
fi

echo "[entrypoint] Готово, запускаю: $*"
exec "$@"
