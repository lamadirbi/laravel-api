#!/bin/sh
set -e
cd /var/www/html

# Optional: set SKIP_MIGRATIONS=1 in Render if something else runs migrations.
if [ "${SKIP_MIGRATIONS:-0}" != "1" ]; then
  php artisan migrate --force
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8080}"
