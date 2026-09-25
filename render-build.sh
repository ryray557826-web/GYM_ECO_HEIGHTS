#!/usr/bin/env bash
# Exit on error
set -e

echo "Installing composer dependencies..."
composer install --no-dev --optimize-autoloader --no-interaction

echo "Creating empty SQLite database if not present..."
touch database/database.sqlite

echo "Running migrations..."
php artisan migrate --force

echo "Clearing and caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Seeding initial cloud data..."
php artisan firebase:seed || true

echo "Build finished successfully!"