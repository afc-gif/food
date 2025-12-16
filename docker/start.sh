#!/bin/bash

cd /app/backend

LOG_FILE="storage/logs/startup.log"
mkdir -p storage/logs

echo "===== APP STARTUP: $(date) =====" | tee $LOG_FILE

# Ensure PORT is set (Railway injects it)
PORT=${PORT:-80}

# Resolve DB settings up front so we can reuse them below.
DB_HOST_VALUE=${DB_HOST:-${PGHOST:-${RENDER_EXTERNAL_DB_HOST:-}}}
DB_PORT_VALUE=${DB_PORT:-${PGPORT:-5432}}
DB_DATABASE_VALUE=${DB_DATABASE:-${PGDATABASE:-railway}}
DB_USERNAME_VALUE=${DB_USERNAME:-${PGUSER:-postgres}}
DB_PASSWORD_VALUE=${DB_PASSWORD:-${PGPASSWORD:-}}

# Ensure .env exists
if [ ! -f .env ]; then
    echo "[$(date)] Creating .env from container environment..." | tee -a $LOG_FILE
    cat > .env << EOF
APP_NAME="${APP_NAME:-Acie Fraiche Cafe}"
APP_ENV=${APP_ENV:-production}
APP_DEBUG=${APP_DEBUG:-false}
APP_URL=${APP_URL:-https://food-app-production-e680.up.railway.app/}
APP_KEY=${APP_KEY:-base64:FjOkA8pS+80LCAG9Dk8ufkH3PcDn8VY3GMLlfdpt2wg=}
LOG_CHANNEL=${LOG_CHANNEL:-stack}
LOG_LEVEL=${LOG_LEVEL:-debug}

DB_CONNECTION=${DB_CONNECTION:-pgsql}
DB_HOST=${DB_HOST_VALUE}
DB_PORT=${DB_PORT_VALUE}
DB_DATABASE=${DB_DATABASE_VALUE}
DB_USERNAME=${DB_USERNAME_VALUE}
DB_PASSWORD=${DB_PASSWORD_VALUE}

SESSION_DRIVER=${SESSION_DRIVER:-database}
CACHE_STORE=${CACHE_STORE:-database}
BROADCAST_CONNECTION=${BROADCAST_CONNECTION:-log}
QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}
EOF
    chmod 640 .env
fi

# Render nginx config with PORT
echo "[$(date)] Rendering nginx config with PORT=$PORT..." | tee -a $LOG_FILE
if command -v envsubst >/dev/null 2>&1; then
    envsubst '$PORT' < /etc/nginx/nginx.conf > /tmp/nginx.conf && mv /tmp/nginx.conf /etc/nginx/nginx.conf
else
    # Fallback if envsubst is unavailable
    sed -i "s/\\\${PORT}/${PORT}/g" /etc/nginx/nginx.conf
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
php artisan config:clear 2>&1 | tee -a $LOG_FILE || true
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

# Migrations (opt-in with retry to tolerate slow DB start)
if [ "${RUN_MIGRATIONS:-true}" != "false" ]; then
    echo "[$(date)] Running migrations (retries=${MIGRATE_RETRIES:-5})..." | tee -a $LOG_FILE
    attempts=0
    until php artisan migrate --force 2>&1 | tee -a $LOG_FILE; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge "${MIGRATE_RETRIES:-5}" ]; then
            echo "[$(date)] ❌ Migrations failed after $attempts attempts" | tee -a $LOG_FILE
            break
        fi
        echo "[$(date)] Migrations failed; retrying in ${MIGRATE_BACKOFF:-3}s (attempt $attempts)" | tee -a $LOG_FILE
        sleep "${MIGRATE_BACKOFF:-3}"
    done
    echo "[$(date)] ✓ Migrations complete (attempts=$attempts)" | tee -a $LOG_FILE
else
    echo "[$(date)] Skipping migrations because RUN_MIGRATIONS=false" | tee -a $LOG_FILE
fi

# Validate Nginx (after rendering)
echo "[$(date)] Validating Nginx (PORT=$PORT)..." | tee -a $LOG_FILE
if ! nginx -t 2>&1 | tee -a $LOG_FILE; then
    echo "[$(date)] ❌ Nginx config invalid!" | tee -a $LOG_FILE
    exit 1
fi
echo "[$(date)] ✓ Nginx config valid" | tee -a $LOG_FILE

# Start PHP-FPM
echo "[$(date)] Starting PHP-FPM..." | tee -a $LOG_FILE
php-fpm -D 2>&1 | tee -a $LOG_FILE || { echo "[$(date)] ❌ PHP-FPM failed!" | tee -a $LOG_FILE; exit 1; }
echo "[$(date)] ✓ PHP-FPM started" | tee -a $LOG_FILE

# Log checkpoint before sleep
echo "[$(date)] [CHECKPOINT] Before sleep" | tee -a $LOG_FILE

# Flush output
sync
echo "[$(date)] [CHECKPOINT] After sync, sleeping for 1 second..." | tee -a $LOG_FILE
sleep 1
echo "[$(date)] [CHECKPOINT] After sleep, about to start Nginx" | tee -a $LOG_FILE

# NGINX START - skip verification, just start
{
    echo ""
    echo "[$(date)] ===== STARTING NGINX ON PORT 0.0.0.0:${PORT:-80} ====="
    echo "[$(date)] ===== APP READY FOR REQUESTS ====="
    echo ""
} | tee -a $LOG_FILE

# Ensure log files exist and are writable
touch storage/logs/nginx-error.log storage/logs/nginx-access.log 2>/dev/null || true
chmod 666 storage/logs/nginx-*.log 2>/dev/null || true

# Start Nginx in foreground - replace this process
echo "[$(date)] [CHECKPOINT] Executing: nginx -g 'daemon off;'" | tee -a $LOG_FILE
exec nginx -g 'daemon off;'
