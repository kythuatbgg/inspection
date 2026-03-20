# Batch Proposal Separation — created_by Fix

## Goal
Phân biệt batch "đề xuất" (inspector tạo) vs "giao thẳng" (admin tạo) qua trường `created_by`.

## Business Rules

| Nguồn batch | Ai tạo | ProposalsView | Dashboard/Tasks |
|---|---|---|---|
| Đề xuất | Inspector | ✅ Thấy (created_by = mình) | ✅ Thấy (sau khi duyệt) |
| Giao thẳng | Admin | ❌ Không thấy | ✅ Thấy |

## Changes Made

### Database
- Migration: `2026_03_20_220738_add_created_by_to_inspection_batches.php`
  - Thêm `created_by` (nullable foreignId → users.id)
  - Nullable cho data cũ không crash

### Backend
- `InspectionBatch.php` — `$fillable` + relationship `creator()`
- `BatchController::store()` — ghi `created_by = $request->user()->id`
- `BatchController::index()`:
  - Nếu có param `created_by` → lọc theo người tạo (ProposalsView)
  - Nếu là inspector và không có `created_by` → lọc theo người được giao (Dashboard/Tasks)

### Frontend
- `ProposalsView.vue` — gọi `/api/batches?created_by={current_user_id}`

## Verification

```bash
# 1. Chạy migration
php artisan migrate

# 2. Tạo batch (inspector) → ProposalsView thấy batch đó
# 3. Admin giao batch → ProposalsView KHÔNG thấy, Dashboard thấy đúng
```
