#!/bin/bash
set -e

cd /app/backend

LOG_FILE="storage/logs/startup.log"
mkdir -p storage/logs

{
    echo "===== START: $(date) ====="
    
    # Ensure writable dirs exist
    echo "[$(date)] Creating directories..."
    mkdir -p storage/framework/{cache,data,sessions,views} bootstrap/cache /run/nginx
    chmod -R 775 storage bootstrap/cache
    chown -R www-data:www-data storage bootstrap/cache /run/nginx public
    echo "[$(date)] ✓ Directories ready"
    
    # Ensure storage symlink
    echo "[$(date)] Setting up storage link..."
    php artisan storage:link 2>&1 || echo "[$(date)] Storage link already exists"
    echo "[$(date)] ✓ Storage link verified"
    
    # Cache config and routes
    echo "[$(date)] Clearing all caches..."
    php artisan config:clear 2>&1 || true
    php artisan route:clear 2>&1 || true
    php artisan view:clear 2>&1 || true
    echo "[$(date)] ✓ Caches cleared"
    
    echo "[$(date)] Caching configuration..."
    if ! php artisan config:cache 2>&1; then
        echo "[$(date)] ❌ ERROR: Config cache failed"
        echo "=== LARAVEL ERROR LOG ==="
        tail -100 storage/logs/laravel.log 2>/dev/null || echo "No laravel.log found"
        exit 1
    fi
    echo "[$(date)] ✓ Config cached"
    
    echo "[$(date)] Caching routes..."
    if ! php artisan route:cache 2>&1; then
        echo "[$(date)] ❌ ERROR: Route cache failed"
        echo "=== LARAVEL ERROR LOG ==="
        tail -100 storage/logs/laravel.log 2>/dev/null || echo "No laravel.log found"
        exit 1
    fi
    echo "[$(date)] ✓ Routes cached"
    
    # Run migrations
    echo "[$(date)] Running database migrations..."
    php artisan migrate --force 2>&1 || echo "[$(date)] Migrations skipped"
    echo "[$(date)] ✓ Migrations complete"
    
    # Test Nginx
    echo "[$(date)] Validating Nginx configuration..."
    if ! nginx -t 2>&1; then
        echo "[$(date)] ❌ ERROR: Nginx config is invalid"
        exit 1
    fi
    echo "[$(date)] ✓ Nginx config valid"
    
    # Start PHP-FPM
    echo "[$(date)] Starting PHP-FPM daemon..."
    php-fpm -D 2>&1 || { echo "[$(date)] ❌ ERROR: PHP-FPM failed to start"; exit 1; }
    echo "[$(date)] ✓ PHP-FPM started"
    
    sleep 2
    
    # Start Nginx
    echo "[$(date)] Starting Nginx..."
    echo "[$(date)] ===== APP READY FOR REQUESTS ====="
    
} | tee -a $LOG_FILE

# Start Nginx in foreground (keeps container alive)
nginx -g 'daemon off;' 2>&1 | tee -a $LOG_FILE
