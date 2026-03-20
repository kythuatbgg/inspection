# Cabinet import/export implementation handoff

## Đã sửa

### Backend
- `backend/app/Http/Controllers/Api/CabinetController.php`
  - Search theo `cabinet_code` và `bts_site`
  - Template import đổi sang `cabinet_template.xlsx`
  - Export cabinet stream CSV trực tiếp từ backend
  - Bỏ `name`, `type` khỏi export contract
  - Import trả thêm `export_token`
  - `export-result` không còn phụ thuộc session, dùng `token`

### Frontend
- `frontend/src/services/cabinetService.js`
  - `exportCabinets()` dùng blob
  - `exportResult(token)` truyền token
- `frontend/src/views/admin/CabinetsView.vue`
  - Placeholder search đổi đúng theo behavior thực tế
  - Download template thành `.xlsx`
  - Export cabinet tải blob CSV trực tiếp
  - Download import result dùng `export_token`
  - Import xong reload lại danh sách cabinet

### Tests
- `backend/tests/Feature/CabinetControllerTest.php`
  - Đồng bộ theo schema hiện tại không còn `name/type`
  - Có test cho Excel template, streamed CSV export, tokenized import-result export
- `frontend/tests/services/cabinetService.spec.js`
  - Đồng bộ với blob export/template và tokenized import-result
- `frontend/tests/services/cabinetTemplate.spec.js`
  - Đồng bộ với Excel content type

## Contract hiện tại
- `GET /api/cabinets?search=...` → search `cabinet_code` + `bts_site`
- `GET /api/cabinets/template` → file Excel `.xlsx`
- `GET /api/cabinets/export` → streamed CSV
- `POST /api/cabinets/import` → trả `export_token`
- `GET /api/cabinets/export-result?token=...` → streamed CSV theo token

## Verify
- Frontend test pass:
  - `cmd /c npm test -- tests/services/cabinetService.spec.js tests/services/cabinetTemplate.spec.js`
- Backend feature test **chưa verify xong** vì bị chặn bởi migration cũ:
  - `backend/database/migrations/2026_03_18_000002_add_manager_staff_roles.php`
  - lỗi SQLite tại câu `ALTER TABLE users DROP CONSTRAINT users_role_check`

## Việc agent sau nên làm tiếp
1. Fix migration SQLite blocker ở `2026_03_18_000002_add_manager_staff_roles.php`
2. Chạy lại `php artisan test tests/Feature/CabinetControllerTest.php`
3. Nếu cần, test tay UI import/export tại trang admin cabinet
