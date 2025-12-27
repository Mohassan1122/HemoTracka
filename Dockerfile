FROM php:8.4-apache

# Set environment variables
ENV APP_ENV=production
ENV APP_DEBUG=false
ENV PORT=80

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    libpq-dev \
    libicu-dev \
    nodejs \
    npm \
    vim \
    htop \
    procps

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_pgsql mbstring exif pcntl bcmath gd zip intl

# Enable Apache modules
RUN a2enmod rewrite headers

# Configure PHP
RUN echo "error_log = /var/log/php_errors.log" > /usr/local/etc/php/conf.d/error-logging.ini && \
    echo "display_errors = On" >> /usr/local/etc/php/conf.d/error-logging.ini && \
    echo "log_errors = On" >> /usr/local/etc/php/conf.d/error-logging.ini

# Configure Apache
ENV APACHE_DOCUMENT_ROOT /var/www/html/public

# Configure apache to use .htaccess files
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Update the default apache site with the config we want
RUN { \
    echo '<VirtualHost *:80>'; \
    echo '    DocumentRoot ${APACHE_DOCUMENT_ROOT}'; \
    echo '    <Directory ${APACHE_DOCUMENT_ROOT}>'; \
    echo '        Options Indexes FollowSymLinks'; \
    echo '        AllowOverride All'; \
    echo '        Require all granted'; \
    echo '        FallbackResource /index.php'; \
    echo '    </Directory>'; \
    echo '    ErrorLog ${APACHE_LOG_DIR}/error.log'; \
    echo '    CustomLog ${APACHE_LOG_DIR}/access.log combined'; \
    echo '    <FilesMatch \.php$>'; \
    echo '        SetHandler application/x-httpd-php'; \
    echo '    </FilesMatch>'; \
    echo '</VirtualHost>'; \
} > /etc/apache2/sites-available/000-default.conf

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy only composer files first for better caching
COPY composer.json composer.lock ./

# Install PHP dependencies
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-interaction --optimize-autoloader --no-dev

# Copy the rest of the application
COPY . .

# Install JS dependencies and build assets
RUN if [ -f "package.json" ]; then \
    npm install && npm run build; \
    fi

# Set permissions
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Create storage directories if they don't exist
RUN mkdir -p /var/www/html/storage/framework/{sessions,views,cache} && \
    mkdir -p /var/www/html/storage/logs

# Create a test PHP file
RUN echo "<?php phpinfo(); ?>" > /var/www/html/public/test.php

# Health check
HEALTHCHECK --interval=30s --timeout=3s \
    CMD curl -f http://localhost/ || exit 1

# Expose port 80
EXPOSE 80

# Start script
COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh
ENTRYPOINT ["docker-entrypoint.sh"]

# Start Apache
CMD ["apache2-foreground"]
