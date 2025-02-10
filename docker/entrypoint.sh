#!/bin/sh

# Create SQLite database directory if it doesn't exist
mkdir -p /var/www/html/database

# Create SQLite database if it doesn't exist
touch /var/www/html/database/database.sqlite
chmod 666 /var/www/html/database/database.sqlite

# Execute the passed command
exec "$@" 