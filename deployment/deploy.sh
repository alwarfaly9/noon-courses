#!/usr/bin/env bash
# ============================================================
# EdLibya Production Deployment Script
# ============================================================
# Usage: bash deploy.sh
# Run from the project root on the production server.
# ============================================================

set -euo pipefail

APP_DIR="/var/www/edlibya/backend"
RELEASE_DATE=$(date +%Y%m%d_%H%M%S)

echo "=========================================="
echo "  EdLibya Deployment — $RELEASE_DATE"
echo "=========================================="

cd "$APP_DIR"

# 1. Pull latest code
echo "[1/10] Pulling latest code..."
git pull origin main

# 2. Install PHP dependencies (no dev)
echo "[2/10] Installing Composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

# 3. Run migrations
echo "[3/10] Running database migrations..."
php artisan migrate --force

# 4. Clear and rebuild caches
echo "[4/10] Optimizing application..."
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# 5. Link storage
echo "[5/10] Linking storage..."
php artisan storage:link 2>/dev/null || true

# 6. Restart queue workers
echo "[6/10] Restarting queue workers..."
php artisan queue:restart

# 7. Restart PHP-FPM
echo "[7/10] Restarting PHP-FPM..."
# Use php8.4-fpm on target servers. Adjust if different.
sudo systemctl reload php8.4-fpm

# 8. Verify health
echo "[8/10] Health check..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost/up)
if [ "$HTTP_CODE" = "200" ]; then
    echo "  ✅ Health check passed"
else
    echo "  ❌ Health check failed (HTTP $HTTP_CODE)"
    echo "  Rolling back..."
    php artisan config:clear
    php artisan route:clear
    exit 1
fi

# 9. Set permissions
echo "[9/10] Setting permissions..."
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

echo "[10/10] ✅ Deployment complete!"
echo ""
echo "Post-deployment reminders:"
echo "  - Monitor logs: tail -f storage/logs/laravel.log"
echo "  - Queue status: sudo systemctl status edlibya-queue"
echo "  - Nginx status: sudo systemctl status nginx"
