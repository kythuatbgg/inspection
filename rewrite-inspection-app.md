# Rewrite Inspection App

## Goal
Viết lại toàn bộ ứng dụng `inspection` với `Vue 3 + PrimeVue + Laravel`, triển khai theo từng pha, bắt đầu từ **Pha A: Auth + role admin/inspector**.

## Project Type
WEB

## Success Criteria
- Có app shell mới cho cả admin và inspector.
- Auth flow mới ổn định, role-aware, dễ mở rộng.
- Backend auth API có contract rõ ràng, controller mỏng, service/action rõ trách nhiệm.
- Frontend và backend đều build/test được ở pha A.

## Tech Stack
- Frontend: `Vue 3`, `Vite`, `PrimeVue`, `Pinia`, `vite-plugin-pwa`
- Backend: `Laravel 11`, `Sanctum`, `Eloquent`
- Testing: `Vitest`, `PHPUnit`

## File Structure
- `frontend/src/layouts/*`
- `frontend/src/views/auth/*`
- `frontend/src/views/admin/*`
- `frontend/src/views/inspector/*`
- `frontend/src/services/api/*`
- `frontend/src/modules/*`
- `backend/app/Actions/Auth/*`
- `backend/app/Http/Requests/Auth/*`
- `backend/app/Http/Resources/Auth/*`
- `backend/app/Support/*`

## Tasks
- [ ] Task 1: Thiết kế app shell mới cho `frontend` → Verify: route `guest/admin/inspector` hoạt động đúng.
- [ ] Task 2: Viết lại auth store + API client → Verify: login/logout/session restore chạy ổn.
- [ ] Task 3: Viết lại auth API trên `backend` → Verify: `php artisan test` pass cho auth tests.
- [ ] Task 4: Tạo role-based landing page cho admin và inspector → Verify: redirect theo role đúng sau login.
- [ ] Task 5: Chuẩn hóa response contract và auth resource → Verify: `GET /api/me` trả shape ổn định.
- [ ] Task 6: Chuẩn bị nền module cho các pha B-F → Verify: cấu trúc thư mục mới rõ ràng, không phá build.
- [ ] Task 7: Chạy build/test frontend và backend → Verify: `npm run build`, `npm test`, `php artisan test` thành công.

## Phase X
- [ ] Frontend: `npm test`
- [ ] Frontend: `npm run build`
- [ ] Backend: `php artisan test`
- [ ] Backend assets: `npm run build`
- [ ] Manual: login admin/inspector, refresh session, logout, route guard
