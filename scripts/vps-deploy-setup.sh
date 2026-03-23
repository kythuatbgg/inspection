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

# Post-receive hook: smart incremental deployment
cat > "$APP_DIR/repo.git/hooks/post-receive" << 'HOOK'
#!/bin/bash
set -euo pipefail

APP_DIR="/opt/fbb-inspection"
WWWROOT="/www/wwwroot/inspector.quandh.online"
POSTGRES_PASSWORD="${POSTGRES_PASSWORD:-lTPxmtO0jLjV83+/wDXs3ULIm1KkkYM1}"
LOG_FILE="/var/log/fbb-deploy.log"

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

# Read the push info from stdin: <old-sha> <new-sha> <ref>
while read -r OLD_SHA NEW_SHA REF; do
    # Only process main branch
    if [[ "$REF" != "refs/heads/main" ]]; then
        log "Skipping non-main branch: $REF"
        exit 0
    fi

    log "=== Starting deployment ==="
    log "Old: $OLD_SHA"
    log "New: $NEW_SHA"

    # Detect what changed
    CHANGED_FILES=$(git diff --name-only "$OLD_SHA" "$NEW_SHA" 2>/dev/null || echo "")

    FRONTEND_CHANGED=false
    BACKEND_CHANGED=false
    DOCKER_CHANGED=false

    for file in $CHANGED_FILES; do
        case "$file" in
            app/frontend/*|frontend/*)
                FRONTEND_CHANGED=true
                ;;
            app/backend/*|backend/*)
                BACKEND_CHANGED=true
                ;;
            app/docker-compose.yml|docker-compose.yml|app/docker/*|docker/*)
                DOCKER_CHANGED=true
                ;;
        esac
    done

    log "Changes detected:"
    log "  Frontend: $FRONTEND_CHANGED"
    log "  Backend: $BACKEND_CHANGED"
    log "  Docker: $DOCKER_CHANGED"

    # Checkout new code to working tree
    GIT_WORK_TREE="$APP_DIR/repo-work" git checkout -f main 2>/dev/null || true

    # If docker files changed, copy them
    if [ "$DOCKER_CHANGED" = true ]; then
        log "Docker files changed - updating docker configs..."
        cp -f "$APP_DIR/repo-work/docker-compose.yml" "$APP_DIR/" 2>/dev/null || true
        cp -rf "$APP_DIR/repo-work/docker/" "$APP_DIR/" 2>/dev/null || true
        # Docker change = rebuild everything
        FRONTEND_CHANGED=true
        BACKEND_CHANGED=true
    fi

    cd "$APP_DIR"

    # Build based on what changed
    if [ "$BACKEND_CHANGED" = true ]; then
        log "Building backend..."
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose build --pull backend
        docker compose up -d --no-deps --build backend

        # Run migrations
        log "Running migrations..."
        docker compose exec -T backend php artisan migrate --force 2>/dev/null || log "Migration note: (may already be applied)"
    fi

    if [ "$FRONTEND_CHANGED" = true ]; then
        log "Building frontend..."
        docker compose build frontend
        docker compose up -d --no-deps --build frontend

        # Copy to wwwroot
        log "Copying frontend to wwwroot..."
        docker cp "$(docker compose ps -q frontend 2>/dev/null || echo ""):/usr/share/nginx/html/." "$WWWROOT/" 2>/dev/null || true
    fi

    log "=== Deployment complete ==="
    echo ""
done
HOOK
chmod +x "$APP_DIR/repo.git/hooks/post-receive"

echo "=== 3. Checkout first deploy ==="
GIT_WORK_TREE="$APP_DIR/repo-work" git checkout -f main

echo "=== 4. Copy Docker files ==="
cp -r "$APP_DIR/repo-work/docker/" "$APP_DIR/"
cp "$APP_DIR/repo-work/docker-compose.yml" "$APP_DIR/"

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
echo ""
echo "Incremental deploy:"
echo "  - Frontend changes only  → builds frontend only"
echo "  - Backend changes only  → builds backend + runs migrations"
echo "  - Docker changes        → rebuilds everything"
echo "  - No changes            → skips build (fast)"
