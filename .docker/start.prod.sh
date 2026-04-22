#!/bin/bash
set -e

cd /var/www/html

echo "==> Running migrations..."
php artisan migrate --force

echo "==> Caching config, routes, views..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "==> Seeding system data (idempotent)..."
php artisan db:seed --class=ExtractionTemplateSeeder --force
php artisan db:seed --class=AiProviderSeeder --force

echo "==> Starting queue worker (auto-restart on failure)..."
(while true; do
    php artisan queue:work --tries=1 --timeout=290 --sleep=3 --max-time=3600
    echo "[$(date)] Queue worker stopped, restarting in 5s..."
    sleep 5
done >> /var/log/queue-worker.log 2>&1) &

echo "==> Starting Apache..."
exec apache2-foreground
