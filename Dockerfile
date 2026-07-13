FROM dunglas/frankenphp:latest

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN install-php-extensions pdo_mysql gd

COPY . /app

WORKDIR /app

RUN composer install --no-dev --optimize-autoloader

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/frankenphp", "run", "--config", "/app/Caddyfile"]
