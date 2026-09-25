FROM richarvey/nginx-php-fpm:latest

# Copy project files
COPY . .

# 1. Install Composer dependencies so vendor/autoload.php exists
RUN composer install --no-dev --optimize-autoloader --no-interaction

# 2. Setup SQLite database, folders, and permissions
RUN touch /var/www/html/database/database.sqlite \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod +x /var/www/html/scripts/*.sh

# Image and server configuration
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1
ENV APP_ENV production
ENV LOG_CHANNEL stderr
ENV COMPOSER_ALLOW_SUPERUSER 1

# Start container using the built-in startup manager
CMD ["/start.sh"]