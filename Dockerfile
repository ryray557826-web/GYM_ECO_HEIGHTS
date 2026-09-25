FROM richarvey/nginx-php-fpm:latest

# Copy project files into container
COPY . .

# Image & Web Server Settings
ENV WEBROOT /var/www/html/public
ENV PHP_ERRORS_STDERR 1
ENV RUN_SCRIPTS 1
ENV REAL_IP_HEADER 1

# Laravel production settings
ENV APP_ENV production
ENV APP_DEBUG false
ENV LOG_CHANNEL stderr
ENV COMPOSER_ALLOW_SUPERUSER 1

# Start Nginx & PHP-FPM
CMD ["/start.sh"]