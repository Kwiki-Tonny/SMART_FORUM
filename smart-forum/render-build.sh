#!/usr/bin/env bash
# Install Composer
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

# Install dependencies
composer install --no-dev --optimize-autoloader

# Cache Laravel assets
php artisan config:cache
php artisan route:cache
php artisan view:cache