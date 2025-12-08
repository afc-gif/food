#!/bin/sh
set -e

cd /app/backend

# Ensure writable dirs exist
mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Ensure storage symlink for uploaded images
php artisan storage:link || true

# Cache config/routes for speed
php artisan config:clear || true
php artisan route:clear || true
php artisan config:cache
php artisan route:cache

# Run migrations (and seed) if DB is reachable
php artisan migrate --force --seed || true

# Start PHP-FPM and Reverb websocket server
php-fpm -D
# Run Reverb on the configured port (fall back to PORT, then 8080)
REVERB_BIND_PORT="${REVERB_SERVER_PORT:-${PORT:-8080}}"
php artisan reverb:start --host=0.0.0.0 --port="${REVERB_BIND_PORT}" &

# Start Nginx in foreground
nginx -g 'daemon off;'
