# FBB Inspection — Production Deployment Guide

> **Production URL:** https://inspector.quandh.online
> **VPS IP:** 180.93.42.138
> **SSH Key:** `~/.ssh/fbb_vps`
> **Admin Login:** `admin` / `Admin@2025`

---

## 1. Kiến trúc Production

```
VPS (Ubuntu 22.04 - aaPanel)
├── Nginx ──► / ────────► /www/wwwroot/inspector.quandh.online/dist/       (Frontend SPA)
│           ► /api/ ────► /www/wwwroot/inspector.quandh.online/api/public/ (Laravel)
│           ► /storage/ ► .../api/storage/app/public/                     (Media files)
├── PHP-FPM 8.3 (aaPanel compiled)
│   ├── Extensions: pgsql, pdo_pgsql, mbstring, opcache, fileinfo, zip
│   ├── php.ini:     /www/server/php/83/etc/php.ini       (FPM)
│   └── php-cli.ini: /www/server/php/83/etc/php-cli.ini   (CLI)
├── PostgreSQL 14
│   ├── Database: fsm_inspection
│   ├── User: postgres / postgres
│   └── Auth: md5 (pg_hba.conf)
└── Git repo → origin: https://github.com/kythuatbgg/inspection.git
```

---

## 2. Những gì đã sửa để chạy Production

### 2.1 Nginx — Fix API Routing

**Vấn đề:** aaPanel Nginx dùng `alias` cho `/api/` khiến Laravel strip prefix `/api/` → route not found.

**Fix:** Thêm `fastcgi_param SCRIPT_NAME /index.php;` vào Nginx vhost config:

```nginx
location /api/ {
    alias /www/wwwroot/inspector.quandh.online/api/public/;
    try_files $uri $uri/ @laravel;

    location ~ \.php$ {
        fastcgi_pass  unix:/tmp/php-cgi-83.sock;
        fastcgi_index index.php;
        include fastcgi.conf;
        fastcgi_param SCRIPT_FILENAME $request_filename;
        fastcgi_param SCRIPT_NAME /index.php;  # ← FIX
    }
}

location @laravel {
    rewrite /api/(.*)$ /api/index.php?/$1 last;
}
```

**File config:** `/www/server/panel/vhost/nginx/inspector.quandh.online.conf`

---

### 2.2 Laravel — JSON Error cho API

**Vấn đề:** API request không có auth → Laravel redirect sang login form (HTML) thay vì trả JSON 401.

**Fix trong `bootstrap/app.php`:**

```php
->withExceptions(function (Exceptions $exceptions) {
    $exceptions->render(function (AuthenticationException $e, Request $request) {
        if ($request->is('api/*') || $request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }
    });
})
```

---

### 2.3 DatabaseSeeder — Luôn gọi AdminSeeder

**Vấn đề:** `DatabaseSeeder` chỉ tìm file mock JSON, không tạo admin user khi file không tồn tại.

**Fix trong `database/seeders/DatabaseSeeder.php`:**

```php
public function run(): void
{
    $this->command->info('Starting database seeding...');
    
    // Always seed admin account first
    $this->call(AdminSeeder::class);  // ← THÊM DÒNG NÀY
    
    if (empty($this->seedDataPath)) {
        // ...
    }
}
```

---

### 2.4 PDF Report — Ảnh dùng Base64

**Vấn đề:** `public_path()` trỏ sai trên production (Nginx alias). DomPDF không render file path.

**Fix trong `inspection.blade.php`:** Helper `resolvePhotoBase64()` tìm ảnh qua `storage_path()` → convert sang `data:image;base64,...` URI.

---

### 2.5 PHP Extensions — pgsql + pdo_pgsql

Biên dịch từ source aaPanel PHP 8.3:

