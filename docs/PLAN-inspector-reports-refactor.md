# PLAN: Refactor Inspector Reports View

## Context

Trang Inspector Reports hiện được triển khai tại [frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue).

Màn này đã có nền tảng tốt về UI/UX:
- KPI tổng quan
- biểu đồ timeline
- top lỗi thường gặp
- danh sách biên bản
- tải PDF từng inspection
- layout mobile khá ổn

Tuy nhiên, sau khi review sâu, có một số vấn đề về correctness, maintainability và performance:
- chưa có pagination cho danh sách inspections
- search có debounce nhưng chưa chống race condition
- loading state đang quá thô ở cấp toàn trang
- error handling chỉ dừng ở `console.error`
- component đang ôm quá nhiều phần UI + data orchestration
- scope hiện tại chưa khớp hoàn toàn với phase 2 plan (thiếu filter batch)

---

## Goals

### Functional goals
- Giữ nguyên trải nghiệm hiện tại của Inspector Reports.
- Không làm mất tính năng export PDF.
- Không làm sai dữ liệu KPI/timeline/top errors/list.
- Nếu backend đã hỗ trợ pagination thì triển khai đúng ở frontend.

### Refactor goals
- Giảm độ phình của [frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue).
- Tách orchestration logic khỏi UI.
- Tách list/charts/filters thành các block nhỏ, rõ trách nhiệm.
- Chuẩn hóa loading / empty / error state.
- Làm rõ các dependency giữa filter ngày, filter result, search, pagination.

### Performance goals
- Ngăn request cũ overwrite request mới khi user search nhanh.
- Tránh render danh sách inspection quá dài.
- Giảm loading toàn trang không cần thiết.
- Chuẩn bị cấu trúc tốt để scale thêm filter batch, pagination, cache.

---

## Current Problems

## 1. Monolithic smart component
[frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue) hiện đang đồng thời xử lý:
- page header
- KPI cards
- timeline chart
- top errors chart
- search/filter form
- desktop table
- mobile cards
- date watchers
- API orchestration
- download logic
- loading logic
- formatting helpers

=> khó mở rộng và khó test.

## 2. Fetch orchestration quá tập trung
`fetchAll()` đang gọi cùng lúc:
- overview
- timeline
- top errors
- inspections

Điều này tiện ban đầu nhưng không tốt khi:
- chỉ muốn reload list
- cần loading riêng cho từng block
- muốn cache một phần dữ liệu
- cần pagination/filter nâng cao

## 3. Search chưa an toàn
Hiện chỉ debounce bằng `setTimeout`, chưa có:
- request cancellation
- latest-request guard
- cleanup timer khi unmount

=> có rủi ro race condition.

## 4. Chưa có pagination
Danh sách inspections hiện render toàn bộ kết quả.
Nếu inspector có nhiều dữ liệu thì page sẽ nặng và khó dùng.

## 5. Error UX chưa đủ
API lỗi chỉ log console, user không có phản hồi trực tiếp.

## 6. Loading quá thô
Chỉ có một `loading` cho toàn page, khiến UX bị giật khi đổi date filter.

## 7. Scope mismatch
Plan phase 2 có filter theo batch, nhưng UI hiện tại chưa có.

---

## Proposed Architecture

## 1. Keep page as composition shell
Giữ [frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue) là page-level shell, nhưng giảm trách nhiệm.

Page shell nên chủ yếu:
- gắn layout tổng thể
- phối hợp các block
- gọi composables

## 2. Split by domain blocks
Tạo các component con theo domain:
- `frontend/src/components/inspector/reports/InspectorReportsFilters.vue`
- `frontend/src/components/inspector/reports/InspectorReportsKpiGrid.vue`
- `frontend/src/components/inspector/reports/InspectorTimelineChart.vue`
- `frontend/src/components/inspector/reports/InspectorTopErrorsCard.vue`
- `frontend/src/components/inspector/reports/InspectorInspectionsTable.vue`
- `frontend/src/components/inspector/reports/InspectorInspectionsMobileList.vue`
- `frontend/src/components/inspector/reports/InspectorInspectionsSection.vue`

