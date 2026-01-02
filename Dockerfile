
# Use PHP FPM base image
FROM php:8.4-fpm

# Install Apache and required modules
RUN apt-get update \
    && apt-get install -y --no-install-recommends apache2 libapache2-mod-fcgid libpng-dev libjpeg-dev libfreetype6-dev libzip-dev libonig-dev libxml2-dev libicu-dev libpq-dev git unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd intl pdo_pgsql pdo pdo_mysql zip mbstring xml \
    && a2enmod rewrite headers proxy proxy_fcgi setenvif \
    && a2enmod mpm_prefork \
    && a2dismod mpm_event mpm_worker mpm_itk mpm_threadpool || true \
    && rm -rf /var/lib/apt/lists/*


# Ensure Apache log directory exists
RUN mkdir -p /var/log/apache2

# Configure Apache to use PHP-FPM
RUN echo "<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    <FilesMatch \.php$>\n\
        SetHandler \"proxy:unix:/var/run/php/php-fpm.sock|fcgi://localhost/\"\n\
    </FilesMatch>\n\
    ErrorLog /var/log/apache2/error.log\n\
    CustomLog /var/log/apache2/access.log combined\n\
</VirtualHost>" > /etc/apache2/sites-available/000-default.conf

# Configure PHP-FPM to use a Unix socket
RUN mkdir -p /var/run/php && \
    sed -i 's|listen = .*|listen = /var/run/php/php-fpm.sock|' /usr/local/etc/php-fpm.d/www.conf


# Copy application code
COPY . /var/www/html

# Install Composer dependencies
WORKDIR /var/www/html
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && composer install --no-interaction --prefer-dist --optimize-autoloader \
    && rm composer-setup.php

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Expose port
EXPOSE 80

# Install supervisor
RUN apt-get update && apt-get install -y supervisor && rm -rf /var/lib/apt/lists/*

# Copy supervisor config
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Start both PHP-FPM and Apache with supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
