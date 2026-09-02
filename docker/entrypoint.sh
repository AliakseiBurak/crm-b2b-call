#!/usr/bin/env bash
set -euo pipefail

cd /app

if [ -z "${DATABASE_URL:-}" ]; then
    echo "[entrypoint] ОШИБКА: DATABASE_URL не задан. Скопируйте .env.example в .env и пересоздайте контейнер: docker compose up -d --force-recreate php" >&2
    exit 1
fi

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

echo "[entrypoint] Настройка git..."
if [ -n "${GIT_AUTHOR_NAME:-}" ]; then
    git config --global user.name "$GIT_AUTHOR_NAME"
    git config --global user.email "$GIT_AUTHOR_EMAIL"
    echo "[entrypoint] Git настроен: $GIT_AUTHOR_NAME <$GIT_AUTHOR_EMAIL>"
fi

echo "[entrypoint] Установка npm-зависимостей..."
if [ -d node_modules ]; then
    echo "[entrypoint] node_modules уже установлен, npm ci пропущен."
else
    npm ci --no-audit --no-fund
fi

echo "[entrypoint] Сборка фронтенд-активов (Webpack Encore)..."
npm run build

echo "[entrypoint] Приведение прав npm-артефактов к пользователю app..."
chown -R app:app node_modules public/build

echo "[entrypoint] Готово, запускаю: $*"
exec "$@"