## 3. Move state/API orchestration into composables
Tạo composables:
- `frontend/src/composables/useInspectorReportsDashboard.js`
- `frontend/src/composables/useInspectorInspectionsList.js`
- `frontend/src/composables/useInspectorReportDownload.js`

## 4. Preserve existing services unless necessary
Giữ [frontend/src/services/inspectorStatsService.js](../frontend/src/services/inspectorStatsService.js) là service access layer.
Chỉ sửa nếu cần:
- hỗ trợ cancellation signal
- hỗ trợ pagination params

---

## Implementation Phases

## Phase 1 — Stabilize data flow and performance first

### Task 1.1
Tách logic dashboard và list ra hai luồng riêng.

**Dashboard data includes:**
- overview
- timeline
- topErrors

**List data includes:**
- inspections
- searchQuery
- filterResult
- filterBatch (nếu triển khai)
- page/perPage/total meta

**Required result:**
- đổi search/filter result không reload lại KPI/timeline/top errors
- đổi date có thể reload cả dashboard + list nếu đúng business rule

**Verify:**
- search chỉ ảnh hưởng inspections list
- result filter chỉ ảnh hưởng inspections list
- date filter vẫn cập nhật đầy đủ các block cần thiết

### Task 1.2
Refactor `fetchAll()` thành các hàm nhỏ hơn.

**Suggested functions:**
- `fetchOverview()`
- `fetchTimeline()`
- `fetchTopErrors()`
- `fetchDashboardData()`
- `fetchInspections()`

**Verify:**
- dễ đọc hơn rõ rệt
- từng khối có thể load lại độc lập

### Task 1.3
Tách loading state.

**Required states:**
- `isInitialLoading`
- `isRefreshingDashboard`
- `isLoadingInspections`
- `isDownloadingReport` hoặc keyed download state

**Verify:**
- đổi search không khóa toàn màn hình
- đổi date không làm page flicker toàn bộ

---

## Phase 2 — Fix search correctness and request handling

### Task 2.1
Giữ debounce cho search nhưng thêm cleanup timer khi unmount.

**Verify:**
- không còn timer treo khi rời page

### Task 2.2
Thêm request dedupe cho inspections query.

**Query key should include:**
- `search`
- `result`
- `batch_id` nếu có
- `from`
- `to`
- `page`

Nếu key không đổi thì không gọi API lại.

**Verify:**
- thao tác lặp lại cùng filter không tạo request thừa

### Task 2.3
Ngăn race condition khi search nhanh.

**Preferred approach:**
- kiểm tra service có thể nhận `signal` / cancel token
- nếu có, hủy request inspections cũ
- nếu chưa có, dùng `requestId` guard trước khi commit `inspections`

**Verify:**
- nhập nhanh không làm list bị overwrite bởi kết quả cũ

---

## Phase 3 — Implement or prepare pagination

### Task 3.1
Xác minh response của `getInspections()` trong [frontend/src/services/inspectorStatsService.js](../frontend/src/services/inspectorStatsService.js).

**Check:**
- có `data`, `meta`, `links` không
- backend có hỗ trợ `page`, `per_page` hoặc tương tự không

### Task 3.2
Nếu backend có pagination, triển khai frontend pagination.

**Required state:**
- `page`
- `perPage`
- `total`
- `lastPage`

**UI:**
- desktop: pagination row dưới table
- mobile: nút `Xem thêm` hoặc pagination đơn giản

**Verify:**
- chuyển trang đúng dữ liệu
- filter/search reset hoặc giữ page hợp lý

### Task 3.3
Nếu backend chưa có pagination thực tế:
- vẫn refactor list composable theo hướng hỗ trợ pagination sau này
- ghi chú limitation rõ trong handoff nếu cần

---

## Phase 4 — Add missing batch filter or align scope

### Task 4.1
Kiểm tra API inspections đã hỗ trợ `batch_id` chưa.

### Task 4.2
Nếu có hỗ trợ:
- load danh sách batch phù hợp cho inspector
- thêm `MobileBottomSheet` filter batch vào khối filters
- kết nối vào query inspections

### Task 4.3
Nếu không hỗ trợ:
- cập nhật docs/scope tương ứng
- không giả lập filter batch ở frontend

**Verify:**
- scope thực tế khớp với docs
- UI không hứa tính năng chưa có backend support

