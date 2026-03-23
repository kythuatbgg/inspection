FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    postgresql-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    git \
    curl \
    unzip \
    oniguruma-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pcntl bcmath gd zip intl exif

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY backend/ .
RUN composer install --no-dev --no-interaction --ignore-platform-reqs \
    && mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Caddy web server (serves :80, proxies to php-fpm :9000)
COPY docker/backend/Caddyfile /etc/caddy/Caddyfile
COPY --from=caddy:2 /usr/bin/caddy /usr/bin/caddy
COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
CMD ["/usr/local/bin/entrypoint.sh"]
