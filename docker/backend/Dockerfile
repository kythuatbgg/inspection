FROM php:8.2-fpm-bookworm

# Install system deps
RUN apt-get update && apt-get install -y --no-install-recommends \
    libpq-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype-dev \
    libicu-dev \
    git \
    curl \
    unzip \
    && rm -rf /var/lib/apt/lists/* \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
    && pecl install apcu && docker-php-ext-enable apcu

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy backend source
COPY app/backend/ .

# Install deps + optimize (skip if not in container yet)
RUN composer install --no-dev --optimize-autoloader --no-interaction \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

EXPOSE 9000

CMD ["php-fpm"]
