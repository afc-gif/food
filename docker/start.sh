#!/bin/bash
set -e

cd /app/backend

echo "[$(date)] Starting application..."

# Ensure writable dirs exist
mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache
echo "[$(date)] Directories prepared"

# Ensure storage symlink for uploaded images
php artisan storage:link 2>&1 || echo "[$(date)] Storage link already exists"
echo "[$(date)] Storage link verified"

# Cache config/routes for speed
php artisan config:clear 2>&1 || true
php artisan route:clear 2>&1 || true
echo "[$(date)] Cache cleared"

php artisan config:cache 2>&1
echo "[$(date)] Config cached"

php artisan route:cache 2>&1
echo "[$(date)] Routes cached"

# Run migrations only (don't seed on every deploy)
echo "[$(date)] Running migrations..."
php artisan migrate --force 2>&1 || echo "[$(date)] Migrations completed or skipped"

echo "[$(date)] Testing Nginx config..."
nginx -t 2>&1 || { echo "Nginx config invalid!"; exit 1; }

echo "[$(date)] Starting PHP-FPM..."
php-fpm -D

sleep 2

echo "[$(date)] Starting Nginx..."
exec nginx -g 'daemon off;'
