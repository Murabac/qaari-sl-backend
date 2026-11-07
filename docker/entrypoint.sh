#!/bin/sh
set -eu

cd /app

mkdir -p \
  storage/app/public \
  storage/app/private \
  storage/app/tmp \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

# Coolify mounts /app/storage and may run the container as a non-root uid.
chmod -R a+rwX storage bootstrap/cache || true

php artisan storage:link --force >/dev/null 2>&1 || true

if [ -n "${APP_KEY:-}" ]; then
  php artisan config:cache --no-interaction
  php artisan view:cache --no-interaction
fi

chmod -R a+rwX storage bootstrap/cache || true

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
  php artisan migrate --force --no-interaction
fi

# Ayah sync is queued. Restart the worker if it exits; do not block FrankenPHP.
if [ "${RUN_QUEUE:-true}" = "true" ]; then
  (
    while true; do
      php artisan queue:work --sleep=3 --tries=2 --timeout=600 --max-time=3600 || true
      sleep 2
    done
  ) >> storage/logs/queue.log 2>&1 &
fi

exec frankenphp run --config /etc/caddy/Caddyfile
