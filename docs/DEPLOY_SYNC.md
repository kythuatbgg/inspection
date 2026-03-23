# Deploy sync flow

## Mục tiêu

Đảm bảo VPS luôn build từ source đang được quản lý trong Git, tránh drift giữa local, container và file sửa tay trên server.

## Cấu trúc deploy trên VPS

- Bare repo: `/opt/fbb-inspection/repo.git`
- Working tree tạm: `/opt/fbb-inspection/repo-work`
- Thư mục deploy thật: `/opt/fbb-inspection/current`
- Frontend static root: `/www/wwwroot/inspector.quandh.online`

## Flow chuẩn

1. Local commit code vào `main`
2. Chạy preflight local:
   - backend test/build cần pass
   - frontend build cần pass
   - working tree phải sạch hoặc đã biết rõ file nào chưa commit
3. Push lên remote `vps`
4. VPS `post-receive` hook:
   - backup release hiện tại vào `/opt/fbb-inspection/releases/<timestamp>`
   - checkout code mới vào `repo-work`
   - `rsync --delete` sang `current`
   - đảm bảo lại thư mục runtime Laravel
   - chạy `docker compose build/up` từ `current`
   - chạy migrate + cache lại Laravel
   - copy frontend build sang web root
   - verify backend qua `/up`
5. Nginx serve frontend static và reverse proxy `/api/` vào backend container

## Flow dev local

### Backend

- copy `backend/.env.example` thành `backend/.env`
- cập nhật DB local và `APP_URL`
- chạy `composer install`
- chạy `npm install`
- chạy `composer dev`

### Frontend độc lập

- vào `frontend/`
- chạy `npm install`
- chạy `npm run dev`
- frontend dev server proxy `/api` và `/storage` sang backend local `http://localhost:8000`

### Preflight trước khi push deploy

- backend: `php artisan test`
- frontend: `npm run build`
- nếu có thay đổi API lớn, test thêm login + dashboard stats local

## Lệnh local

Thêm remote một lần:

- `bash scripts/add-vps-remotes.sh`

Remote script giờ sẽ tự update URL nếu remote `vps` đã tồn tại.

Deploy:

- `git push vps main`

## File quan trọng

- `scripts/vps-deploy-setup.sh`
- `scripts/add-vps-remotes.sh`
- `docker-compose.yml`
- `docker/backend/Dockerfile`
- `docker/frontend/Dockerfile`
- `.dockerignore`

## Quy tắc bắt buộc

- Không sửa tay file app trong container đang chạy
- Không sửa tay file Laravel trên VPS ngoài Git
- Không hardcode secret trong script Git-tracked
- Mọi thay đổi phải commit trong repo rồi push deploy
- Docker phải build từ `backend/` và `frontend/` trong repo hiện tại
- Không được exclude `docker-compose.yml` hoặc `docker/Dockerfile*` khỏi ngữ cảnh build/deploy
- `POSTGRES_PASSWORD` phải được export rõ ràng khi setup/redeploy server và sẽ được lưu vào `/opt/fbb-inspection/shared/deploy.env`

## Lý do lỗi cũ xảy ra

Các lỗi production trước đó đến từ drift:

- thiếu thư mục runtime Laravel trong `storage/framework/*`
- `bootstrap/app.php` bị sửa tay trên VPS
- `AppServiceProvider.php` bị sửa tay trên VPS
- password Postgres thật không còn khớp với config
- script deploy cũ chỉ copy file Docker nhưng không đồng bộ source app đầy đủ

## Checklist sau khi đổi flow deploy

- push thử một commit nhỏ lên `main`
- kiểm tra hook có sync vào `/opt/fbb-inspection/current`
- kiểm tra có backup mới trong `/opt/fbb-inspection/releases`
- kiểm tra `docker compose ps`
- kiểm tra `https://inspector.quandh.online/up`
- login thử `admin`
- kiểm tra một số API chính như `/api/me` và `/api/dashboard/stats`

## Rollback nhanh

Nếu release mới lỗi nhưng backup vẫn còn:

1. chọn bản gần nhất trong `/opt/fbb-inspection/releases`
2. `rsync --delete` bản backup đó về `/opt/fbb-inspection/current`
3. chạy lại `docker compose up -d`
4. verify lại `/up` và flow login

## Gợi ý vận hành

Nếu cần thay đổi config deploy, chỉ sửa trong repo rồi chạy lại setup script hoặc push lại để hook mới được áp dụng.
