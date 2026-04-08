#!/bin/bash
set -e

cd /var/www/html

echo "==> Installing PHP dependencies..."
composer install --no-interaction

echo "==> Generating app key (if missing)..."
php artisan key:generate --no-interaction 2>/dev/null || true

echo "==> Running migrations..."
php artisan migrate --no-interaction 2>/dev/null || true

echo "==> Starting queue worker..."
php artisan queue:work --tries=3 --timeout=300 --sleep=3 >> /var/log/queue-worker.log 2>&1 &

echo "==> Starting Apache..."
exec apache2-foreground
