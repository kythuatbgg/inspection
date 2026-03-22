#!/usr/bin/env bash
# =============================================================================
# FBB Inspection — VPS Setup Script
# Run once on VPS: ssh -i ~/.ssh/fbb_vps root@180.93.42.138 'bash -s' < this_script
# =============================================================================

set -euo pipefail

APP_DIR="/opt/fbb-inspection"
REPO_BACKEND="${APP_DIR}/repo-backend.git"
REPO_FRONTEND="${APP_DIR}/repo-frontend.git"
POSTGRES_PASSWORD="${POSTGRES_PASSWORD:-$(openssl rand -base64 24)}"
DOMAIN="inspector.quandh.online"
VESTACP_NGINX="/www/server/nginx/conf/nginx.conf"
VESTACP_VHOST="/www/server/panel/vhost/nginx"
VESTACP_CERT="/www/server/panel/vhost/cert"

echo "=== 1. Create app directory ==="
mkdir -p "$APP_DIR"/{repo-backend.git,repo-frontend.git,app}

echo "=== 2. Clone from GitHub (requires GitHub credentials) ==="
cd "$APP_DIR"
git clone https://github.com/kythuatbgg/inspection.git app 2>/dev/null || \
  echo "⚠️  Manual clone needed: git clone https://github.com/kythuatbgg/inspection.git /opt/fbb-inspection/app"

# Use existing app if already cloned
if [ ! -d "$APP_DIR/app/backend" ]; then
  echo "⚠️  Clone failed. Run manually:"
  echo "   cd /opt/fbb-inspection && git clone https://github.com/kythuatbgg/inspection.git app"
  echo "   Then re-run this script."
  exit 1
fi

echo "=== 3. Copy docker-compose.yml and Dockerfiles ==="
cp "$APP_DIR/app/docker-compose.yml" "$APP_DIR/"
cp -r "$APP_DIR/app/docker/" "$APP_DIR/"

echo "=== 4. Create bare git repos for webhook deploy ==="
# Backend bare repo
git init --bare "$REPO_BACKEND"
cat > "$REPO_BACKEND/hooks/post-receive" << 'BACKEND_HOOK'
#!/bin/bash
set -euo pipefail
APP_DIR="/opt/fbb-inspection"
TARGET="$APP_DIR/app/backend"
GIT_WORK_TREE="$APP_DIR/work-backend" git checkout -f main 2>/dev/null || true
# Rebuild backend
docker compose -f "$APP_DIR/docker-compose.yml" build backend
docker compose -f "$APP_DIR/docker-compose.yml" up -d --no-deps backend
echo "[$(date)] Backend deployed."
BACKEND_HOOK

# Frontend bare repo
git init --bare "$REPO_FRONTEND"
cat > "$REPO_FRONTEND/hooks/post-receive" << 'FRONTEND_HOOK'
#!/bin/bash
set -euo pipefail
APP_DIR="/opt/fbb-inspection"
# Build frontend
docker compose -f "$APP_DIR/docker-compose.yml" build frontend
docker compose -f "$APP_DIR/docker-compose.yml" up -d frontend
echo "[$(date)] Frontend deployed."
FRONTEND_HOOK

chmod +x "$REPO_BACKEND/hooks/post-receive"
chmod +x "$REPO_FRONTEND/hooks/post-receive"

echo "=== 5. Start containers (first-time) ==="
cd "$APP_DIR"
POSTGRES_PASSWORD="$POSTGRES_PASSWORD" docker compose up -d --build postgres
echo "Waiting for PostgreSQL to be ready..."
sleep 10

echo "=== 6. Run Laravel migrations ==="
docker compose exec -T backend php artisan migrate --force

echo "=== 7. Generate app key if missing ==="
docker compose exec -T backend php artisan key:generate --force

echo "=== 8. Create VestaCP Nginx vhost (API proxy) ==="
cat > "${VESTACP_VHOST}/api.${DOMAIN}.conf" << VHOST
server {
    listen 80;
    server_name api.${DOMAIN};

    client_max_body_size 50M;

    location / {
        proxy_pass http://127.0.0.1:8000;
        proxy_http_version 1.1;
        proxy_set_header Host \$http_host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_connect_timeout 60s;
        proxy_read_timeout 60s;
        proxy_send_timeout 60s;
    }
}
VHOST

echo "=== 9. Restart Nginx ==="
nginx -t && systemctl reload nginx

echo "=== 10. Enable & start all services ==="
docker compose up -d --build

echo ""
echo "=== DONE ==="
echo "PostgreSQL password: $POSTGRES_PASSWORD"
echo "Save this password! It is NOT stored anywhere."
echo ""
echo "Git remote to add on local:"
echo "  git remote add vps-backend ssh://root@180.93.42.138${REPO_BACKEND}"
echo "  git remote add vps-frontend ssh://root@180.93.42.138${REPO_FRONTEND}"
echo ""
echo "To deploy:"
echo "  git push vps-backend main   # backend + migrate"
echo "  git push vps-frontend main  # frontend rebuild"
