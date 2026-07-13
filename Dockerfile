FROM php:8.2-cli

RUN docker-php-ext-install pdo_mysql pdo_sqlite

COPY . /app

WORKDIR /app

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD php -S 0.0.0.0:${PORT:-8080} -t public public/index.php
