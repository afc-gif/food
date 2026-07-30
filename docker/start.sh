#!/bin/bash
set -e

cd /app/backend

echo "=== STARTUP BEGIN ==="
echo "Setting up directories..."
mkdir -p storage/logs storage/framework/{cache,data,sessions,views} bootstrap/cache /run/nginx
chmod -R 775 storage bootstrap/cache /run/nginx
chown -R www-data:www-data storage bootstrap/cache /run/nginx public storage/logs

echo "Creating .env..."
if [ ! -f .env ]; then
    cat > .env << 'EOF'
APP_NAME=Acie
APP_ENV=production
APP_DEBUG=false
APP_URL=https://www.afc.com.ng
APP_KEY=base64:FjOkA8pS+80LCAG9Dk8ufkH3PcDn8VY3GMLlfdpt2wg=
LOG_CHANNEL=stack
LOG_LEVEL=debug
DB_CONNECTION=pgsql
DB_HOST=switchback.proxy.rlwy.net
DB_PORT=49743
DB_DATABASE=railway
DB_USERNAME=postgres
DB_PASSWORD=QrreLCphAXwYVfAJzODFGeWeWUXMLDBT
SESSION_DRIVER=database
CACHE_STORE=database
BROADCAST_CONNECTION=log
QUEUE_CONNECTION=database
EOF
fi

echo "Running storage link..."
php artisan storage:link 2>&1 || true

echo "Clearing caches..."
php artisan config:clear 2>&1 || true

echo "Caching config..."
php artisan config:cache 2>&1 || echo "Config cache failed, continuing..."

echo "Caching routes..."
php artisan route:cache 2>&1 || echo "Route cache failed, continuing..."

echo "Running migrations..."
php artisan migrate --force 2>&1 || echo "Migration failed, continuing..."

echo "Starting PHP-FPM..."
php-fpm -D

echo "=== PHP-FPM STARTED ==="
sleep 2

# Verify PHP-FPM is listening
echo "Checking PHP-FPM socket connectivity..."
if php -r "var_dump(fsockopen('127.0.0.1', 9000, \$errno, \$errstr, 2));" 2>&1 | grep -q "resource"; then
    echo "✓ PHP-FPM socket is responding"
else
    echo "✗ WARNING: PHP-FPM socket may not be responding"
fi

echo "Validating Nginx config..."
if ! nginx -t 2>&1; then
    echo "❌ Nginx config is invalid!"
    exit 1
fi

echo "=== STARTING NGINX ON PORT 80 ==="
echo "Nginx PID: $$"

# Start Nginx in foreground - if it exits, this script exits
exec nginx -g 'daemon off;'
