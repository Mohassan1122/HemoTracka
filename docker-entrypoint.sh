#!/bin/bash
set -e

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create necessary directories if they don't exist
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs

# Generate application key if not set
if [ -z "$(grep 'APP_KEY=base64' .env)" ]; then
    php artisan key:generate
fi

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations if needed
if [ "$RUN_MIGRATIONS" = "true" ]; then
    php artisan migrate --force
fi

# Start Apache
exec apache2-foreground
