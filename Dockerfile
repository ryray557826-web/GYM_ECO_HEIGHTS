FROM richarvey/nginx-php-fpm:latest

# Copy project files
COPY . .

# Image & Web Server Settings
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Production optimizations
ENV APP_ENV production
ENV LOG_CHANNEL stderr
ENV COMPOSER_ALLOW_SUPERUSER 1

# Ensure folders and permissions exist
RUN touch /var/www/html/database/database.sqlite \
    && mkdir -p /var/www/html/storage/logs \
    && mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/database

# Automatically migrate tables, seed the owner account, and launch Nginx
CMD ["/bin/bash", "-c", "touch /var/www/html/database/database.sqlite && php artisan migrate --force && php artisan db:seed --force && chown -R www-data:www-data /var/www/html/database /var/www/html/storage && /start.sh"]