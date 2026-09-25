#!/usr/bin/env bash

echo "Starting container boot setup..."

# Ensure database exists and is writable by the web server
touch /var/www/html/database/database.sqlite
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Run migrations and seed the owner account
php artisan migrate --force
php artisan db:seed --force || true

# Cache configurations
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "Container boot setup completed successfully!"