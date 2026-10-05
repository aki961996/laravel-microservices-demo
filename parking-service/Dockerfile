FROM php:8.5-cli
RUN docker-php-ext-install pdo_mysql
WORKDIR /var/www
CMD php artisan serve --host=0.0.0.0 --port=8000
