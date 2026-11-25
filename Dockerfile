FROM dunglas/frankenphp:1-php8.5-bookworm AS base

WORKDIR /app

RUN install-php-extensions pdo_pgsql pdo_sqlite mbstring intl zip opcache \
    && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

FROM base AS dependencies

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./

RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .

RUN mkdir -p bootstrap/cache storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs \
    && composer dump-autoload --no-dev --classmap-authoritative --no-scripts --no-interaction

FROM base AS runtime

ENV APP_ENV=production \
    APP_NAME="Laravel Task Manager" \
    APP_DEBUG=false \
    APP_LOCALE=pt_BR \
    APP_TIMEZONE=America/Maceio \
    DB_CONNECTION=pgsql \
    DB_SSLMODE=require \
    CACHE_STORE=database \
    SESSION_DRIVER=database \
    SESSION_ENCRYPT=true \
    SESSION_SECURE_COOKIE=true \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info \
    QUEUE_CONNECTION=sync \
    BOOST_ENABLED=false \
    BOOST_BROWSER_LOGS_WATCHER=false \
    TRUSTED_PROXIES=* \
    PORT=80 \
    LARAVEL_STORAGE_PATH=/tmp/laravel/storage \
    APP_CONFIG_CACHE=/tmp/laravel/bootstrap/config.php \
    APP_SERVICES_CACHE=/tmp/laravel/bootstrap/services.php \
    APP_PACKAGES_CACHE=/tmp/laravel/bootstrap/packages.php \
    APP_ROUTES_CACHE=/tmp/laravel/bootstrap/routes.php \
    APP_EVENTS_CACHE=/tmp/laravel/bootstrap/events.php \
    XDG_CONFIG_HOME=/tmp/caddy/config \
    XDG_DATA_HOME=/tmp/caddy/data

COPY --from=dependencies /app /app
COPY Caddyfile /etc/caddy/Caddyfile
COPY docker-entrypoint.sh /usr/local/bin/laravel-entrypoint

RUN chmod +x /usr/local/bin/laravel-entrypoint

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/laravel-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
