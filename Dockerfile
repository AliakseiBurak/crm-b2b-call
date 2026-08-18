FROM php:8.5-fpm-bookworm

ARG APP_UID=1000
ARG APP_GID=1000

# Fail fast with clear logs; isolate apt from extension compilation for cacheability.
SHELL ["/bin/bash", "-euxo", "pipefail", "-c"]

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libicu-dev \
        libzip-dev \
        unzip \
        git \
        curl \
        ca-certificates

RUN docker-php-ext-install \
        pdo_mysql \
        intl \
        zip \
        bcmath \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Fail the build loudly if required modules are not actually loadable.
RUN php -m | grep -Eq '^pdo_mysql$' \
    && php -m | grep -Eq '^intl$' \
    && php -m | grep -Eq '^zip$'

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN groupadd --gid ${APP_GID} app \
    && useradd --uid ${APP_UID} --gid ${APP_GID} --create-home --shell /bin/bash app

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
COPY docker/gen-certs.sh /usr/local/bin/gen-certs

RUN chmod +x /usr/local/bin/entrypoint /usr/local/bin/gen-certs

WORKDIR /app

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm"]
