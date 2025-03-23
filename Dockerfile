FROM php:8.4-fpm

RUN apt update && apt install -y \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    nodejs \
    npm \
    libicu-dev \
    zlib1g-dev \
    bzip2 \
    xz-utils \
    pkg-config \
    gcc \
    g++ \
    make \
    supervisor \
    && docker-php-ext-configure zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install zip pdo pdo_mysql intl bcmath \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && docker-php-ext-enable gd

COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY . .

# Create supervisor config directory
RUN mkdir -p /etc/supervisor.d/

# Copy supervisor configuration
COPY docker/supervisor/supervisord.conf /etc/supervisord.conf
COPY docker/supervisor/conf.d/ /etc/supervisor.d/

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

COPY composer.json composer.lock /var/www/html/

RUN composer install --optimize-autoloader

# Optimize and cache for production
# RUN php artisan optimize:clear \
#     && php artisan config:cache \
#     && php artisan route:cache \
#     && php artisan view:cache \
#     && php artisan filament:assets

# Set proper permissions for public directory
RUN chown -R www-data:www-data /var/www/html/public \
    && chmod -R 775 /var/www/html/public

EXPOSE 9000

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

RUN php artisan storage:link

CMD ["/usr/local/bin/start.sh"]