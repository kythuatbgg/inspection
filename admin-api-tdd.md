# Admin API Integration với TDD

## Goal
Kết nối Admin Dashboard với Backend API sử dụng Test-Driven Development

## Tasks

### Phase 1: Setup Test Environment

- [x] **Task 1:** Cài đặt Vitest + Vue Test Utils → Verify: `npm test` chạy không lỗi

### Phase 2: Tạo API Base & Services

- [x] **Task 2:** Tạo `src/services/api.js` (axios base instance với interceptors) → Verify: File tồn tại, export axios instance

- [x] **Task 3:** Viết test cho `api.js` - test interceptor thêm token → Verify: Test pass

- [x] **Task 4:** Tạo `src/services/batchService.js` (getBatches, getBatchById, createBatch) → Verify: File tồn tại

- [x] **Task 5:** Viết test cho `batchService.js` - mock API calls → Verify: Test pass

- [x] **Task 6:** Tạo `src/services/cabinetService.js` (getCabinets) → Verify: File tồn tại

- [x] **Task 7:** Viết test cho `cabinetService.js` → Verify: Test pass

### Phase 3: Kết nối Admin Views

- [x] **Task 8:** Cập nhật `DashboardView.vue` gọi API thực → Verify: Hiển thị dữ liệu từ API

- [x] **Task 9:** Cập nhật `BatchesView.vue` gọi API thực → Verify: Hiển thị danh sách batches

- [x] **Task 10:** Cập nhật `CabinetsView.vue` gọi API thực → Verify: Hiển thị danh sách cabinets

## Done When
- [x] Admin Dashboard hiển thị dữ liệu thực từ API
- [x] Tất cả tests pass (15 tests)
- [x] Không còn hardcoded mock data

## Summary
- **Services created:** api.js, batchService.js, cabinetService.js
- **Tests written:** 15 tests passing
- **Views updated:** DashboardView, BatchesView, CabinetsView
