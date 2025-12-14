#!/bin/bash

cd /app/backend

LOG_FILE="storage/logs/startup.log"
mkdir -p storage/logs

# Ensure .env exists - create from environment variables if needed
if [ ! -f .env ]; then
    echo "[$(date)] Creating .env from environment variables..." | tee -a $LOG_FILE
    {
        echo "APP_NAME=\"${APP_NAME:-Acie Fraiche Cafe}\""
        echo "APP_ENV=${APP_ENV:-production}"
        echo "APP_DEBUG=${APP_DEBUG:-false}"
        echo "APP_URL=${APP_URL:-https://afc.com.ng}"
        echo "APP_KEY=${APP_KEY:-base64:FjOkA8pS+80LCAG9Dk8ufkH3PcDn8VY3GMLlfdpt2wg=}"
        echo ""
        echo "LOG_CHANNEL=stack"
        echo "LOG_LEVEL=${LOG_LEVEL:-debug}"
        echo ""
        echo "DB_CONNECTION=${DB_CONNECTION:-pgsql}"
        echo "DB_HOST=${DB_HOST:-localhost}"
        echo "DB_PORT=${DB_PORT:-5432}"
        echo "DB_DATABASE=${DB_DATABASE:-railway}"
        echo "DB_USERNAME=${DB_USERNAME:-postgres}"
        echo "DB_PASSWORD=${DB_PASSWORD}"
        echo ""
        echo "SESSION_DRIVER=database"
        echo "CACHE_STORE=database"
        echo "BROADCAST_CONNECTION=log"
        echo "QUEUE_CONNECTION=database"
    } > .env
    chmod 644 .env
fi

{
    echo "===== APP STARTUP: $(date) ====="
    
    # Ensure writable dirs exist
    echo "[$(date)] Creating directories..."
    mkdir -p storage/logs storage/framework/{cache,data,sessions,views} bootstrap/cache /run/nginx
    chmod -R 775 storage bootstrap/cache /run/nginx
    chown -R www-data:www-data storage bootstrap/cache /run/nginx public
    chown -R www-data:www-data storage/logs
    echo "[$(date)] ✓ Directories created and permissions set"
    
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
    
} | tee -a $LOG_FILE 2>&1

echo "[$(date)] Testing PHP-FPM connectivity..." | tee -a $LOG_FILE
sleep 2

# Verify PHP-FPM is actually running and listening
echo "[$(date)] Checking PHP-FPM process..." | tee -a $LOG_FILE
if ps aux | grep -v grep | grep php-fpm > /dev/null; then
    echo "[$(date)] ✓ PHP-FPM process running" | tee -a $LOG_FILE
else
    echo "[$(date)] ❌ PHP-FPM process NOT running!" | tee -a $LOG_FILE
    exit 1
fi

# Try to connect to PHP-FPM port
echo "[$(date)] Testing PHP-FPM port 9000..." | tee -a $LOG_FILE
if timeout 3 bash -c "</dev/tcp/127.0.0.1/9000" 2>/dev/null; then
    echo "[$(date)] ✓ Successfully connected to PHP-FPM port 9000" | tee -a $LOG_FILE
else
    echo "[$(date)] ⚠ Could not connect to PHP-FPM port 9000, but continuing..." | tee -a $LOG_FILE
fi

# Final permission check
echo "[$(date)] Final permission checks..." | tee -a $LOG_FILE
chmod -R 777 storage/logs 2>/dev/null || true
chown -R www-data:www-data storage/logs 2>/dev/null || true

echo "[$(date)] Starting Nginx..." | tee -a $LOG_FILE

# Start Nginx and capture any errors
nginx -g 'daemon off;' 2>&1 | tee -a $LOG_FILE &
NGINX_PID=$!
sleep 2

# Check if Nginx is still running
if ! ps -p $NGINX_PID > /dev/null 2>&1; then
    echo "[$(date)] ❌ CRITICAL ERROR: Nginx crashed after startup!" | tee -a $LOG_FILE
    echo "" | tee -a $LOG_FILE
    echo "===== NGINX ERROR LOG =====" | tee -a $LOG_FILE
    cat storage/logs/nginx-error.log 2>/dev/null | tee -a $LOG_FILE || echo "No nginx-error.log found" | tee -a $LOG_FILE
    echo "" | tee -a $LOG_FILE
    echo "===== LARAVEL ERROR LOG =====" | tee -a $LOG_FILE
    cat storage/logs/laravel.log 2>/dev/null | tail -100 | tee -a $LOG_FILE || echo "No laravel.log found" | tee -a $LOG_FILE
    echo "" | tee -a $LOG_FILE
    echo "===== LAST 20 LINES OF STARTUP LOG =====" | tee -a $LOG_FILE
    tail -20 $LOG_FILE | tee -a $LOG_FILE
    exit 1
fi

# Verify Nginx is listening on port 80
echo "[$(date)] Verifying Nginx is listening on port 80..." | tee -a $LOG_FILE
LISTEN_CHECK=$(ss -tlnp 2>/dev/null | grep ':80 ')
if [ -n "$LISTEN_CHECK" ]; then
    echo "[$(date)] ✓ Nginx is listening on port 80" | tee -a $LOG_FILE
    echo "$LISTEN_CHECK" | tee -a $LOG_FILE
else
    echo "[$(date)] ❌ ERROR: Nginx is NOT listening on port 80!" | tee -a $LOG_FILE
    echo "" | tee -a $LOG_FILE
    echo "===== NGINX PROCESS INFO =====" | tee -a $LOG_FILE
    ps aux | grep nginx | grep -v grep | tee -a $LOG_FILE
    echo "" | tee -a $LOG_FILE
    echo "===== ALL LISTENING PORTS =====" | tee -a $LOG_FILE
    ss -tlnp 2>/dev/null | tee -a $LOG_FILE
    echo "" | tee -a $LOG_FILE
    echo "===== NGINX ERROR LOG =====" | tee -a $LOG_FILE
    cat storage/logs/nginx-error.log 2>/dev/null | tee -a $LOG_FILE || echo "No nginx-error.log found" | tee -a $LOG_FILE
    exit 1
fi

echo "[$(date)] ===== APP IS READY FOR REQUESTS =====" | tee -a $LOG_FILE
echo "[$(date)] Nginx PID: $NGINX_PID" | tee -a $LOG_FILE

# Test if the app actually responds
echo "[$(date)] Testing app health..." | tee -a $LOG_FILE
sleep 1
if curl -s -f http://127.0.0.1/health > /dev/null 2>&1; then
    echo "[$(date)] ✓ App health check passed" | tee -a $LOG_FILE
elif curl -s http://127.0.0.1/ > /dev/null 2>&1; then
    echo "[$(date)] ✓ App responds to requests" | tee -a $LOG_FILE
else
    echo "[$(date)] ⚠ App may not be responding to requests" | tee -a $LOG_FILE
fi

# Wait for Nginx to exit (should run forever)
wait $NGINX_PID
EXIT_CODE=$?
echo "[$(date)] ❌ Nginx exited with code $EXIT_CODE" | tee -a $LOG_FILE
echo "" | tee -a $LOG_FILE
echo "===== NGINX ERROR LOG =====" | tee -a $LOG_FILE
cat storage/logs/nginx-error.log 2>/dev/null | tee -a $LOG_FILE || echo "No nginx-error.log" | tee -a $LOG_FILE
exit 1
