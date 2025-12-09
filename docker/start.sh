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

# Start PHP-FPM
php-fpm -D

# Start Nginx in foreground
nginx -g 'daemon off;'
