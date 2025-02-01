#!/bin/sh

# Run the queue worker if QUEUE_WORKER is set
if [ "${QUEUE_WORKER}" = "true" ]; then
    echo "Starting Queue Worker..."
    exec php /var/www/html/artisan queue:work --tries=3 --timeout=90
fi

# Run the scheduler if SCHEDULER is set
if [ "${SCHEDULER}" = "true" ]; then
    echo "Starting Scheduler..."
    exec php /var/www/html/artisan schedule:work
fi

# Run PHP-FPM by default
echo "Starting PHP-FPM..."
exec php-fpm 