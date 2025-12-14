#!/bin/bash
set -e

cd /app/backend

# Create .env if missing
if [ ! -f .env ]; then
    cat > .env << 'EOF'
APP_NAME="Acie Fraiche Cafe"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:FjOkA8pS+80LCAG9Dk8ufkH3PcDn8VY3GMLlfdpt2wg=
APP_URL=https://afc.com.ng
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

# Ensure directories
mkdir -p storage/logs storage/framework/{cache,data,sessions,views} bootstrap/cache /run/nginx
chmod -R 777 storage bootstrap/cache /run/nginx
chown -R www-data:www-data storage bootstrap/cache /run/nginx public storage/logs

# Clear caches
php artisan config:clear 2>&1 || true
php artisan route:clear 2>&1 || true

# Cache config and routes
php artisan config:cache
php artisan route:cache

# Run migrations
php artisan migrate --force || true

# Start PHP-FPM in background
echo "Starting PHP-FPM..."
php-fpm -D

# Wait for PHP-FPM to be ready
sleep 2

# Check PHP-FPM is running
if ! pgrep -f php-fpm > /dev/null; then
    echo "ERROR: PHP-FPM failed to start"
    exit 1
fi

echo "PHP-FPM is running"
echo "Starting Nginx..."

# Start Nginx in foreground (this keeps the container alive)
nginx -g 'daemon off;'