---

## Phase 5 — Split view into maintainable UI blocks

### Task 5.1
Tạo `InspectorReportsFilters.vue`.

**Scope:**
- date from/to
- search input
- result filter
- report language selector
- batch filter nếu có

**Props/Emits:**
- controlled inputs hoặc v-model pattern

**Verify:**
- phần filter không còn nằm rải rác trong page

### Task 5.2
Tạo `InspectorReportsKpiGrid.vue`.

**Scope:**
- 4 KPI cards

**Verify:**
- page shell ngắn hơn
- KPI UI tách biệt và dễ test snapshot hơn

### Task 5.3
Tạo `InspectorTimelineChart.vue`.

**Scope:**
- timeline bars
- month formatting
- empty state
- legend

**Verify:**
- logic bar height không nằm trong page trừ khi dùng composable helper

### Task 5.4
Tạo `InspectorTopErrorsCard.vue`.

**Scope:**
- top errors list
- horizontal bars
- empty state

### Task 5.5
Tạo `InspectorInspectionsSection.vue` và tách tiếp:
- `InspectorInspectionsTable.vue`
- `InspectorInspectionsMobileList.vue`

**Verify:**
- page shell chỉ điều phối state + render blocks

---

## Phase 6 — Improve UX states

### Task 6.1
Tách empty state của inspections thành 2 trường hợp:
- chưa có biên bản nào
- không có kết quả phù hợp với filter/search

### Task 6.2
Thêm error feedback cho user.

**Minimum:**
- toast khi download lỗi
- message nhẹ khi load inspections lỗi
- message nhẹ khi dashboard load lỗi

### Task 6.3
Cân nhắc skeleton/loading giữ layout ổn định cho:
- KPI grid
- charts
- inspections section

**Verify:**
- UX khi refresh mượt hơn
- không “nháy trắng” cả trang

---

## Phase 7 — Clean code and formatting improvements

### Task 7.1
Đưa constants tĩnh ra ngoài `setup` nếu không phụ thuộc reactive state.

**Candidate:**
- `langOptions`

### Task 7.2
Giữ `resultOptions` reactive nếu locale có thể đổi runtime.

### Task 7.3
Không dùng index làm key cho top errors nếu có field ổn định hơn.

**Preferred key:**
- `error_content`
- hoặc `error_content + category`

### Task 7.4
Ổn định shape dữ liệu `overview` khi map response.

**Goal:**
- tránh `undefined` nếu backend trả thiếu field

---

## Phase 8 — Optional composables

### Task 8.1
Tạo `useInspectorReportsDashboard.js`.

**State:**
- `overview`
- `timeline`
- `topErrors`
- `isInitialLoading`
- `isRefreshingDashboard`
- `dashboardError`

**Actions:**
- `fetchOverview()`
- `fetchTimeline()`
- `fetchTopErrors()`
- `refreshDashboard()`

### Task 8.2
Tạo `useInspectorInspectionsList.js`.

**State:**
- `inspections`
- `searchQuery`
- `filterResult`
- `filterBatch`
- `page`
- `perPage`
- `total`
- `isLoadingInspections`
- `inspectionsError`

**Actions:**
- `fetchInspections()`
- `resetFilters()`
- `goToPage()`
- `setSearchQueryDebounced()` hoặc watch nội bộ composable

### Task 8.3
Tạo `useInspectorReportDownload.js`.

**State:**
- `activeDownloads` hoặc `downloadingId`

**Actions:**
- `downloadReport(insp, lang)`
- toast success/error nếu cần

---

## File Plan

### Existing files to modify
- [frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue)
- [frontend/src/services/inspectorStatsService.js](../frontend/src/services/inspectorStatsService.js) (nếu cần cancellation/pagination support)
- [frontend/src/services/reportService.js](../frontend/src/services/reportService.js) (chỉ nếu cần thay đổi hỗ trợ cancellation hoặc naming)

