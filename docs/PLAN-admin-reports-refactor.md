# PLAN: Refactor Admin Reports View

## Context

Trang Admin Reports hiện đang được triển khai tập trung trong [frontend/src/views/admin/ReportsView.vue](../frontend/src/views/admin/ReportsView.vue) với phạm vi trách nhiệm quá lớn:

- render 3 tab: Reports / Statistics / Export
- quản lý filter, search, date range
- gọi API trực tiếp
- quản lý loading state
- quản lý download PDF / Excel
- render layout riêng cho desktop và mobile
- hiển thị toast
- chứa nhiều inline style

Điều này khiến file khó đọc, khó test, khó mở rộng và bắt đầu có rủi ro hiệu năng khi dữ liệu tăng.

---

## Goals

### Functional goals
- Giữ nguyên hành vi hiện tại của trang Reports admin.
- Không làm mất bất kỳ tính năng nào đang có.
- Không thay đổi API contract nếu không cần.

### Refactor goals
- Biến [frontend/src/views/admin/ReportsView.vue](../frontend/src/views/admin/ReportsView.vue) thành shell component gọn nhẹ.
- Tách logic API/state ra composables.
- Tách UI theo tab và theo khối nghiệp vụ.
- Giảm lặp desktop/mobile.
- Chuẩn hóa loading / empty / error state.
- Xóa inline style không cần thiết.

### Performance goals
- Không tải thống kê ngay khi người dùng chưa mở tab Statistics.
- Tránh spam request khi search.
- Bỏ request thừa khi query không đổi.
- Chuẩn bị cho pagination nếu dữ liệu lớn.
- Chỉ disable đúng nút đang tải file.

---

## Non-goals

- Không redesign toàn bộ giao diện.
- Không đổi text/i18n trừ khi cần để hỗ trợ trạng thái mới.
- Không thay đổi backend API trừ khi phát hiện pagination đã sẵn có và chỉ cần tận dụng.
- Không generic hóa quá mức ngay từ đầu.

---

## Proposed Architecture

### 1. Shell view
Giữ [frontend/src/views/admin/ReportsView.vue](../frontend/src/views/admin/ReportsView.vue) chỉ còn:
- header/title
- tabs config
- active tab state
- mount các tab component

### 2. Tab components
Tạo các component riêng:
- `frontend/src/components/admin/reports/AdminReportsTab.vue`
- `frontend/src/components/admin/reports/AdminStatisticsTab.vue`
- `frontend/src/components/admin/reports/AdminExportTab.vue`

### 3. Composables
Tạo composables riêng cho nghiệp vụ:
- `frontend/src/composables/useAdminReportsSearch.js`
- `frontend/src/composables/useAdminReportsStats.js`
- `frontend/src/composables/useAdminReportsExport.js`

### 4. Domain UI blocks
Ưu tiên tách theo domain thay vì generic hóa quá sớm:
- `AdminReportsFilters.vue`
- `AdminInspectionResultsTable.vue`
- `AdminInspectionResultsMobileList.vue`
- `AdminKpiGrid.vue`
- `AdminBtsStatsBlock.vue`
- `AdminInspectorStatsBlock.vue`
- `AdminErrorStatsBlock.vue`

---

## Implementation Phases

## Phase 1 — Split monolithic view into tab components

### Task 1.1
Tạo `AdminReportsTab.vue` và di chuyển toàn bộ UI + logic liên quan tới tab Reports vào component này.

**Scope:**
- batch filter
- search cabinet
- report language select
- search result desktop table
- search result mobile cards
- download individual report
- batch PDF actions

**Verify:**
- tab Reports hiển thị y hệt trước khi refactor
- search vẫn hoạt động
- tải PDF vẫn hoạt động

### Task 1.2
Tạo `AdminStatisticsTab.vue` và di chuyển toàn bộ UI + logic tab Statistics.

