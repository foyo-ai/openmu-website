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
# Non-fatal: if a view fails to precompile, Laravel compiles it on demand instead of
# crash-looping the whole container (a broken page 500s; the site stays up).
php artisan view:cache || true

# Prime the leaderboard snapshot now (best-effort), then keep it fresh every minute
# via the Laravel scheduler running in the background (schedule:work -> rankings:refresh).
php artisan rankings:refresh >/dev/null 2>&1 || true
php artisan schedule:work >/dev/null 2>&1 &

# php-fpm in the background, nginx in the foreground (keeps the container alive).
php-fpm -D
exec nginx -g 'daemon off;'
