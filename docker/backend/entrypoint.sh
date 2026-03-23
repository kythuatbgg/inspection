#!/bin/sh
set -eu

cd /var/www/html

mkdir -p \
  storage/framework/cache/data \
  storage/framework/sessions \
  storage/framework/testing \
  storage/framework/views \
  storage/logs \
  bootstrap/cache

chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache

if [ ! -L public/storage ]; then
  php artisan storage:link >/dev/null 2>&1 || true
fi

exec sh -c 'caddy run --config /etc/caddy/Caddyfile & php-fpm'