**Scope:**
- KPI cards
- from/to date filter
- overview/by BTS/by inspector/by error blocks
- mobile layouts của statistics

**Verify:**
- số liệu hiển thị đúng như cũ
- filter ngày vẫn hoạt động

### Task 1.3
Tạo `AdminExportTab.vue` và di chuyển toàn bộ UI + logic tab Export.

**Scope:**
- export batch excel
- export statistics excel
- export critical errors excel

**Verify:**
- cả 3 khối export hoạt động đúng

### Task 1.4
Rút gọn [frontend/src/views/admin/ReportsView.vue](../frontend/src/views/admin/ReportsView.vue) thành shell component.

**Verify:**
- file chính chỉ còn title + tabs + render tab
- không còn logic API nặng trong shell view

---

## Phase 2 — Move state and API logic into composables

### Task 2.1
Tạo `useAdminReportsSearch.js`.

**State:**
- `batches`
- `selectedBatchId`
- `searchCabinet`
- `reportLang`
- `searchResults`
- `loadingSearch`

**Actions:**
- `loadBatches()`
- `searchReports()`
- `debouncedSearch()`

**Verify:**
- logic search không còn nằm trực tiếp trong component tab
- tab Reports chỉ consume composable

### Task 2.2
Tạo `useAdminReportsStats.js`.

**State:**
- `stats`
- `btsData`
- `inspectorData`
- `errorData`
- `filterFrom`
- `filterTo`
- `loadingOverview`
- `loadingBts`
- `loadingInspector`
- `loadingErrors`
- `hasLoadedStats`

**Actions:**
- `loadStats()`
- `loadStatsIfNeeded()`

**Verify:**
- component Statistics nhẹ hơn rõ rệt
- có thể load lazy theo tab

### Task 2.3
Tạo `useAdminReportsExport.js`.

**State:**
- `exportBatchId`
- `exportErrorBatchId`

**Actions:**
- `downloadBatchExcel()`
- `downloadStatsExcel()`
- `downloadCriticalErrorsExcel()`
- nếu phù hợp, gom thêm các batch PDF downloads vào composable chung

**Verify:**
- toàn bộ export action nằm ngoài UI component

---

## Phase 3 — Introduce granular download state

### Problem
Biến `downloading` hiện đang khóa toàn bộ các nút tải dù chỉ một action đang chạy.

### Task 3.1
Thay thế `downloading` bằng download state theo key.

**Recommended design:**
- `const activeDownloads = ref(new Set())`
- helper:
  - `startDownload(key)`
  - `finishDownload(key)`
  - `isDownloading(key)`

**Suggested keys:**
- `inspection:{id}`
- `batch-summary:{batchId}`
- `acceptance:{batchId}`
- `critical-pdf:{batchId|all}`
- `batch-excel:{batchId}`
- `stats-excel`
- `critical-excel:{batchId|all}`

**Verify:**
- chỉ nút đang tải bị disable
- tải file khác vẫn thao tác được nếu không xung đột

---

## Phase 4 — Performance optimization for data fetching

### Task 4.1
Lazy load Statistics tab.

**Current issue:**
`loadStats()` chạy ngay khi mount dù user có thể không mở tab Statistics.

**Required change:**
- chỉ gọi `loadStatsIfNeeded()` khi user mở tab `statistics` lần đầu
- giữ cache trong phiên để quay lại tab không load lại vô ích

**Verify:**
- khi mở page lần đầu chỉ load dữ liệu cần thiết cho tab Reports
- tab Statistics load lần đầu khi được mở

### Task 4.2
Add search request deduplication.

**Required change:**
- build query key từ `selectedBatchId + trimmed cabinet_code`
- nếu query hiện tại giống query gần nhất thì không gọi API lại

**Verify:**
- đổi qua lại UI không gây request trùng lặp vô ích

### Task 4.3
Cancel stale search request nếu có thể.

