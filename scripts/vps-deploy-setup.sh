#!/usr/bin/env bash
# =============================================================================
# FBB Inspection — VPS Setup Script (single repo)
# Run: ssh -i ~/.ssh/fbb_vps root@180.93.42.138 'bash -s' < this_script
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
    echo "[ERROR] POSTGRES_PASSWORD is required before running this script." >&2
    echo "Example: POSTGRES_PASSWORD='your-password' ssh root@host 'bash -s' < scripts/vps-deploy-setup.sh" >&2
    exit 1
fi

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1"
}

echo "=== 1. Setup directories ==="
mkdir -p "$APP_DIR"/{repo.git,frontend.dist}
mkdir -p "$DEPLOY_DIR"
mkdir -p "$RELEASES_DIR"
mkdir -p "$WORK_TREE"
mkdir -p "$SHARED_DIR"
mkdir -p "$WWWROOT"

cat > "$DEPLOY_ENV_FILE" <<ENVVARS
POSTGRES_PASSWORD='${POSTGRES_PASSWORD}'
ENVVARS
chmod 600 "$DEPLOY_ENV_FILE"

echo "=== 2. Clone repo (SSH) ==="
if [ ! -d "$APP_DIR/repo.git" ]; then
  git clone --bare git@github.com:kythuatbgg/inspection.git "$APP_DIR/repo.git"
fi

# Post-receive hook: smart incremental deployment
cat > "$APP_DIR/repo.git/hooks/post-receive" << 'HOOK'
#!/bin/bash
set -euo pipefail

APP_DIR="/opt/fbb-inspection"
DEPLOY_DIR="$APP_DIR/current"
RELEASES_DIR="$APP_DIR/releases"
WORK_TREE="$APP_DIR/repo-work"
SHARED_DIR="$APP_DIR/shared"
DEPLOY_ENV_FILE="$SHARED_DIR/deploy.env"
WWWROOT="/www/wwwroot/inspector.quandh.online"
LOG_FILE="/var/log/fbb-deploy.log"

if [ -f "$DEPLOY_ENV_FILE" ]; then
    set -a
    . "$DEPLOY_ENV_FILE"
    set +a
fi

log() {
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] $1" | tee -a "$LOG_FILE"
}

require_postgres_password() {
    if [ -z "${POSTGRES_PASSWORD:-}" ]; then
        log "ERROR: POSTGRES_PASSWORD is not set in deploy environment"
        exit 1
    fi
}

archive_current_release() {
    if [ -d "$DEPLOY_DIR" ] && [ "$(find "$DEPLOY_DIR" -mindepth 1 -maxdepth 1 | head -n 1)" ]; then
        local stamp
        stamp="$(date '+%Y%m%d-%H%M%S')"
        mkdir -p "$RELEASES_DIR/$stamp"
        rsync -a --delete "$DEPLOY_DIR/" "$RELEASES_DIR/$stamp/"
        log "Backup release created at $RELEASES_DIR/$stamp"
    else
        log "Skipping backup: current release is empty"
    fi
}

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

    cat > "$DEPLOY_DIR/.env" <<ENVVARS
POSTGRES_PASSWORD='${POSTGRES_PASSWORD}'
ENVVARS
}

ensure_backend_runtime_dirs() {
    mkdir -p \
        "$DEPLOY_DIR/backend/storage/framework/cache/data" \
        "$DEPLOY_DIR/backend/storage/framework/sessions" \
        "$DEPLOY_DIR/backend/storage/framework/testing" \
        "$DEPLOY_DIR/backend/storage/framework/views" \
        "$DEPLOY_DIR/backend/storage/logs" \
        "$DEPLOY_DIR/backend/bootstrap/cache"
}

verify_backend_health() {
    if curl -fsS http://127.0.0.1:8000/up >/dev/null; then
        log "Health check passed: /up"
    else
        log "ERROR: backend health check failed"
        exit 1
    fi
}

