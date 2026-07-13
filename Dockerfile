FROM dunglas/frankenphp:latest

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

RUN install-php-extensions pdo_mysql gd

COPY . /app

WORKDIR /app

RUN composer install --no-dev --optimize-autoloader

ENV SERVER_NAME=:80

EXPOSE 80

CMD ["frankenphp", "run"]
