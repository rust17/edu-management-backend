#!/bin/bash

# Wait for the database connection to be ready
echo "Waiting for database connection..."
while ! php artisan db:monitor > /dev/null 2>&1; do
    sleep 1
done

php artisan config:cache
php artisan route:cache

# Check if migrations need to be run
php artisan migrate:status | grep "No migrations found" > /dev/null 2>&1
NEED_MIGRATION=$?

if [ $NEED_MIGRATION -eq 0 ] || [ "$FORCE_MIGRATION" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate
fi

# Check if Passport needs to be installed
if [ "$PASSPORT_INSTALLED" = "true" ]; then
    echo "Installing Passport..."
    php artisan passport:install --force
    echo "Passport installation completed."
else
    echo "Passport already installed."
fi

# Start PHP-FPM
php-fpm --fpm-config /usr/local/etc/php-fpm.d/www.conf
echo "PHP-FPM started"

# Start Nginx
nginx -g "daemon off;"
if [ $? -ne 0 ]; then
    echo "Failed to start Nginx"
    exit 1
fi
