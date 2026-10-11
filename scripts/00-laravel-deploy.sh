#!/usr/bin/env bash

echo "Starting container boot setup..."

# Ensure database file exists and is writable by web server
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Run migrations safely without wiping data
php artisan migrate --force

# Caching configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Container boot setup completed successfully!"