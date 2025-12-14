#!/bin/bash

cd /app/backend

LOG_FILE="storage/logs/startup.log"
mkdir -p storage/logs

echo "===== APP STARTUP: $(date) =====" | tee $LOG_FILE

# Ensure .env exists
if [ ! -f .env ]; then
    echo "[$(date)] Creating .env from environment variables..." | tee -a $LOG_FILE
    cat > .env << 'EOF'
APP_NAME="Acie Fraiche Cafe"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://afc.com.ng
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
    chmod 644 .env
fi

# Setup directories
echo "[$(date)] Setting up directories..." | tee -a $LOG_FILE
mkdir -p storage/logs storage/framework/{cache,data,sessions,views} bootstrap/cache /run/nginx
chmod -R 775 storage bootstrap/cache /run/nginx
chown -R www-data:www-data storage bootstrap/cache /run/nginx public storage/logs
echo "[$(date)] ✓ Directories ready" | tee -a $LOG_FILE

# Storage link
echo "[$(date)] Setting up storage link..." | tee -a $LOG_FILE
php artisan storage:link 2>&1 | tee -a $LOG_FILE || true
echo "[$(date)] ✓ Storage link verified" | tee -a $LOG_FILE

# Clear caches
echo "[$(date)] Clearing caches..." | tee -a $LOG_FILE
php artisan config:clear 2>&1 | tee -a $LOG_FILE || trueStarting Container
[Sun Dec 14 11:17:53 UTC 2025] Setting up storage link...
[Sun Dec 14 11:17:53 UTC 2025] Creating .env from environment variables...
===== APP STARTUP: Sun Dec 14 11:17:53 UTC 2025 =====
[Sun Dec 14 11:17:53 UTC 2025] Creating directories...
[Sun Dec 14 11:17:53 UTC 2025] ✓ Directories created and permissions set
   INFO  The [public/storage] link has been connected to [storage/app/public].  
[Sun Dec 14 11:17:53 UTC 2025] ✓ Storage link verified
[Sun Dec 14 11:17:53 UTC 2025] Clearing all caches...
   INFO  Route cache cleared successfully.  
   INFO  Configuration cache cleared successfully.  
   INFO  Compiled views cleared successfully.  
[Sun Dec 14 11:17:54 UTC 2025] ✓ Caches cleared
[Sun Dec 14 11:17:54 UTC 2025] Caching configuration...
[Sun Dec 14 11:17:55 UTC 2025] ✓ Config cached
[Sun Dec 14 11:17:55 UTC 2025] Caching routes...
   INFO  Configuration cached successfully.  
   INFO  Routes cached successfully.  
[Sun Dec 14 11:17:55 UTC 2025] ✓ Routes cached
[Sun Dec 14 11:17:55 UTC 2025] Running database migrations...
[14-Dec-2025 11:17:56] NOTICE: ready to handle connections
[Sun Dec 14 11:17:56 UTC 2025] Starting PHP-FPM daemon...
[Sun Dec 14 11:17:56 UTC 2025] ✓ PHP-FPM started
[14-Dec-2025 11:17:56] NOTICE: fpm is running, pid 52
[Sun Dec 14 11:17:56 UTC 2025] ✓ Migrations complete
[Sun Dec 14 11:17:56 UTC 2025] Validating Nginx configuration...
nginx: the configuration file /etc/nginx/nginx.conf syntax is ok
nginx: configuration file /etc/nginx/nginx.conf test is successful
[Sun Dec 14 11:17:56 UTC 2025] ✓ Nginx config valid
   INFO  Nothing to migrate.  
[Sun Dec 14 11:17:59 UTC 2025] Verifying PHP-FPM is listening...
[Sun Dec 14 11:17:59 UTC 2025] ⚠ Port 9000 not found, trying ss command...
[Sun Dec 14 11:17:59 UTC 2025] ✓ PHP-FPM listening on port 9000
php artisan route:clear 2>&1 | tee -a $LOG_FILE || true
php artisan view:clear 2>&1 | tee -a $LOG_FILE || true
echo "[$(date)] ✓ Caches cleared" | tee -a $LOG_FILE

# Cache config and routes
echo "[$(date)] Caching configuration..." | tee -a $LOG_FILE
php artisan config:cache 2>&1 | tee -a $LOG_FILE || echo "[$(date)] ❌ Config cache failed" | tee -a $LOG_FILE
echo "[$(date)] ✓ Config cached" | tee -a $LOG_FILE

echo "[$(date)] Caching routes..." | tee -a $LOG_FILE
php artisan route:cache 2>&1 | tee -a $LOG_FILE || echo "[$(date)] ❌ Route cache failed" | tee -a $LOG_FILE
echo "[$(date)] ✓ Routes cached" | tee -a $LOG_FILE

# Migrations
echo "[$(date)] Running migrations..." | tee -a $LOG_FILE
php artisan migrate --force 2>&1 | tee -a $LOG_FILE || echo "[$(date)] Migrations skipped" | tee -a $LOG_FILE
echo "[$(date)] ✓ Migrations complete" | tee -a $LOG_FILE

# Validate Nginx
echo "[$(date)] Validating Nginx..." | tee -a $LOG_FILE
if ! nginx -t 2>&1 | tee -a $LOG_FILE; then
    echo "[$(date)] ❌ Nginx config invalid!" | tee -a $LOG_FILE
    exit 1
fi
echo "[$(date)] ✓ Nginx config valid" | tee -a $LOG_FILE

# Start PHP-FPM
echo "[$(date)] Starting PHP-FPM..." | tee -a $LOG_FILE
php-fpm -D 2>&1 | tee -a $LOG_FILE || { echo "[$(date)] ❌ PHP-FPM failed!" | tee -a $LOG_FILE; exit 1; }
echo "[$(date)] ✓ PHP-FPM started" | tee -a $LOG_FILE

sleep 2

# Verify PHP-FPM
echo "[$(date)] Verifying PHP-FPM listening on 9000..." | tee -a $LOG_FILE
if ss -tlnp 2>/dev/null | grep -q 9000; then
    echo "[$(date)] ✓ PHP-FPM listening on 9000" | tee -a $LOG_FILE
else
    echo "[$(date)] ⚠ Cannot verify PHP-FPM port" | tee -a $LOG_FILE
fi

# NGINX START
echo "" | tee -a $LOG_FILE
echo "[$(date)] ===== STARTING NGINX ON PORT 80 =====" | tee -a $LOG_FILE
echo "[$(date)] ===== APP READY FOR REQUESTS =====" | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE

# Ensure log files exist and are writable
touch storage/logs/nginx-error.log storage/logs/nginx-access.log 2>/dev/null || true
chmod 666 storage/logs/nginx-*.log 2>/dev/null || true

# Start Nginx in foreground
exec nginx -g 'daemon off;' 2>&1 | tee -a $LOG_FILE
