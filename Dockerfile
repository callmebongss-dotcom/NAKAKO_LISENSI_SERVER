FROM dunglas/frankenphp:latest

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN install-php-extensions pdo_mysql gd

COPY . /app

WORKDIR /app

RUN composer install --no-dev --optimize-autoloader

EXPOSE 8080

CMD sh -c "php -S 0.0.0.0:${PORT:-8080} -t public public/index.php"
