FROM php:8.2-fpm-alpine3.18

RUN apk add --no-cache \
    libzip \
    libzip-dev \
    freetype \
    jpeg \
    libpng \
    freetype-dev \
    jpeg-dev \
    libpng-dev \
    nodejs \
    npm \
    icu-libs \
    icu-dev \
    zlib-dev \
    bzip2-dev \
    xz-dev \
    pkgconf \
    gcc \
    g++ \
    make \
    supervisor \
    && docker-php-ext-configure zip \
    && docker-php-ext-configure intl \
    && docker-php-ext-install zip pdo pdo_mysql intl bcmath \
    && docker-php-ext-configure gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/ \
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

# RUN php artisan optimize:clear \
#     && php artisan config:cache \
#     && php artisan route:cache \
#     && php artisan view:cache \

# RUN php artisan cache:clear && php artisan config:clear

EXPOSE 9000

COPY docker/start.sh /usr/local/bin/start.sh
RUN chmod +x /usr/local/bin/start.sh

CMD ["/usr/local/bin/start.sh"]