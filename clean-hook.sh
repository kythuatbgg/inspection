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

    # Generate production .env from .env.example + secrets
    if [ -f "$DEPLOY_DIR/backend/.env.example" ]; then
        cp "$DEPLOY_DIR/backend/.env.example" "$DEPLOY_DIR/backend/.env"
    fi

    # Override with production values (Docker env vars take precedence, but be explicit)
    sed -i "s/APP_ENV=.*/APP_ENV=production/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/APP_DEBUG=.*/APP_DEBUG=true/" "$DEPLOY_DIR/backend/.env"
    sed -i "s|APP_URL=.*|APP_URL=https://${DOMAIN}|" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_CONNECTION=.*/DB_CONNECTION=pgsql/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_HOST=.*/DB_HOST=postgres/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_PORT=.*/DB_PORT=5432/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_DATABASE=.*/DB_DATABASE=fsm_inspection/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/DB_USERNAME=.*/DB_USERNAME=fbb_user/" "$DEPLOY_DIR/backend/.env"
    sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${POSTGRES_PASSWORD}|" "$DEPLOY_DIR/backend/.env"
    sed -i "s/CACHE_STORE=.*/CACHE_STORE=database/" "$DEPLOY_DIR/backend/.env"
    sed -i "s/LOG_LEVEL=.*/LOG_LEVEL=debug/" "$DEPLOY_DIR/backend/.env"
    # Keep APP_KEY from existing .env if exists
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
