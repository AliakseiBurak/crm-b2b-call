FROM php:8.5-fpm-bookworm

ARG APP_UID=1000
ARG APP_GID=1000

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        unzip \
        git \
        curl \
        ca-certificates \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        intl \
        zip \
        opcache \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --gid ${APP_GID} app \
    && useradd --uid ${APP_UID} --gid ${APP_GID} --create-home --shell /bin/bash app

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/gen-certs.sh /usr/local/bin/gen-certs

RUN chmod +x /usr/local/bin/entrypoint /usr/local/bin/gen-certs

WORKDIR /app

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
