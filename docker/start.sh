#!/bin/sh

# Clear cache and config before starting the workers
php /var/www/html/artisan config:clear
php /var/www/html/artisan cache:clear
php /var/www/html/artisan config:cache
php /var/www/html/artisan route:clear
php /var/www/html/artisan view:clear

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
