#!/usr/bin/env bash
# =============================================================================
# FBB Inspection — VPS Setup Script (single repo)
# Run: ssh -i ~/.ssh/fbb_vps root@180.93.42.138 'bash -s' < this_script
# =============================================================================

set -euo pipefail

APP_DIR="/opt/fbb-inspection"
DOMAIN="inspector.quandh.online"
VESTACP_VHOST="/www/server/panel/vhost/nginx"
WWWROOT="/www/wwwroot/${DOMAIN}"
POSTGRES_PASSWORD="${POSTGRES_PASSWORD:-lTPxmtO0jLjV83+/wDXs3ULIm1KkkYM1}"

echo "=== 1. Setup directories ==="
mkdir -p "$APP_DIR"/{repo.git,frontend.dist}
mkdir -p "$WWWROOT"

echo "=== 2. Clone repo (SSH) ==="
if [ ! -d "$APP_DIR/repo.git" ]; then
  git clone --bare git@github.com:kythuatbgg/inspection.git "$APP_DIR/repo.git"
fi

# Post-receive hook: pull + build + migrate
cat > "$APP_DIR/repo.git/hooks/post-receive" << 'HOOK'
#!/bin/bash
set -euo pipefail
APP_DIR="/opt/fbb-inspection"
POSTGRES_PASSWORD="${POSTGRES_PASSWORD:-lTPxmtO0jLjV83+/wDXs3ULIm1KkkYM1}"

# Pull latest into working tree
GIT_WORK_TREE="$APP_DIR/app" git checkout -f main

# Rebuild backend + frontend
cd "$APP_DIR"
POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose build --pull
POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --build

# Run migrations if needed
docker compose exec -T backend php artisan migrate --force 2>/dev/null || true

# Copy new frontend dist to nginx wwwroot
docker cp "$(docker compose ps -q frontend):/usr/share/nginx/html/." "$APP_DIR/frontend.dist/"

echo "[$(date)] Deployed successfully."
HOOK
chmod +x "$APP_DIR/repo.git/hooks/post-receive"

echo "=== 3. Checkout first deploy ==="
GIT_WORK_TREE="$APP_DIR/app" git checkout -f main

echo "=== 4. Copy Docker files ==="
cp -r "$APP_DIR/app/docker/" "$APP_DIR/"
cp "$APP_DIR/app/docker-compose.yml" "$APP_DIR/"

echo "=== 5. Start containers (first-time) ==="
cd "$APP_DIR"
POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --build postgres backend

echo "Waiting for PostgreSQL..."
sleep 15

echo "=== 6. Run Laravel migrations ==="
docker compose exec -T backend php artisan migrate --force

echo "=== 7. Build frontend + copy to wwwroot ==="
docker compose up -d --build frontend
sleep 5
docker cp "$(docker compose ps -q frontend):/usr/share/nginx/html/." "$WWWROOT/"

echo "=== 8. Setup Nginx vhost (HTTP) ==="
cat > "${VESTACP_VHOST}/${DOMAIN}.conf" << VHOST
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};

    root ${WWWROOT};
    index index.html;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto http;
        proxy_connect_timeout 60s;
        proxy_read_timeout 60s;
    }

    location ~* \\.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
}
VHOST

echo "=== 9. SSL certbot (if not exists) ==="
if [ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
    mkdir -p "$WWWROOT/.well-known/acme-challenge"
    certbot certonly --webroot -w "$WWWROOT" -d "$DOMAIN" \
        --non-interactive --agree-tos --email quandhonline@gmail.com
fi

echo "=== 10. Update vhost with SSL ==="
cat > "${VESTACP_VHOST}/${DOMAIN}.conf" << VHOST
server {
    listen 80;
    listen [::]:80;
    server_name ${DOMAIN};
    return 301 https://\$host\$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name ${DOMAIN};

    root ${WWWROOT};
    index index.html;

    ssl_certificate /etc/letsencrypt/live/${DOMAIN}/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/${DOMAIN}/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers EECDH+CHACHA20:EECDH+AES128:RSA+AES128:EECDH+AES256:RSA+AES256:!MD5;
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
    ssl_session_timeout 10m;
    add_header Strict-Transport-Security "max-age=31536000" always;

    location / {
        try_files \$uri \$uri/ /index.html;
    }

    location /api/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
        proxy_connect_timeout 60s;
        proxy_read_timeout 60s;
    }

    location ~* \\.(js|css|png|jpg|jpeg|gif|ico|svg|woff2)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }

    location ^~ /.well-known/acme-challenge/ {
        root ${WWWROOT};
        allow all;
    }
}
VHOST

echo "=== 11. Reload Nginx ==="
nginx -t && nginx -s reload

echo ""
echo "=== DONE ==="
echo "Site: https://${DOMAIN}"
echo "API:  https://${DOMAIN}/api/"
echo ""
echo "Git remote to add on local:"
echo "  git remote add vps ssh://root@180.93.42.138${APP_DIR}/repo.git"
echo ""
echo "Deploy: git push vps main"
