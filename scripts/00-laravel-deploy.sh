#!/usr/bin/env bash

echo "Starting container boot setup..."

# Ensure database file exists and is writable
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Run migrations
php artisan migrate --force

# 1. Restore members from Firebase
php artisan firebase:sync || true

# 2. One-time: populate realistic passes, revenue & attendance
#    (only runs if there are almost no subscriptions yet)
php artisan members:populate --force || true

# Caching
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Container boot setup completed successfully!"