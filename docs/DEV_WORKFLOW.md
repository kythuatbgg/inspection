# Dev workflow

## Mục tiêu

Chuẩn hoá quy trình chạy local trên Windows cho backend Laravel + frontend Vue, đồng thời giảm khác biệt giữa local và VPS.

## 1. Backend local

Thư mục làm việc: `backend/`

Checklist lần đầu:

1. copy `backend/.env.example` -> `backend/.env`
2. cấu hình DB local
3. chạy `composer install`
4. chạy `npm install`
5. chạy `php artisan key:generate`
6. chạy `php artisan migrate`

Lệnh dev chính:

- `composer dev`

Lệnh này sẽ chạy cùng lúc:

- Laravel server
- queue listener
- log tail
- Vite cho backend assets

## 2. Frontend local

Thư mục làm việc: `frontend/`

Checklist lần đầu:

1. chạy `npm install`
2. chạy `npm run dev`

Mặc định Vite frontend proxy:

- `/api` -> `http://localhost:8000`
- `/storage` -> `http://localhost:8000`

Nghĩa là backend phải chạy trước nếu muốn test UI đầy đủ.

## 3. Quy trình làm việc khuyến nghị

1. chạy backend local
2. chạy frontend local
3. code + test từng phần
4. trước khi commit:
   - backend: `php artisan test`
   - frontend: `npm run build`
5. commit vào `main` hoặc branch làm việc
6. chỉ deploy khi local đã pass preflight

## 4. Quy ước dev/deploy

- không sửa code trực tiếp trên VPS
- không dùng container đang chạy như nguồn sự thật
- Git là nguồn sự thật duy nhất
- mọi thay đổi infra phải nằm trong repo: `docker-compose.yml`, `docker/`, `scripts/`

## 5. Khi nào cần redeploy full

Redeploy full sẽ xảy ra nếu đổi một trong các nhóm sau:

- `docker-compose.yml`
- `docker/**`
- `scripts/**`
- `.dockerignore`

Lý do: các file này có thể làm thay đổi build context, image, startup hoặc hành vi hook deploy.