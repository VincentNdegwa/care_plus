#!/usr/bin/env bash
echo "Running composer"
composer global require hirak/prestissimo
composer install --no-dev --working-dir=/var/www/html

echo "generating application key..."
php artisan key:generate --show

echo "Caching config..."
php artisan config:cache

echo "Clearing cache..."
php artisan cache:clear

echo "Clearing sessions..."
php artisan session:clear

echo "Clearing view cache..."
php artisan view:clear

echo "Clearing compiled views..."
php artisan view:clear

echo "Caching routes..."
php artisan route:cache


echo "Running migrations..."
php artisan migrate --force
