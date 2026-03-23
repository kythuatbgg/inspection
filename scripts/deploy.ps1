<#
.SYNOPSIS
Deploy FBB Inspection app (Frontend Vue + Backend Laravel) to VPS directly.
#>

$VPS_IP = "180.93.42.138"
$VPS_USER = "root"
$SSH_KEY = "~/.ssh/fbb_vps"
$DOMAIN = "inspector.quandh.online"
$APP_DIR = "/www/wwwroot/$DOMAIN"
$DB_PASS = "fbb_secure_2025"

Write-Host "========================================" -ForegroundColor Cyan
Write-Host " Deploying FBB Inspection to VPS (Direct) " -ForegroundColor Cyan
Write-Host "========================================" -ForegroundColor Cyan

# 1. Build frontend
Write-Host "`n[1/5] Building frontend application..." -ForegroundColor Yellow
Set-Location "frontend"
npm run build
Set-Location ".."

# 2. Upload frontend
Write-Host "`n[2/5] Uploading frontend files..." -ForegroundColor Yellow
# Must ensure target dir exists
ssh -i $SSH_KEY -o StrictHostKeyChecking=no $VPS_USER@$VPS_IP "mkdir -p $APP_DIR"
scp -i $SSH_KEY -o StrictHostKeyChecking=no -r frontend/dist/* $VPS_USER@$VPS_IP`:$APP_DIR/

# 3. Pack backend
Write-Host "`n[3/5] Packing backend application..." -ForegroundColor Yellow
# Create a tar file excluding unnecessary local dev folders
tar.exe -czf backend-deploy.tar.gz -C backend `
    --exclude=vendor `
    --exclude=node_modules `
    --exclude=.env `
    --exclude=tests `
    --exclude=storage/framework/cache/data/* `
    --exclude=storage/framework/views/* `
    --exclude=storage/logs/* `
    .

# 4. Upload backend
Write-Host "`n[4/5] Uploading backend bundle..." -ForegroundColor Yellow
ssh -i $SSH_KEY -o StrictHostKeyChecking=no $VPS_USER@$VPS_IP "mkdir -p $APP_DIR/api"
scp -i $SSH_KEY -o StrictHostKeyChecking=no backend-deploy.tar.gz $VPS_USER@$VPS_IP`:$APP_DIR/api/

# 5. Extract and configure backend on VPS
Write-Host "`n[5/5] Extracting and setting up Laravel on VPS..." -ForegroundColor Yellow
$VPS_SCRIPT = @"
cd $APP_DIR/api
tar -xzf backend-deploy.tar.gz
rm backend-deploy.tar.gz

if [ ! -f .env ]; then
    cp .env.example .env
fi

# Configure .env safely
sed -i 's/APP_ENV=.*/APP_ENV=production/' .env
sed -i 's/APP_DEBUG=.*/APP_DEBUG=false/' .env
sed -i 's|APP_URL=.*|APP_URL=https://$DOMAIN|' .env
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=sqlite/' .env

# Remove unused DB vars for clean sqlite setup
sed -i '/DB_HOST=/d' .env
sed -i '/DB_PORT=/d' .env
sed -i '/DB_DATABASE=/d' .env
sed -i '/DB_USERNAME=/d' .env
sed -i '/DB_PASSWORD=/d' .env

sed -i 's/CACHE_STORE=.*/CACHE_STORE=file/' .env
sed -i 's/SESSION_DRIVER=.*/SESSION_DRIVER=file/' .env

# Create sqlite database
touch database/database.sqlite
chmod 666 database/database.sqlite

# Install Composer packages
/www/server/php/83/bin/php /usr/bin/composer install --ignore-platform-req=ext-exif --no-dev --optimize-autoloader || {
    echo "Composer fallback: trying standard composer if aaPanel doesn't have it globally"
    composer install --ignore-platform-req=ext-exif --no-dev --optimize-autoloader
}

# Generate App Key if empty
if grep -q "APP_KEY=$" .env || ! grep -q "APP_KEY" .env; then
    /www/server/php/83/bin/php artisan key:generate --force
fi

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/testing storage/framework/views storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache
chown -R www:www . || true

# Migrate and cache
/www/server/php/83/bin/php artisan migrate --force
/www/server/php/83/bin/php artisan db:seed --class=AdminSeeder --force 2>/dev/null || true
/www/server/php/83/bin/php artisan optimize:clear
/www/server/php/83/bin/php artisan config:cache
/www/server/php/83/bin/php artisan route:cache
/www/server/php/83/bin/php artisan view:cache
"@

$VPS_SCRIPT | Set-Content -Path "deploy-remote.sh" -Encoding UTF8

scp -i $SSH_KEY -o StrictHostKeyChecking=no deploy-remote.sh $VPS_USER@$VPS_IP`:$APP_DIR/api/deploy-remote.sh
ssh -i $SSH_KEY -o StrictHostKeyChecking=no $VPS_USER@$VPS_IP "cd $APP_DIR/api && dos2unix deploy-remote.sh 2>/dev/null; chmod +x deploy-remote.sh && bash deploy-remote.sh"
ssh -i $SSH_KEY -o StrictHostKeyChecking=no $VPS_USER@$VPS_IP "rm $APP_DIR/api/deploy-remote.sh"

# Clean up local files
Remove-Item backend-deploy.tar.gz
Remove-Item deploy-remote.sh

Write-Host "`n========================================" -ForegroundColor Green
Write-Host " Deploy completed successfully! 🚀" -ForegroundColor Green
Write-Host " -> https://$DOMAIN" -ForegroundColor Green
Write-Host "========================================" -ForegroundColor Green
