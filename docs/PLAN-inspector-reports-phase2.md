# PLAN: Inspector Reports Page — Phase 2

## Context

Phase 1 đã hoàn thành: Inspector có thể xuất biên bản PDF từng tủ trong BatchDetailView.
Phase 2: Tạo trang **Reports riêng** cho Inspector với đầy đủ thống kê cá nhân.

---

## Scope

| Feature | Mô tả |
|---------|-------|
| **KPI Cards** | Tổng tủ đã kiểm, tỷ lệ PASS/FAIL, lỗi critical, điểm trung bình |
| **Danh sách biên bản** | Tất cả inspection xuyên batch, search theo cabinet code, filter theo batch/result/date |
| **Biểu đồ theo thời gian** | Số tủ kiểm theo tuần/tháng (bar chart đơn giản bằng CSS) |
| **Thống kê lỗi thường gặp** | Top lỗi critical + tần suất (horizontal bar chart CSS) |
| **Xuất PDF** | Nút download biên bản từ danh sách (reuse Phase 1 logic) |

---

## Proposed Changes

### Backend

#### [NEW] `InspectorStatsController.php`

Tạo controller mới tại `app/Http/Controllers/Api/InspectorStatsController.php`:

**Endpoints:**

| Method | Route | Response |
|--------|-------|----------|
| GET | `/inspector/stats/overview` | KPI: total, passed, failed, pass_rate, critical_errors, avg_score |
| GET | `/inspector/stats/timeline` | Mảng `[{month, total, passed, failed}]` cho 6 tháng gần nhất |
| GET | `/inspector/stats/top-errors` | Mảng `[{error_content, category, count}]` top 10 lỗi |
| GET | `/inspector/stats/inspections` | Paginated list: inspection + cabinet_code + batch_name + result + score + date. Hỗ trợ `?search=&batch_id=&result=&from=&to=` |

**Quan trọng:** Tất cả query đều tự động filter theo `auth()->id()` (chỉ data của Inspector đang đăng nhập).

#### [MODIFY] `routes/api.php`

Thêm route group cho Inspector stats (trong middleware `auth:sanctum`, **không** trong `manager`):

```php
Route::prefix('inspector/stats')->group(function () {
    Route::get('overview', [InspectorStatsController::class, 'overview']);
    Route::get('timeline', [InspectorStatsController::class, 'timeline']);
    Route::get('top-errors', [InspectorStatsController::class, 'topErrors']);
    Route::get('inspections', [InspectorStatsController::class, 'inspections']);
});
```

---

### Frontend

#### [NEW] `InspectorReportsView.vue`

Trang mới tại `views/inspector/InspectorReportsView.vue`:

**Layout (top → bottom):**

1. **Header:** "Báo cáo của tôi" + bộ lọc ngày (MobileDatePicker from/to)
2. **KPI Grid:** 4 cards (Pro Max pattern — 1 card lớn trên + 2x2 compact dưới trên mobile)
   - Tổng tủ đã KT | Tỷ lệ đạt | Lỗi Critical | Điểm TB
3. **Biểu đồ theo thời gian:** CSS bar chart 6 cột (6 tháng gần nhất), mỗi cột chia 2 màu (pass/fail)
4. **Top lỗi thường gặp:** Horizontal bar chart CSS, max 10 items, badge category
5. **Danh sách biên bản:**
   - Search by cabinet code
   - Filter by batch (MobileBottomSheet), result (PASS/FAIL), date range
   - Mobile card list / Desktop compact table
   - Nút "Xuất PDF" trên mỗi row
   - Pagination

**Tuân thủ:** `fbb-design-system` SKILL + `mobile-webview-design` SKILL

#### [NEW] `inspectorStatsService.js`

Service mới tại `services/inspectorStatsService.js`:

```js
getOverview(params)      // GET /inspector/stats/overview
getTimeline(params)      // GET /inspector/stats/timeline
getTopErrors(params)     // GET /inspector/stats/top-errors
getInspections(params)   // GET /inspector/stats/inspections
```

#### [MODIFY] `InspectorLayout.vue`

Thêm nav item vào `navItems[]`:

```js
{ path: '/inspector/reports', labelKey: 'nav.reports', icon: FileBarChart }
```

Import `FileBarChart` từ lucide-vue-next.

Thêm `pageTitle` case:
```js
if (route.path === '/inspector/reports') return t('inspector.myReports')
```

#### [MODIFY] `router/index.js`

Thêm route con trong `/inspector`:

```js
{ path: 'reports', name: 'inspector-reports', component: () => import('../views/inspector/InspectorReportsView.vue') }
```

#### [MODIFY] `vi.json` + `en.json`

Thêm keys:
- `nav.reports` → "Báo cáo" / "Reports"
- `inspector.myReports` → "Báo cáo của tôi" / "My Reports"
- `inspector.totalInspected` → "Tổng đã kiểm tra" / "Total Inspected"
- `inspector.avgScore` → "Điểm trung bình" / "Avg. Score"
- `inspector.passRate` → "Tỷ lệ đạt" / "Pass Rate"
- `inspector.criticalErrors` → "Lỗi nghiêm trọng" / "Critical Errors"
- `inspector.timeline` → "Theo thời gian" / "Timeline"
- `inspector.topErrors` → "Lỗi thường gặp" / "Common Errors"
- `inspector.allInspections` → "Tất cả biên bản" / "All Inspections"
- `inspector.searchCabinet` → "Tìm mã tủ..." / "Search cabinet..."
- `inspector.filterBatch` → "Lọc theo đợt" / "Filter by batch"
- `inspector.filterResult` → "Kết quả" / "Result"
- `inspector.noInspections` → "Chưa có biên bản nào" / "No inspections yet"

---

## File Summary

| File | Loại | Layer |
|------|------|-------|
| `InspectorStatsController.php` | NEW | Backend |
| `routes/api.php` | MODIFY | Backend |
| `InspectorReportsView.vue` | NEW | Frontend |
| `inspectorStatsService.js` | NEW | Frontend |
| `InspectorLayout.vue` | MODIFY | Frontend |
| `router/index.js` | MODIFY | Frontend |
| `vi.json` + `en.json` | MODIFY | Frontend |

**Tổng: 4 file mới + 4 file sửa**

---

## Verification Plan

### Automated
- `ux_audit.py` trên `InspectorReportsView.vue` → PASS

### Manual
1. Login Inspector → Sidebar hiện tab "Báo cáo"
2. KPI cards hiển thị đúng dữ liệu cá nhân (không lẫn Inspector khác)
3. Biểu đồ timeline hiện 6 tháng gần nhất
4. Top errors hiển thị đúng lỗi thường gặp nhất
5. Danh sách biên bản: search, filter, download PDF hoạt động
6. Mobile 375px: layout đẹp, touch targets ≥ 48px, cards stacked