### New files to create
- `frontend/src/components/inspector/reports/InspectorReportsFilters.vue`
- `frontend/src/components/inspector/reports/InspectorReportsKpiGrid.vue`
- `frontend/src/components/inspector/reports/InspectorTimelineChart.vue`
- `frontend/src/components/inspector/reports/InspectorTopErrorsCard.vue`
- `frontend/src/components/inspector/reports/InspectorInspectionsSection.vue`
- `frontend/src/components/inspector/reports/InspectorInspectionsTable.vue`
- `frontend/src/components/inspector/reports/InspectorInspectionsMobileList.vue`
- `frontend/src/composables/useInspectorReportsDashboard.js`
- `frontend/src/composables/useInspectorInspectionsList.js`
- `frontend/src/composables/useInspectorReportDownload.js`

### Optional utilities
- `frontend/src/composables/useAsyncRequestState.js`
- `frontend/src/composables/useDebouncedQuery.js`

---

## Acceptance Criteria

### Correctness
- [ ] Search không còn bị race condition dễ thấy.
- [ ] API lỗi không còn im lặng với người dùng.
- [ ] Empty state phân biệt được “không có dữ liệu” và “không có kết quả lọc”.
- [ ] Nếu backend có pagination, frontend dùng đúng metadata phân trang.

### Performance
- [ ] Search không tạo request thừa khi query không đổi.
- [ ] Timer debounce được cleanup khi unmount.
- [ ] Đổi search/result không reload toàn dashboard.
- [ ] Danh sách inspections không còn render theo kiểu khó scale.

### Maintainability
- [ ] Page file ngắn gọn hơn đáng kể.
- [ ] Logic dashboard/list/download được tách rõ.
- [ ] List desktop/mobile không còn làm page file quá dài.

### UX
- [ ] Loading không còn chặn toàn trang trong các thao tác nhỏ.
- [ ] Download lỗi có feedback cho user.
- [ ] Mobile layout vẫn đẹp sau refactor.

### Scope alignment
- [ ] Filter batch được bổ sung nếu backend support.
- [ ] Nếu không support, docs/scope được cập nhật rõ ràng.

---

## Recommended Execution Order for Claude Code

1. Đọc lại [frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue) và [frontend/src/services/inspectorStatsService.js](../frontend/src/services/inspectorStatsService.js).
2. Xác minh inspections API có pagination không.
3. Tách `fetchAll()` thành dashboard flow + inspections flow.
4. Tách loading state.
5. Chống race condition cho search và cleanup timer.
6. Thêm error feedback tối thiểu.
7. Bổ sung pagination nếu backend support.
8. Bổ sung batch filter nếu backend support.
9. Tách component con cho filters, charts, inspections list.
10. Nếu cần, tách tiếp composables.
11. Kiểm tra lỗi các file đã thay đổi.

---

## Suggested Prompt for Claude Code

Refactor trang inspector reports tại [frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue) theo kế hoạch trong file này.

Yêu cầu bắt buộc:
- giữ nguyên behavior hiện tại
- ưu tiên sửa correctness và performance trước
- tách dashboard flow khỏi inspections list flow
- search phải có debounce cleanup và không bị race condition dễ thấy
- không dùng loading toàn trang cho mọi thao tác nhỏ
- thêm error feedback thực tế cho user
- nếu backend inspections đã hỗ trợ pagination thì triển khai pagination
- nếu backend hỗ trợ batch filter thì thêm filter batch
- refactor theo hướng tách component con/composables hợp lý, nhưng không over-engineer

Sau khi sửa, kiểm tra lỗi cho các file đã thay đổi.

---

## Delivery Strategy

### Round 1 — Correctness + performance stabilization
- split dashboard flow / inspections flow
- loading states
- search debounce cleanup
- request dedupe / race condition protection
- error UI tối thiểu
- pagination nếu backend đã có sẵn

### Round 2 — Maintainability + component split
- split filters / charts / list blocks
- optional composables
- batch filter nếu cần
- polish empty states

### Why this order
- sửa lỗi có khả năng ảnh hưởng dữ liệu trước
- giảm rủi ro production issues sớm
- sau đó mới tối ưu kiến trúc và độ sạch mã

---

## Done When
- [ ] Có kế hoạch chi tiết để refactor inspector reports an toàn.
- [ ] Plan bao phủ cả correctness, performance, maintainability và UX.
- [ ] Claude Code có thể triển khai mà không cần suy diễn nhiều.