```bash
# pgsql
cd /www/server/php/83/src/ext/pgsql
/www/server/php/83/bin/phpize && ./configure --with-php-config=/www/server/php/83/bin/php-config
make -j2 && make install

# pdo_pgsql
cd /www/server/php/83/src/ext/pdo_pgsql
/www/server/php/83/bin/phpize && ./configure --with-php-config=/www/server/php/83/bin/php-config --with-pdo-pgsql
make -j2 && make install

# Enable trong CẢ HAI file ini:
echo 'extension=pgsql.so' >> /www/server/php/83/etc/php.ini
echo 'extension=pdo_pgsql.so' >> /www/server/php/83/etc/php.ini
echo 'extension=pgsql.so' >> /www/server/php/83/etc/php-cli.ini
echo 'extension=pdo_pgsql.so' >> /www/server/php/83/etc/php-cli.ini
```

---

### 2.6 Frontend Nginx Root & Browser Cache

**Vấn đề:** Ban đầu Nginx fallback về `index.html` cũ nằm ở root folder do `deploy.sh` chạy `git reset --hard` (không xóa untracked files). Khi SCP thư mục `dist/` lên, request web vẫn tải `index.html` bản cũ. Code frontend không tự động cập nhật được.
**Fix:** 
1. Cấu hình Nginx `root` trỏ trực tiếp vào thư mục `dist`: `root /www/wwwroot/inspector.quandh.online/dist;`
2. Update `deploy.ps1` thêm lệnh SSH tự động `rm -rf dist/*` trước khi upload bundle mới lên VPS.

---

### 2.7 Guardrails cho API Kiểm tra lô

**Vấn đề:** Inspector vẫn có thể nhấn nút nộp biên bản và truy cập route kiểm tra cho các lô có trạng thái `proposal` (chưa duyệt) hoặc đã kết thúc.
**Fix Backend:** `InspectionController::store()` và `SyncController::sync()` đã thêm check:
- Trả 422 nếu `$plan->batch->approval_status !== 'approved'`
- Trả 422 nếu `$plan->batch->status === 'completed'`
**Fix Frontend:** Trong `BatchDetailView.vue`, thêm banner Warning, disable nhấn vào các cabinet và chặn function `goToInspection()` khi điều kiện duyệt chưa thỏa mãn.

---

### 2.8 PWA Locales (i18n)

**Vấn đề:** Banner cài đặt điện thoại thiếu translation (`pwa.installTitle`).
**Fix:** Thay vì thêm key mới, sửa template `InspectorLayout.vue` trỏ đúng sang `inspector.installTitle` (đã có sẵn trong `vi.json` và `en.json`).

---

## 3. Deploy Code từ Local lên Production

### 3.1 Quy trình chuẩn (Git Push → SSH Deploy)

```powershell
# Bước 1: Commit & Push ở local
git add .
git commit -m "feat: mô tả thay đổi"
git push origin main

# Bước 2: SSH vào VPS chạy deploy script
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "bash /www/wwwroot/inspector.quandh.online/deploy.sh"
```

**Deploy script tự động làm:**
1. **Frontend:** Clean thư mục `/dist/*` trên VPS, chạy `npm run build` local, SCP bundle mới nhất vào mục `/dist`.
2. **Backend:** `git fetch` và `git reset --hard` trực tiếp trên VPS.
3. Chạy `php artisan optimize:clear` từ aaPanel PHP và restart PHP-FPM.

> ⚠️ `git reset --hard` sẽ ghi đè code trực tiếp trên VPS. Nginx root ưu tiên `/dist` file từ local. **Luôn sửa code ở local.**

---

### 3.2 Deploy nhanh bằng SCP (khi cần deploy 1-2 file gấp)

```powershell
# Deploy 1 file cụ thể
scp -i ~/.ssh/fbb_vps backend/app/Http/Controllers/Api/SomeController.php root@180.93.42.138:/www/wwwroot/inspector.quandh.online/api/app/Http/Controllers/Api/SomeController.php

# Clear cache sau khi SCP
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "cd /www/wwwroot/inspector.quandh.online/api && /www/server/php/83/bin/php artisan optimize:clear"
```

---

## 4. Deploy Database (Migration & Seeder)

### 4.1 Khi thêm Migration mới

```powershell
# Bước 1: Tạo migration ở local
php artisan make:migration add_xxx_to_yyy_table

# Bước 2: Code migration, test local
php artisan migrate

# Bước 3: Push code + chạy migrate trên VPS
git add . && git commit -m "migration: add xxx" && git push
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "bash /www/wwwroot/inspector.quandh.online/deploy.sh"
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "cd /www/wwwroot/inspector.quandh.online/api && /www/server/php/83/bin/php artisan migrate --force"
```

