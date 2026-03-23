#!/usr/bin/env bash
# =============================================================================
# FBB Inspection — VPS Setup Script
# Run from local: ssh -i ~/.ssh/fbb_vps root@180.93.42.138 'bash -s' < scripts/vps-deploy-setup.sh
# =============================================================================

set -euo pipefail

APP_DIR="/opt/fbb-inspection"
DEPLOY_DIR="$APP_DIR/current"
RELEASES_DIR="$APP_DIR/releases"
WORK_TREE="$APP_DIR/repo-work"
SHARED_DIR="$APP_DIR/shared"
DEPLOY_ENV_FILE="$SHARED_DIR/deploy.env"
DOMAIN="inspector.quandh.online"
VESTACP_VHOST="/www/server/panel/vhost/nginx"
WWWROOT="/www/wwwroot/${DOMAIN}"

if [ -z "${POSTGRES_PASSWORD:-}" ]; then
    echo "[ERROR] POSTGRES_PASSWORD is required." >&2
    echo "Usage: POSTGRES_PASSWORD='yourpass' ssh root@host 'bash -s' < scripts/vps-deploy-setup.sh" >&2
    exit 1
fi

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"; }

# ──────────────────────────────────────────────────────────────────────────────
log "=== 1. Create directories ==="
mkdir -p "$APP_DIR/repo.git"
mkdir -p "$DEPLOY_DIR" "$RELEASES_DIR" "$WORK_TREE" "$SHARED_DIR" "$WWWROOT"

# Save deploy secrets
cat > "$DEPLOY_ENV_FILE" <<ENVVARS
POSTGRES_PASSWORD='${POSTGRES_PASSWORD}'
ENVVARS
chmod 600 "$DEPLOY_ENV_FILE"

# ──────────────────────────────────────────────────────────────────────────────
log "=== 2. Init bare git repo (from GitHub HTTPS) ==="
if [ ! -f "$APP_DIR/repo.git/HEAD" ]; then
    git init --bare "$APP_DIR/repo.git"
    # Set remote so we can pull on first setup
    git -C "$APP_DIR/repo.git" remote add origin https://github.com/kythuatbgg/inspection.git 2>/dev/null || true
fi

# ──────────────────────────────────────────────────────────────────────────────
log "=== 3. Install post-receive hook ==="
cat > "$APP_DIR/repo.git/hooks/post-receive" << 'HOOK'
#!/bin/bash
set -euo pipefail

APP_DIR="/opt/fbb-inspection"
DEPLOY_DIR="$APP_DIR/current"
RELEASES_DIR="$APP_DIR/releases"
WORK_TREE="$APP_DIR/repo-work"
SHARED_DIR="$APP_DIR/shared"
DEPLOY_ENV_FILE="$SHARED_DIR/deploy.env"
DOMAIN="inspector.quandh.online"
WWWROOT="/www/wwwroot/${DOMAIN}"
LOG_FILE="/var/log/fbb-deploy.log"

[ -f "$DEPLOY_ENV_FILE" ] && { set -a; . "$DEPLOY_ENV_FILE"; set +a; }

log() { echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"; }

sync_repo() {
    mkdir -p "$WORK_TREE" "$DEPLOY_DIR"
    git --git-dir="$APP_DIR/repo.git" --work-tree="$WORK_TREE" checkout -f main

    rsync -a --delete \
        --exclude='.git' \
        --exclude='backend/storage' \
        --exclude='backend/vendor' \
        --exclude='frontend/node_modules' \
        --exclude='frontend/dist' \
        "$WORK_TREE/" "$DEPLOY_DIR/"

    # Generate .env from .env.example
    if [ -f "$DEPLOY_DIR/backend/.env.example" ]; then
        cp "$DEPLOY_DIR/backend/.env.example" "$DEPLOY_DIR/backend/.env"
    fi

    # Inject production values (sed-safe: use | as delimiter)
    sed -i "s/APP_ENV=.*/APP_ENV=production/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" "$DEPLOY_DIR/backend/.env"
    sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_HOST=.*/DB_HOST=postgres/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_PORT=.*/DB_PORT=5432/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=fsm_inspection/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=fbb_user/" "$DEPLOY_DIR/backend/.env"
    # Use Python to avoid sed delimiter collision with special chars in password
    python3 -c "
import re, sys
content = open('$DEPLOY_DIR/backend/.env').read()
content = re.sub(r'^DB_PASSWORD=.*', 'DB_PASSWORD=${POSTGRES_PASSWORD}', content, flags=re.MULTILINE)
open('$DEPLOY_DIR/backend/.env', 'w').write(content)
"
    sed -i "s/CACHE_STORE=.*/CACHE_STORE=file/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=file/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/LOG_LEVEL=.*/LOG_LEVEL=info/" "$DEPLOY_DIR/backend/.env"
}

ensure_storage_dirs() {
    mkdir -p \
        "$DEPLOY_DIR/backend/storage/framework/cache/data" \
        "$DEPLOY_DIR/backend/storage/framework/sessions" \
        "$DEPLOY_DIR/backend/storage/framework/testing" \
        "$DEPLOY_DIR/backend/storage/framework/views" \
        "$DEPLOY_DIR/backend/storage/logs" \
        "$DEPLOY_DIR/backend/bootstrap/cache"
}

copy_frontend_dist() {
    local cid
    cid="$(docker compose ps -q frontend 2>/dev/null || true)"
    if [ -z "$cid" ]; then
        log "ERROR: frontend container not found"
        exit 1
    fi
    mkdir -p "$WWWROOT"
    rm -rf "$WWWROOT"/*
    docker cp "$cid:/usr/share/nginx/html/." "$WWWROOT/"
}

while read -r OLD_SHA NEW_SHA REF; do
    [[ "$REF" != "refs/heads/main" ]] && { log "Skipping non-main: $REF"; exit 0; }

    log "=== Deployment started: ${NEW_SHA:0:8} ==="

    # Detect changed areas
    if [[ "$OLD_SHA" =~ ^0+$ ]]; then
        CHANGED=$(git --git-dir="$APP_DIR/repo.git" diff-tree --no-commit-id --name-only -r "$NEW_SHA" 2>/dev/null || echo "")
    else
        CHANGED=$(git --git-dir="$APP_DIR/repo.git" diff --name-only "$OLD_SHA" "$NEW_SHA" 2>/dev/null || echo "")
    fi

    FRONTEND_CHANGED=false
    BACKEND_CHANGED=false
    DOCKER_CHANGED=false

    for f in $CHANGED; do
        case "$f" in
            frontend/*) FRONTEND_CHANGED=true ;;
            backend/*)  BACKEND_CHANGED=true ;;
            docker*|docker-compose.yml) DOCKER_CHANGED=true ;;
        esac
    done

    # Force rebuild everything if no diff info or docker changed
    if [ -z "$CHANGED" ] || [ "$DOCKER_CHANGED" = true ]; then
        FRONTEND_CHANGED=true
        BACKEND_CHANGED=true
    fi

    log "Changes → frontend:$FRONTEND_CHANGED backend:$BACKEND_CHANGED docker:$DOCKER_CHANGED"

    # Archive current release
    if [ -d "$DEPLOY_DIR" ] && [ "$(ls -A "$DEPLOY_DIR" 2>/dev/null)" ]; then
        stamp="$(date '+%Y%m%d-%H%M%S')"
        rsync -a --delete "$DEPLOY_DIR/" "$RELEASES_DIR/$stamp/"
        log "Backup: $RELEASES_DIR/$stamp"
        # Keep only last 3 releases
        ls -dt "$RELEASES_DIR"/*/ 2>/dev/null | tail -n +4 | xargs rm -rf 2>/dev/null || true
    fi

    sync_repo
    ensure_storage_dirs

    cd "$DEPLOY_DIR"

    # Always ensure postgres is up
    POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d postgres

    if [ "$BACKEND_CHANGED" = true ]; then
        log "Building backend..."
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose build --pull backend
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --no-deps backend

        log "Running migrations..."
        sleep 5
        docker compose exec -T backend php artisan migrate --force 2>/dev/null \
            || log "Migration note: may already be up to date"
        docker compose exec -T backend php artisan db:seed --class=AdminSeeder --force 2>/dev/null \
            || log "Seeder note: admin may already exist"
        docker compose exec -T backend php artisan optimize:clear >/dev/null 2>&1 || true
        docker compose exec -T backend php artisan config:cache >/dev/null 2>&1 || true
        docker compose exec -T backend php artisan route:cache >/dev/null 2>&1 || true
        docker compose exec -T backend php artisan view:cache >/dev/null 2>&1 || true
    fi

    if [ "$FRONTEND_CHANGED" = true ]; then
        log "Building frontend..."
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose build frontend
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --no-deps frontend
        sleep 3
        copy_frontend_dist
    fi

    # Health check
    if curl -fsS http://127.0.0.1:8000/up >/dev/null; then
        log "Health check passed ✓"
    else
        log "ERROR: health check failed"
        exit 1
    fi

    log "=== Deployment complete: ${NEW_SHA:0:8} ==="
done
HOOK
chmod +x "$APP_DIR/repo.git/hooks/post-receive"

# ──────────────────────────────────────────────────────────────────────────────
log "=== 4. First checkout + deploy ==="
git --git-dir="$APP_DIR/repo.git" --work-tree="$WORK_TREE" checkout -f main

rsync -a --delete \
    --exclude='.git' \
    --exclude='backend/storage' \
    --exclude='backend/vendor' \
    --exclude='frontend/node_modules' \
    --exclude='frontend/dist' \
    "$WORK_TREE/" "$DEPLOY_DIR/"

if [ -f "$DEPLOY_DIR/backend/.env.example" ]; then
    cp "$DEPLOY_DIR/backend/.env.example" "$DEPLOY_DIR/backend/.env"
fi

sed -i "s/APP_ENV=.*/APP_ENV=production/" "$DEPLOY_DIR/backend/.env"
sed -i "s/APP_DEBUG=.*/APP_DEBUG=false/" "$DEPLOY_DIR/backend/.env"
sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" "$DEPLOY_DIR/backend/.env"
sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" "$DEPLOY_DIR/backend/.env"
sed -i "s/DB_HOST=.*/DB_HOST=postgres/" "$DEPLOY_DIR/backend/.env"
sed -i "s/DB_PORT=.*/DB_PORT=5432/" "$DEPLOY_DIR/backend/.env"
sed -i "s/DB_DATABASE=.*/DB_DATABASE=fsm_inspection/" "$DEPLOY_DIR/backend/.env"
sed -i "s/DB_USERNAME=.*/DB_USERNAME=fbb_user/" "$DEPLOY_DIR/backend/.env"
python3 -c "
import re
content = open('$DEPLOY_DIR/backend/.env').read()
content = re.sub(r'^DB_PASSWORD=.*', 'DB_PASSWORD=${POSTGRES_PASSWORD}', content, flags=re.MULTILINE)
open('$DEPLOY_DIR/backend/.env', 'w').write(content)
"
sed -i "s/CACHE_STORE=.*/CACHE_STORE=file/" "$DEPLOY_DIR/backend/.env"
sed -i "s/SESSION_DRIVER=.*/SESSION_DRIVER=file/" "$DEPLOY_DIR/backend/.env"
sed -i "s/LOG_LEVEL=.*/LOG_LEVEL=info/" "$DEPLOY_DIR/backend/.env"

mkdir -p \
    "$DEPLOY_DIR/backend/storage/framework/cache/data" \
    "$DEPLOY_DIR/backend/storage/framework/sessions" \
    "$DEPLOY_DIR/backend/storage/framework/testing" \
    "$DEPLOY_DIR/backend/storage/framework/views" \
    "$DEPLOY_DIR/backend/storage/logs" \
    "$DEPLOY_DIR/backend/bootstrap/cache"

# ──────────────────────────────────────────────────────────────────────────────
log "=== 5. Start containers ==="
cd "$DEPLOY_DIR"
POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --build postgres backend

log "Waiting for PostgreSQL..."
sleep 20

log "=== 6. Migrate + seed admin ==="
docker compose exec -T backend php artisan migrate --force
docker compose exec -T backend php artisan db:seed --class=AdminSeeder --force
docker compose exec -T backend php artisan optimize:clear >/dev/null 2>&1 || true
docker compose exec -T backend php artisan config:cache >/dev/null 2>&1 || true
docker compose exec -T backend php artisan route:cache >/dev/null 2>&1 || true
docker compose exec -T backend php artisan view:cache >/dev/null 2>&1 || true

log "=== 7. Build + deploy frontend ==="
POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --build frontend
sleep 5
docker cp "$(docker compose ps -q frontend):/usr/share/nginx/html/." "$WWWROOT/"

log "=== 8. Health check ==="
curl -fsS http://127.0.0.1:8000/up >/dev/null && log "Backend healthy ✓"

# ──────────────────────────────────────────────────────────────────────────────
log "=== 9. Setup Nginx vhost (HTTP) ==="
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
    }

    location ^~ /storage/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
    }

    location ^~ /.well-known/acme-challenge/ {
        root ${WWWROOT};
        allow all;
    }
}
VHOST

