FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader
RUN chmod +x /var/www/docker-entrypoint.sh

EXPOSE 8000

CMD ["sh", "/var/www/docker-entrypoint.sh"]