### 4.2 Khi cần Seed dữ liệu mới

```powershell
# Seed tất cả
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "cd /www/wwwroot/inspector.quandh.online/api && /www/server/php/83/bin/php artisan db:seed --force"

# Seed 1 seeder cụ thể
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "cd /www/wwwroot/inspector.quandh.online/api && /www/server/php/83/bin/php artisan db:seed --class=AdminSeeder --force"
```

### 4.3 Khi cần port data từ Local → Production

> **Quan trọng:** Dùng `scp` (binary) thay vì PowerShell `Get-Content | ssh` để tránh vỡ Unicode.

```powershell
# Bước 1: Tạo script export ở local (backend/export_data.php)
# Bước 2: Chạy export
php export_data.php   # → tạo file data_export.json

# Bước 3: Upload JSON bằng SCP (giữ nguyên UTF-8)
scp -i ~/.ssh/fbb_vps data_export.json root@180.93.42.138:/www/wwwroot/inspector.quandh.online/data_export.json

# Bước 4: Upload + chạy import script
scp -i ~/.ssh/fbb_vps backend/import_data.php root@180.93.42.138:/www/wwwroot/inspector.quandh.online/api/import_data.php
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "cd /www/wwwroot/inspector.quandh.online/api && /www/server/php/83/bin/php import_data.php"

# Bước 5: Dọn file tạm
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "rm -f /www/wwwroot/inspector.quandh.online/data_export.json /www/wwwroot/inspector.quandh.online/api/import_data.php"
```

> ⚠️ **KHÔNG dùng** `Get-Content file.json | ssh ... "cat > ..."` — PowerShell sẽ convert UTF-8 sang encoding Windows, phá vỡ tiếng Việt/Khmer.

---

## 5. Các lệnh thường dùng

```powershell
# SSH vào VPS
ssh -i ~/.ssh/fbb_vps root@180.93.42.138

# Deploy code
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "bash /www/wwwroot/inspector.quandh.online/deploy.sh"

# Chạy artisan command
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "cd /www/wwwroot/inspector.quandh.online/api && /www/server/php/83/bin/php artisan <COMMAND>"

# Xem Laravel log
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "tail -50 /www/wwwroot/inspector.quandh.online/api/storage/logs/laravel.log"

# Restart services
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "/etc/init.d/php-fpm-83 restart"
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "nginx -s reload"
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "systemctl restart postgresql"

# Kiểm tra PostgreSQL
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "PGPASSWORD=postgres psql -U postgres -h 127.0.0.1 -d fsm_inspection -c 'SELECT count(*) FROM users;'"
```

---

## 6. Backup & Rollback

```powershell
# Tạo backup mới
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "cp -a /www/wwwroot/inspector.quandh.online /www/wwwroot/inspector.quandh.online.bak.$(date +%Y%m%d)"

# Rollback về backup
ssh -i ~/.ssh/fbb_vps root@180.93.42.138 "rsync -a /www/wwwroot/inspector.quandh.online.bak.20260324/ /www/wwwroot/inspector.quandh.online/"

# Backup hiện có: inspector.quandh.online.bak.20260324 (81MB)
```

---

## 7. Lưu ý quan trọng

| Lưu ý | Chi tiết |
|-------|----------|
| **Không sửa trực tiếp trên VPS** | `deploy.sh` dùng `git reset --hard` sẽ ghi đè |
| **SCP thay vì pipe** | Dùng `scp` upload file UTF-8, không dùng `Get-Content \| ssh` |
| **2 file php.ini** | aaPanel PHP dùng `php.ini` (FPM) và `php-cli.ini` (CLI) riêng |
| **Storage symlink** | Đã tạo: `public/storage` → `storage/app/public` |
| **Composer** | VPS có SWAP 1GB, chạy `composer install` cần flag `--no-dev` |
| **Frontend build** | Build ở local (`npm run build`) rồi SCP thư mục `dist/` lên |
