#!/usr/bin/env bash

echo "Starting container boot setup..."

# Ensure database file exists and is writable
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Run migrations
php artisan migrate --force

# RUN TWO-WAY CLOUD SYNC:
# 1. Pushes revenues and expenses into Firebase
# 2. Restores ECO-042 and all members from Firebase
php artisan firebase:sync || true

# Caching configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Container boot setup completed successfully!"