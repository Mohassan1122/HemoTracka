#!/bin/bash
set -e

cd /var/www/html

# Create .env file if it doesn't exist
if [ ! -f .env ]; then
    cat > .env << EOF
APP_NAME=HemoTracka
APP_ENV=production
APP_DEBUG=false
APP_URL=http://localhost
APP_KEY=
DB_CONNECTION=pgsql
DB_HOST=${DB_HOST:-db}
DB_PORT=${DB_PORT:-5432}
DB_DATABASE=${DB_DATABASE:-hemotracka}
DB_USERNAME=${DB_USERNAME:-postgres}
DB_PASSWORD=${DB_PASSWORD:-}
CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_DRIVER=sync
MAIL_DRIVER=smtp
MAIL_HOST=${MAIL_HOST:-smtp.mailtrap.io}
MAIL_PORT=${MAIL_PORT:-2525}
MAIL_USERNAME=${MAIL_USERNAME:-null}
MAIL_PASSWORD=${MAIL_PASSWORD:-null}
MAIL_ENCRYPTION=null
EOF
    php artisan key:generate
fi

# Set permissions
chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create necessary directories if they don't exist
mkdir -p /var/www/html/storage/framework/{sessions,views,cache}
mkdir -p /var/www/html/storage/logs

# Create storage symlink if it doesn't exist
if [ ! -L /var/www/html/public/storage ]; then
    php artisan storage:link
fi

# Create upload directories if they don't exist
mkdir -p /var/www/html/storage/app/public/profile_pictures
mkdir -p /var/www/html/storage/app/public/organization_logos
mkdir -p /var/www/html/storage/app/public/organization_covers
chown -R www-data:www-data /var/www/html/storage/app/public
chmod -R 775 /var/www/html/storage/app/public

# Cache configuration
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Run migrations
php artisan migrate --force

php artisan queue:work

# Run seeders
php artisan db:seed --force

# Start supervisor (manages both PHP-FPM and Apache)
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf