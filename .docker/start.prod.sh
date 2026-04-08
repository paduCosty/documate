#!/bin/bash
set -e

cd /var/www/html

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Starting queue worker..."
php artisan queue:work --tries=3 --timeout=300 --sleep=3 >> /var/log/queue-worker.log 2>&1 &

echo "==> Starting Apache..."
exec apache2-foreground