**Required change:**
- kiểm tra `reportService.searchInspections()` đang dùng axios/fetch theo cách nào
- nếu hỗ trợ, dùng `AbortController` hoặc cancellation tương ứng
- đảm bảo request cũ không overwrite kết quả request mới

**Verify:**
- nhập nhanh nhiều ký tự không gây race condition hiển thị sai dữ liệu

### Task 4.4
Cleanup debounce timer khi component unmount.

**Verify:**
- không còn timer treo sau khi rời trang

---

## Phase 5 — Prepare or implement pagination

### Task 5.1
Kiểm tra `reportService.searchInspections()` và response backend xem đã hỗ trợ pagination chưa.

**If backend supports pagination:**
Triển khai:
- `page`
- `perPage`
- `total`
- `lastPage`
- UI phân trang cho desktop
- mobile dùng pagination đơn giản hoặc “xem thêm”

**If backend does not support pagination:**
- giữ nguyên behavior hiện tại
- nhưng chuẩn bị state structure để dễ thêm pagination sau
- ghi chú rõ limitation trong plan/handoff nếu cần

**Verify:**
- không làm hỏng search hiện tại
- nếu pagination có sẵn thì render đúng trang hiện tại

---

## Phase 6 — Reduce duplicate desktop/mobile markup

### Task 6.1
Tách tab Reports thành các component hiển thị riêng:
- `AdminInspectionResultsTable.vue`
- `AdminInspectionResultsMobileList.vue`

**Props should include:**
- `items`
- `reportLang` nếu cần hiển thị
- `isDownloading`
- `onDownload`

**Verify:**
- markup trong tab Reports giảm đáng kể
- behavior không đổi

### Task 6.2
Tách statistics blocks theo domain:
- `AdminBtsStatsBlock.vue`
- `AdminInspectorStatsBlock.vue`
- `AdminErrorStatsBlock.vue`

**Verify:**
- logic mỗi block rõ ràng
- loading / empty / table / mobile card nằm trong block tương ứng

---

## Phase 7 — Standardize data states

### Task 7.1
Chuẩn hóa pattern loading / data / empty cho các block.

**Option A:** tạo wrapper component chung.

**Option B:** giữ local nhưng dùng cùng pattern và class naming.

**Preferred for safety:** Option B trước, chỉ tạo wrapper nếu thực sự giảm lặp rõ rệt.

**Verify:**
- loading state đồng nhất
- empty state rõ ràng, không mâu thuẫn logic

### Task 7.2
Cân nhắc skeleton loading cho KPI/table blocks thay spinner nếu effort thấp.

**Verify:**
- layout ổn định hơn trong lúc chờ dữ liệu

---

## Phase 8 — Clean template and CSS

### Task 8.1
Loại bỏ inline styles trong template và chuyển thành CSS classes có tên rõ ràng.

**Examples from current file:**
- compact mobile card header spacing
- tight body spacing
- count/error text alignment
- full-width export info blocks

**Verify:**
- template sạch hơn
- style không thay đổi đáng kể về mặt thị giác

### Task 8.2
Di chuyển constants/derived options khỏi template inline.

**Examples:**
- tabs config
- language options
- critical errors batch options

**Verify:**
- template giảm object literal inline
- render ổn định hơn

---

## File Plan

### Existing files to modify
- [frontend/src/views/admin/ReportsView.vue](../frontend/src/views/admin/ReportsView.vue)
- `frontend/src/services/reportService.*` (chỉ nếu cần hỗ trợ cancel/pagination)

### New files to create
- `frontend/src/components/admin/reports/AdminReportsTab.vue`
- `frontend/src/components/admin/reports/AdminStatisticsTab.vue`
- `frontend/src/components/admin/reports/AdminExportTab.vue`
- `frontend/src/components/admin/reports/AdminInspectionResultsTable.vue`
- `frontend/src/components/admin/reports/AdminInspectionResultsMobileList.vue`
- `frontend/src/components/admin/reports/AdminBtsStatsBlock.vue`
- `frontend/src/components/admin/reports/AdminInspectorStatsBlock.vue`
- `frontend/src/components/admin/reports/AdminErrorStatsBlock.vue`
- `frontend/src/composables/useAdminReportsSearch.js`
- `frontend/src/composables/useAdminReportsStats.js`
- `frontend/src/composables/useAdminReportsExport.js`

