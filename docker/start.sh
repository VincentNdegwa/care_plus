#!/bin/sh

# Create SQLite database if it doesn't exist
mkdir -p /var/www/html/database
touch /var/www/html/database/database.sqlite
chmod 666 /var/www/html/database/database.sqlite

# Clear config cache
php artisan config:clear

# Run migrations for cache if needed
php artisan migrate --database=sqlite --force

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