nginx -t && nginx -s reload
log "Nginx reloaded ✓"

log "=== 10. SSL (certbot) ==="
if [ ! -f "/etc/letsencrypt/live/${DOMAIN}/fullchain.pem" ]; then
    certbot certonly --webroot -w "$WWWROOT" -d "$DOMAIN" \
        --non-interactive --agree-tos --email quandhonline@gmail.com
fi

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
    ssl_prefer_server_ciphers on;
    ssl_session_cache shared:SSL:10m;
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
    }

    location ^~ /storage/ {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto https;
    }

    location ^~ /.well-known/acme-challenge/ {
        root ${WWWROOT};
        allow all;
    }
}
VHOST

nginx -t && nginx -s reload
log "SSL configured ✓"

echo ""
echo "╔══════════════════════════════════════════════════════════╗"
echo "║  ✅ FBB Inspection deployed successfully!               ║"
echo "║                                                          ║"
echo "║  URL:   https://${DOMAIN}                ║"
echo "║  Admin: admin / Admin@2025                               ║"
echo "║                                                          ║"
echo "║  Add git remote on local:                                ║"
echo "║  git remote add vps ssh://root@180.93.42.138${APP_DIR}/repo.git ║"
echo "║                                                          ║"
echo "║  Deploy: git push vps main                               ║"
echo "╚══════════════════════════════════════════════════════════╝"
