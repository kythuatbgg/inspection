#!/usr/bin/env bash
# =============================================================================
# FBB Inspection — VPS Setup Script (Direct PHP - No Docker)
# Run from local: ssh -i ~/.ssh/fbb_vps root@180.93.42.138 'bash -s' < scripts/vps-setup-direct.sh
# =============================================================================

set -euo pipefail

DOMAIN="inspector.quandh.online"
APP_DIR="/www/wwwroot/${DOMAIN}"
VESTACP_VHOST="/www/server/panel/vhost/nginx"
DB_NAME="database.sqlite"
PHP_VERSION="83" # aaPanel PHP 8.3 path

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"; }

# 1. Checking PHP extensions
log "=== 1. Checking PHP extensions ==="
# aaPanel PHP extensions are generally managed via panel, but doing a quick check
if [ -d "/www/server/php/${PHP_VERSION}/bin/" ]; then
    PHP_CMD="/www/server/php/${PHP_VERSION}/bin/php"
else
    PHP_CMD="php"
fi

# (No DB setup needed here, SQLite file is created during deploy)

# 3. Setup Directories
log "=== 3. Setup app directories ==="
mkdir -p "$APP_DIR/api"
chown -R www:www "$APP_DIR" || true  # aaPanel uses www:www

# 4. Setup Nginx vhost
log "=== 4. Setting up Nginx vhost ==="
cat > "${VESTACP_VHOST}/${DOMAIN}.conf" << VHOST
server {
    listen 80;
    server_name ${DOMAIN};
    root ${APP_DIR};
    index index.html;

    # Frontend Vue Router
    location / {
        try_files \$uri \$uri/ /index.html;
    }

    # Backend API (Laravel)
    location /api/ {
        alias ${APP_DIR}/api/public/;
        try_files \$uri \$uri/ @laravel;

        # Serve PHP
        location ~ \.php$ {
            fastcgi_pass  unix:/tmp/php-cgi-${PHP_VERSION}.sock;
            fastcgi_index index.php;
            include fastcgi.conf;
            fastcgi_param SCRIPT_FILENAME \$request_filename;
        }
    }

    location @laravel {
        rewrite /api/(.*)$ /api/index.php?/\$1 last;
    }

    # Laravel Storage
    location /storage/ {
        alias ${APP_DIR}/api/storage/app/public/;
    }

    location ^~ /.well-known/acme-challenge/ {
        root ${APP_DIR};
        allow all;
    }
}
VHOST

nginx -t && nginx -s reload || true

# 5. SSL
log "=== 5. SSL Generation ==="
if [ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
    certbot certonly --webroot -w "$APP_DIR" -d "$DOMAIN" --non-interactive --no-eff-email --agree-tos --email quandhonline@gmail.com || log "Certbot failed, perhaps DNS not pointed?"
fi

if [ -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
    cat > "${VESTACP_VHOST}/${DOMAIN}.conf" << VHOST
server {
    listen 80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    server_name ${DOMAIN};
    root ${APP_DIR};
    index index.html;

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location /api/ {
        alias ${APP_DIR}/api/public/;
        try_files \$uri \$uri/ @laravel;

        location ~ \.php$ {
            fastcgi_pass  unix:/tmp/php-cgi-${PHP_VERSION}.sock;
            fastcgi_index index.php;
            include fastcgi.conf;
            fastcgi_param SCRIPT_FILENAME \$request_filename;
        }
    }

    location @laravel {
        rewrite /api/(.*)$ /api/index.php?/\$1 last;
    }

    location /storage/ {
        alias ${APP_DIR}/api/storage/app/public/;
    }
}
VHOST
    nginx -t && nginx -s reload || true
fi

log "=== Setup Complete! ==="
log "Please run deploy.ps1 locally now."
