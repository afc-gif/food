#!/bin/bash
set -e

cd /app/backend

PORT=${PORT:-80}

echo "===== APP STARTUP ====="
echo "Starting PHP-FPM..."
php-fpm -D
echo "PHP-FPM started"

sleep 1

echo "Starting Nginx on port $PORT..."
exec nginx -g 'daemon off;'
