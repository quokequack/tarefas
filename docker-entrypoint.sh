#!/bin/sh
set -eu

: "${APP_KEY:?Defina APP_KEY nas variáveis de ambiente da Vercel.}"
: "${DB_URL:?Defina DB_URL com a conexão PostgreSQL nas variáveis de ambiente da Vercel.}"

mkdir -p \
    "$LARAVEL_STORAGE_PATH/framework/cache/data" \
    "$LARAVEL_STORAGE_PATH/framework/cache/locks" \
    "$LARAVEL_STORAGE_PATH/framework/sessions" \
    "$LARAVEL_STORAGE_PATH/framework/views" \
    "$LARAVEL_STORAGE_PATH/logs" \
    "$(dirname "$APP_CONFIG_CACHE")" \
    "$XDG_CONFIG_HOME" \
    "$XDG_DATA_HOME"

# Os segredos e as URLs só estão disponíveis quando a instância inicia.
php artisan config:cache --no-interaction
php artisan view:cache --no-interaction

exec "$@"
