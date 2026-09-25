#!/usr/bin/env bash
echo "Ensuring SQLite database exists..."
touch /var/www/html/database/database.sqlite

echo "Running migrations..."
php artisan migrate --force

echo "Caching configurations..."
php artisan config:cache
php artisan route:cache
php artisan view:cache