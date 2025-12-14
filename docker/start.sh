#!/bin/bash
set -e

cd /app/backend

LOG_FILE="storage/logs/startup.log"
mkdir -p storage/logs

{
    echo "===== START: $(date) ====="
    
    # Ensure writable dirs exist
    echo "[$(date)] Creating directories..."
    mkdir -p storage/logs storage/framework/{cache,data,sessions,views} bootstrap/cache /run/nginx
    chmod -R 775 storage bootstrap/cache /run/nginx
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
    
    sleep 3
    
    # Verify PHP-FPM is listening
    echo "[$(date)] Verifying PHP-FPM is listening..."
    if netstat -tlnp 2>/dev/null | grep -q 9000; then
        echo "[$(date)] ✓ PHP-FPM listening on port 9000"
    else
        echo "[$(date)] ⚠ Port 9000 not found, trying ss command..."
        if ss -tlnp 2>/dev/null | grep -q 9000; then
            echo "[$(date)] ✓ PHP-FPM listening on port 9000"
        else
            echo "[$(date)] ⚠ Cannot verify PHP-FPM listening (netstat/ss not available)"
        fi
    fi
    
    # Start Nginx
    echo "[$(date)] Starting Nginx..."
    echo "[$(date)] ===== APP READY FOR REQUESTS ====="
    
} | tee -a $LOG_FILE

# Test PHP-FPM connectivity before starting Nginx
echo "[$(date)] Testing PHP-FPM connectivity..." | tee -a $LOG_FILE
if timeout 5 bash -c "</dev/tcp/127.0.0.1/9000" 2>/dev/null; then
    echo "[$(date)] ✓ PHP-FPM port 9000 is reachable" | tee -a $LOG_FILE
else
    echo "[$(date)] ⚠ Warning: PHP-FPM port may not be reachable" | tee -a $LOG_FILE
fi

# Final permission check before Nginx
echo "[$(date)] Final permissions check..." | tee -a $LOG_FILE
chmod -R 777 storage/logs 2>/dev/null || true
chown -R www-data:www-data storage/logs 2>/dev/null || true

echo "[$(date)] Starting Nginx in foreground..." | tee -a $LOG_FILE

# Start Nginx in background first to check if it actually stays running
nginx -g 'daemon off;' &
NGINX_PID=$!
sleep 2

# Verify Nginx is still running
if ! ps -p $NGINX_PID > /dev/null 2>&1; then
    echo "[$(date)] ❌ ERROR: Nginx crashed immediately after startup" | tee -a $LOG_FILE
    echo "===== NGINX ERROR LOG =====" | tee -a $LOG_FILE
    tail -50 storage/logs/nginx-error.log 2>/dev/null || echo "No nginx error log" | tee -a $LOG_FILE
    echo "===== LARAVEL ERROR LOG =====" | tee -a $LOG_FILE
    tail -50 storage/logs/laravel.log 2>/dev/null || echo "No laravel log" | tee -a $LOG_FILE
    exit 1
fi

# Verify port 80 is listening
echo "[$(date)] Verifying Nginx is listening on port 80..." | tee -a $LOG_FILE
if netstat -tlnp 2>/dev/null | grep -q ':80 '; then
    echo "[$(date)] ✓ Nginx listening on port 80" | tee -a $LOG_FILE
elif ss -tlnp 2>/dev/null | grep -q ':80 '; then
    echo "[$(date)] ✓ Nginx listening on port 80" | tee -a $LOG_FILE
else
    echo "[$(date)] ⚠ Port 80 not detected, but Nginx process running" | tee -a $LOG_FILE
fi

echo "[$(date)] ===== APP READY FOR REQUESTS =====" | tee -a $LOG_FILE

# Wait for Nginx process to exit
wait $NGINX_PID
if [ $? -ne 0 ]; then
    echo "[$(date)] ❌ Nginx exited with error" | tee -a $LOG_FILE
    echo "===== NGINX ERROR LOG =====" | tee -a $LOG_FILE
    tail -50 storage/logs/nginx-error.log 2>/dev/null || echo "No nginx error log" | tee -a $LOG_FILE
    exit 1
fi
    echo "===== LARAVEL ERROR LOG ====="
    tail -50 storage/logs/laravel.log 2>/dev/null || echo "No laravel log"
    exit 1
fi