### Optional new utilities
- `frontend/src/composables/useDownloadState.js`
- `frontend/src/composables/useToast.js` (only if project does not already have one)

---

## Acceptance Criteria

### Maintainability
- [ ] `ReportsView.vue` không còn là god component.
- [ ] Logic API/search/stats/export không còn nhồi trong 1 file.
- [ ] Download state rõ nghĩa, không dùng 1 boolean chung.

### Performance
- [ ] Tab Statistics không tải dữ liệu trước khi cần.
- [ ] Search không spam request vô ích.
- [ ] Query giống nhau không gọi lại API.
- [ ] Race condition do search nhanh được giảm hoặc loại bỏ.
- [ ] Có hướng xử lý dữ liệu lớn bằng pagination hoặc state chuẩn bị cho pagination.

### UI/UX
- [ ] Giao diện giữ nguyên hoặc tốt hơn.
- [ ] Mobile layout không bị vỡ.
- [ ] Trạng thái loading/empty rõ ràng hơn.
- [ ] Chỉ đúng nút đang tải file bị disable.

### Safety
- [ ] Không đổi behavior ngoài ý muốn.
- [ ] Không làm hỏng i18n đang dùng.
- [ ] Không phát sinh lỗi import hoặc reactivity.

---

## Recommended Execution Order for Claude Code

1. Đọc `ReportsView.vue`, `reportService`, các component mobile input đang dùng.
2. Tách 3 tab component trước, chưa tối ưu sâu.
3. Tách composables cho search/stats/export.
4. Đưa lazy-load statistics vào shell/tab flow.
5. Thay `downloading` bằng keyed download state.
6. Tách subcomponents cho reports/statistics blocks.
7. Tối ưu request search: debounce cleanup, dedupe, cancellation nếu khả thi.
8. Kiểm tra pagination support và triển khai nếu backend có sẵn.
9. Dọn inline CSS và constants inline.
10. Chạy kiểm tra lỗi trên các file đã sửa.

---

## Suggested Prompt for Claude Code

Refactor trang admin reports tại [frontend/src/views/admin/ReportsView.vue](../frontend/src/views/admin/ReportsView.vue) theo kế hoạch trong file này.

Yêu cầu bắt buộc:
- giữ nguyên behavior hiện tại
- tách view thành 3 tab component riêng
- chuyển logic API/state sang composables
- thay `downloading` boolean bằng keyed download state
- lazy load tab Statistics
- tối ưu search để tránh request thừa
- giảm lặp desktop/mobile bằng component con hợp lý
- chuyển inline styles sang CSS class
- nếu backend search inspections đã hỗ trợ pagination thì triển khai phân trang; nếu chưa có thì chuẩn bị state/code structure để thêm sau

Sau khi sửa, kiểm tra lỗi cho các file đã thay đổi.

---

## Notes

### Recommended delivery strategy
Nên chia làm 2 lượt nếu muốn giảm rủi ro:

#### Round 1
- split tab components
- composables
- lazy load statistics
- keyed download state
- CSS cleanup

#### Round 2
- pagination
- request cancellation
- subcomponents cho desktop/mobile blocks
- skeleton loading

### Why this order
- giảm nguy cơ vỡ tính năng
- dễ review
- dễ rollback
- vẫn thu được lợi ích maintainability ngay từ Round 1

---

## Done When
- [ ] Có kế hoạch rõ ràng để Claude Code refactor an toàn.
- [ ] Refactor giải quyết cả maintainability lẫn performance.
- [ ] Kế hoạch đủ chi tiết để implement mà không cần suy diễn nhiều.
