#!/bin/sh

if [ "${QUEUE_WORKER}" = "true" ]; then
    echo "Starting Queue Worker..."
    exec php /var/www/html/artisan queue:work --tries=3 --timeout=90
fi

if [ "${SCHEDULER}" = "true" ]; then
    echo "Starting Scheduler..."
    exec php /var/www/html/artisan schedule:work
fi

# If no queue or scheduler, start PHP-FPM
echo "Starting PHP-FPM..."
exec php-fpm