copy_frontend_dist() {
    local frontend_container
    frontend_container="$(docker compose ps -q frontend 2>/dev/null || true)"

    if [ -z "$frontend_container" ]; then
        log "ERROR: frontend container not found"
        exit 1
    fi

    mkdir -p "$WWWROOT"
    rm -rf "$WWWROOT"/*
    docker cp "$frontend_container:/usr/share/nginx/html/." "$WWWROOT/"
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

    require_postgres_password

    # Detect what changed
    if [[ "$OLD_SHA" =~ ^0+$ ]]; then
        CHANGED_FILES=$(git --git-dir="$APP_DIR/repo.git" diff-tree --no-commit-id --name-only -r "$NEW_SHA" 2>/dev/null || echo "")
    else
        CHANGED_FILES=$(git --git-dir="$APP_DIR/repo.git" diff --name-only "$OLD_SHA" "$NEW_SHA" 2>/dev/null || echo "")
    fi

    FRONTEND_CHANGED=false
    BACKEND_CHANGED=false
    DOCKER_CHANGED=false
    INFRA_CHANGED=false

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
            scripts/*|.dockerignore)
                INFRA_CHANGED=true
                ;;
        esac
    done

    if [ -z "$CHANGED_FILES" ]; then
        FRONTEND_CHANGED=true
        BACKEND_CHANGED=true
        DOCKER_CHANGED=true
    fi

    log "Changes detected:"
    log "  Frontend: $FRONTEND_CHANGED"
    log "  Backend: $BACKEND_CHANGED"
    log "  Docker: $DOCKER_CHANGED"
    log "  Infra: $INFRA_CHANGED"

    archive_current_release

    log "Syncing tracked files into deploy directory..."
    sync_repo
    ensure_backend_runtime_dirs

    # Docker change = rebuild everything
    if [ "$DOCKER_CHANGED" = true ] || [ "$INFRA_CHANGED" = true ]; then
        FRONTEND_CHANGED=true
        BACKEND_CHANGED=true
    fi

    cd "$DEPLOY_DIR"

    POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d postgres

    # Build based on what changed
    if [ "$BACKEND_CHANGED" = true ]; then
        log "Building backend..."
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose build --pull backend
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --no-deps backend

        # Run migrations
        log "Running migrations..."
        docker compose exec -T backend php artisan migrate --force 2>/dev/null || log "Migration note: (may already be applied)"
        docker compose exec -T backend php artisan optimize:clear >/dev/null 2>&1 || true
        docker compose exec -T backend php artisan config:cache >/dev/null 2>&1 || true
        docker compose exec -T backend php artisan route:cache >/dev/null 2>&1 || true
        docker compose exec -T backend php artisan view:cache >/dev/null 2>&1 || true
    fi

    if [ "$FRONTEND_CHANGED" = true ]; then
        log "Building frontend..."
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose build frontend
        POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --no-deps frontend

        # Copy to wwwroot
        log "Copying frontend to wwwroot..."
        copy_frontend_dist
    fi

    verify_backend_health

    log "=== Deployment complete ==="
    echo ""
done
HOOK
chmod +x "$APP_DIR/repo.git/hooks/post-receive"

echo "=== 3. Checkout first deploy ==="
git --git-dir="$APP_DIR/repo.git" --work-tree="$WORK_TREE" checkout -f main

echo "=== 4. Sync repo into deploy directory ==="
rsync -a --delete \
    --exclude='.git' \
    --exclude='backend/storage' \
    --exclude='backend/vendor' \
    --exclude='frontend/node_modules' \
    --exclude='frontend/dist' \
    "$WORK_TREE/" "$DEPLOY_DIR/"

cat > "$DEPLOY_DIR/.env" <<ENVVARS
POSTGRES_PASSWORD='${POSTGRES_PASSWORD}'
ENVVARS

mkdir -p \
    "$DEPLOY_DIR/backend/storage/framework/cache/data" \
    "$DEPLOY_DIR/backend/storage/framework/sessions" \
    "$DEPLOY_DIR/backend/storage/framework/testing" \
    "$DEPLOY_DIR/backend/storage/framework/views" \
    "$DEPLOY_DIR/backend/storage/logs" \
    "$DEPLOY_DIR/backend/bootstrap/cache"

echo "=== 5. Start containers (first-time) ==="
cd "$DEPLOY_DIR"
POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --build postgres backend

echo "Waiting for PostgreSQL..."
sleep 15

echo "=== 6. Run Laravel migrations ==="
docker compose exec -T backend php artisan migrate --force
docker compose exec -T backend php artisan optimize:clear >/dev/null 2>&1 || true
docker compose exec -T backend php artisan config:cache >/dev/null 2>&1 || true
docker compose exec -T backend php artisan route:cache >/dev/null 2>&1 || true
docker compose exec -T backend php artisan view:cache >/dev/null 2>&1 || true

echo "=== 7. Build frontend + copy to wwwroot ==="
POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --build frontend
sleep 5
docker cp "$(docker compose ps -q frontend):/usr/share/nginx/html/." "$WWWROOT/"

echo "=== 7b. Verify backend health ==="
curl -fsS http://127.0.0.1:8000/up >/dev/null

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

    location ^~ /storage/ {
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

    location ^~ /storage/ {
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
echo "  - Backend or infra changes → rebuilds backend, runs migrations, verifies /up"
echo "  - Docker changes        → rebuilds everything"
echo "  - No changes            → skips build (fast)"
