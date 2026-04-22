#!/bin/bash
set -e

cd /var/www/html

echo "==> Installing PHP dependencies..."
composer install --no-interaction

echo "==> Generating app key (if missing)..."
php artisan key:generate --no-interaction 2>/dev/null || true

echo "==> Running migrations..."
php artisan migrate --no-interaction 2>/dev/null || true

echo "==> Seeding system data (idempotent)..."
php artisan db:seed --class=ExtractionTemplateSeeder --no-interaction 2>/dev/null || true
php artisan db:seed --class=AiProviderSeeder --no-interaction 2>/dev/null || true

echo "==> Starting queue worker (auto-restart on failure)..."
(while true; do
    php artisan queue:work --tries=1 --timeout=290 --sleep=3 --max-time=3600
    echo "[$(date)] Queue worker stopped, restarting in 5s..."
    sleep 5
done >> /var/log/queue-worker.log 2>&1) &

echo "==> Starting Apache..."
exec apache2-foreground
