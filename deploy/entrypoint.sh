#!/bin/sh
set -e
cd /app

# Allow one-off commands, e.g. `docker compose run --rm web php artisan migrate --force`
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

# Re-cache config & views with the runtime environment present (env injected by compose).
# NOTE: deliberately NOT route:cache — mcamara localized-route prefixes don't cache cleanly.
php artisan config:clear  >/dev/null 2>&1 || true
php artisan config:cache
php artisan view:cache

# php-fpm in the background, nginx in the foreground (keeps the container alive).
php-fpm -D
exec nginx -g 'daemon off;'
