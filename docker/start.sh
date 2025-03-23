#!/bin/sh

# Clear all caches first
php /var/www/html/artisan optimize:clear

rm -rf /var/www/html/storage/framework/views/*


# Publish and build Filament assets
php /var/www/html/artisan filament:assets
php /var/www/html/artisan vendor:publish --tag=filament-config --force

# Cache configurations
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:cache
php /var/www/html/artisan view:cache
php /var/www/html/artisan storage:link

# Set proper permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/html/public

if [ "${QUEUE_WORKER}" = "true" ]; then
    echo "Starting Queue Worker..."
    exec php /var/www/html/artisan queue:work --tries=3 --timeout=90
    exit 0
fi

if [ "${SCHEDULER}" = "true" ]; then
    echo "Starting Scheduler..."
    exec php /var/www/html/artisan schedule:work
    exit 0
fi

# If no queue or scheduler, start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
