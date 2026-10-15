FROM php:8.3-cli

RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader --no-interaction

RUN touch database/database.sqlite \
    && chmod -R 777 storage bootstrap/cache database

EXPOSE 10000

CMD touch database/database.sqlite && php artisan migrate --force --seed && php artisan serve --host=0.0.0.0 --port=${PORT:-10000}