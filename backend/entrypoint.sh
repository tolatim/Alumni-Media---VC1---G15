#!/bin/bash

echo "Running Laravel setup..."

cd /var/www

# Fix permissions every time container starts
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Recreate storage symlink if missing
if [ ! -L public/storage ]; then
    php artisan storage:link
fi

# Install vendor if missing (optional)
if [ ! -d vendor ]; then
    composer install
fi

# Clear cache
php artisan config:clear
php artisan cache:clear

# Start PHP-FPM
exec php-fpm
