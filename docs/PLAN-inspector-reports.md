# PLAN: Inspector Report Export (Option C)

## Context

Inspector hiện chỉ có thể: xem Batch → mở tủ → kiểm tra → submit. **Không có cách nào xuất biên bản PDF.**
Backend API `/reports/*` đã mở cho cả Inspector (không nằm trong middleware `manager`).
Chỉ cần thêm UI frontend.

---

## Phase 1: Nút "Xuất PDF" trên BatchDetailView (NGAY)

### Scope

Thêm chức năng xuất biên bản PDF cho từng tủ đã kiểm tra, ngay trong `BatchDetailView.vue` của Inspector.

### Changes

---

#### [MODIFY] [BatchDetailView.vue](file:///d:/DEV/Claude/inspection/frontend/src/views/inspector/BatchDetailView.vue)

1. **Import** `reportService` + `triggerDownload` + `Download` icon + `MobileBottomSheet`
2. **Thêm state:** `reportLang` (mặc định `'en'`), `downloadingId` (track tủ nào đang tải)
3. **Language selector** ở phần Batch Info card (dùng `MobileBottomSheet` với 3 options EN/VN/KH)
4. **Nút "Xuất PDF"** trên mỗi card tủ có `plan.inspection`:
   - Vị trí: dưới dòng kết quả (PASS/FAIL + Score + Critical errors)
   - Style: `btn-primary` nhỏ gọn, icon `Download`, full-width trên mobile
   - `@click.stop` để không trigger navigation vào InspectionView
   - Loading state riêng cho từng tủ (`downloadingId === plan.id`)
5. **Hàm `downloadReport(plan)`**: gọi `reportService.downloadInspectionReport(plan.inspection.id, reportLang)` → `triggerDownload`

---

#### [MODIFY] [vi.json](file:///d:/DEV/Claude/inspection/frontend/src/i18n/vi.json) + [en.json](file:///d:/DEV/Claude/inspection/frontend/src/i18n/en.json)

Thêm i18n keys:
- `inspector.exportPdf` → "Xuất biên bản" / "Export Report"
- `inspector.reportLang` → "Ngôn ngữ biên bản" / "Report Language"
- `inspector.downloading` → "Đang tải..." / "Downloading..."
- `inspector.downloadSuccess` → "Tải biên bản thành công" / "Report downloaded"
- `inspector.downloadError` → "Lỗi khi tải biên bản" / "Download failed"

---

### Không cần thay đổi

| Layer | Lý do |
|-------|-------|
| Backend API | `/reports/inspection/{id}` đã có, đã mở cho inspector |
| Router | Không thêm route mới |
| `reportService.js` | Đã có `downloadInspectionReport(id, lang)` |
| Inspector Layout | Không thêm nav item mới |

---

## Phase 2: Trang Inspector Reports (SAU NÀY — chưa triển khai)

> Tạo `InspectorReportsView.vue` + route `/inspector/reports` với:
> - Thống kê cá nhân (tổng số tủ đã kiểm, tỷ lệ pass, lỗi critical)
> - Danh sách tất cả biên bản xuyên batch
> - Tìm kiếm/lọc theo batch, code tủ
> 
> **Sẽ triển khai khi có nhu cầu.**

---

## Verification Plan

### Automated
- `ux_audit.py` trên `BatchDetailView.vue` → PASS

### Manual
1. Đăng nhập Inspector → mở 1 Batch đã kiểm tra
2. Thấy Language selector ở header batch
3. Tủ đã kiểm tra → nút "Xuất biên bản" hiển thị rõ ràng
4. Bấm → tải file PDF thành công
5. Đổi ngôn ngữ KH → xuất → font Khmer hiển thị đúng
6. Kiểm tra mobile 375px: nút full-width, touch target ≥ 48px
