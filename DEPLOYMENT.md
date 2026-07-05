# Deployment Guide

Requirements
- Ubuntu 22.04+
- Nginx
- PHP-FPM 8.4 (php8.4-fpm)
- MySQL
- Composer
- Git

Basic deploy (on server, inside `/var/www/edlibya/backend`):

```bash
git pull origin main
composer install --no-dev --optimize-autoloader --no-interaction
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan storage:link
php artisan queue:restart
sudo systemctl reload php8.4-fpm
```

Queue worker (systemd)
- File: `/etc/systemd/system/edlibya-queue.service`
- Ensure it points to the app `artisan` path and does not force a specific driver.
- After copying the provided service, run:

```bash
sudo systemctl daemon-reload
sudo systemctl enable edlibya-queue
sudo systemctl start edlibya-queue
```

Scheduler (cron)
- Add the Laravel scheduler to crontab on the server (runs every minute):

```bash
* * * * * cd /var/www/edlibya/backend && php artisan schedule:run >> /dev/null 2>&1
```

Notes
- The repository defaults to database-backed cache/session/queue. If you provision Redis, update `.env` and enable `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`.
- Keep `.env` out of version control.
