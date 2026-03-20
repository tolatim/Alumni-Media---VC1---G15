FROM php:8.2-cli

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip libzip-dev libonig-dev \
    && docker-php-ext-install pdo_mysql mbstring zip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

EXPOSE 8000

CMD ["sh", "-c", "php -r \"if (!file_exists('.env')) { if (file_exists('.env.docker')) { copy('.env.docker', '.env'); } else { copy('.env.example', '.env'); } }\" && if ! grep -q '^APP_KEY=base64:' .env; then php artisan key:generate --force --no-interaction; fi && php artisan config:clear && php artisan cache:clear && (php artisan storage:link || true) && php artisan migrate --force --no-interaction && php artisan serve --host=0.0.0.0 --port=8000"]
