# PLAN: Inspector Reports — Follow-up Fixes

## Context

Inspector Reports đã được refactor theo hướng tốt hơn:
- tách component
- tách composables
- thêm loading/error state rõ hơn
- cải thiện search với debounce + abort
- chuẩn bị pagination

Tuy nhiên sau vòng review mới nhất, vẫn còn một số vấn đề cần xử lý tiếp để chốt chất lượng production.

Mục tiêu của file này là liệt kê **các fix follow-up theo đúng thứ tự ưu tiên**, để Claude Code có thể xử lý tiếp mà không cần suy diễn.

---

## Priority 1 — Fix correctness trước

### Task 1: Fix mapping paginated response cho inspections

**File:** [frontend/src/composables/useInspectorInspectionsList.js](../frontend/src/composables/useInspectorInspectionsList.js)

### Problem
Logic hiện tại có khả năng detect response có pagination nhưng chưa set `inspections.value` đúng trong mọi shape response.

### Required changes
- Xác minh shape response thực tế của endpoint `/inspector/stats/inspections`
- Hỗ trợ đúng các trường hợp phổ biến:
  - `{ data: [...], meta: {...} }`
  - `{ data: { data: [...], meta: {...} } }`
  - array thường nếu backend chưa paginate
- Khi response có pagination, phải luôn map đúng:
  - `inspections.value`
  - `total.value`
  - `lastPage.value`
  - `hasPagination.value`

### Verify
- Có pagination meta thì list vẫn hiển thị đúng dữ liệu
- Không còn tình huống list rỗng do map sai response

---

### Task 2: Luôn gửi `page` và `per_page` ngay từ request đầu tiên

**File:** [frontend/src/composables/useInspectorInspectionsList.js](../frontend/src/composables/useInspectorInspectionsList.js)

### Problem
Hiện request đầu có thể chưa gửi `page/per_page` vì logic phụ thuộc `hasPagination`, trong khi `hasPagination` chỉ được biết sau response đầu tiên.

### Required changes
- Luôn gửi:
  - `page`
  - `per_page`
- Sau đó mới dùng response để xác định backend có paginate thực sự hay không

### Verify
- Request đầu tiên luôn có params phân trang
- Frontend vẫn hoạt động với cả API có paginate và API chưa paginate

---

## Priority 2 — Fix kiến trúc chưa sạch

### Task 3: Bỏ cơ chế toast global qua `window.__inspectorReportsToast`

**Files:**
- [frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue)
- [frontend/src/composables/useInspectorReportDownload.js](../frontend/src/composables/useInspectorReportDownload.js)

### Problem
Composable download đang gọi toast qua global mutable state trên `window`.

### Required changes
- Refactor sang callback rõ ràng hơn:
  - `onSuccess`
  - `onError`
- Hoặc dùng toast composable chung nếu project đã có pattern sẵn
- Không dùng `window.__inspectorReportsToast`

### Verify
- Toast success/error vẫn hoạt động
- Không còn global state để truyền toast

---

### Task 4: Làm rõ flow async của `refreshAll()`

**File:** [frontend/src/views/inspector/InspectorReportsView.vue](../frontend/src/views/inspector/InspectorReportsView.vue)

### Problem
Hiện `refreshAll()` đang `await` dashboard nhưng không `await` inspections list, làm flow hơi khó hiểu.

### Required changes
Chọn một trong hai hướng và làm rõ bằng code:

#### Option A — Parallel load
Dùng `Promise.all()` để load dashboard và inspections song song.

#### Option B — Intentional split
Nếu cố ý load dashboard trước rồi list sau, cần code rõ chủ đích và comment ngắn giải thích.

### Recommended
Ưu tiên Option A.

### Verify
- Initial load flow rõ ràng
- Không mập mờ về việc refresh tổng có gồm inspections hay không

---

## Priority 3 — Hoàn tất scope feature

### Task 5: Xác minh và bổ sung batch filter nếu backend support

**Files khả năng liên quan:**
- [frontend/src/components/inspector/reports/InspectorReportsFilters.vue](../frontend/src/components/inspector/reports/InspectorReportsFilters.vue)
- [frontend/src/composables/useInspectorInspectionsList.js](../frontend/src/composables/useInspectorInspectionsList.js)
- service/API liên quan nếu cần

### Problem
Composable list đã có `filterBatch`, nhưng UI chưa expose batch filter.

### Required changes
- Xác minh backend inspections endpoint có support `batch_id` không
- Nếu có support:
  - thêm batch filter vào UI
  - nối vào query params
- Nếu không support:
  - bỏ `filterBatch` để tránh state thừa
  - hoặc để TODO rõ ràng nếu thật sự cần giữ chỗ

### Verify
- UI và backend support khớp nhau
- Không còn state/filter bị treo không dùng

---

## Priority 4 — Hardening dữ liệu và maintainability

### Task 6: Ổn định shape của `overview`

**File:** [frontend/src/composables/useInspectorReportsDashboard.js](../frontend/src/composables/useInspectorReportsDashboard.js)

### Problem
`overview` đang bị replace bằng object response thô, có thể thiếu field nếu backend trả thiếu dữ liệu.

### Required changes
- Tạo `DEFAULT_OVERVIEW`
- Khi map response, merge với default shape

### Verify
- KPI luôn có shape ổn định
- Không bị `undefined` nếu thiếu field từ API

---

### Task 7: Reuse các hàm fetch riêng trong dashboard composable

**File:** [frontend/src/composables/useInspectorReportsDashboard.js](../frontend/src/composables/useInspectorReportsDashboard.js)

### Problem
Composable đã có:
- `fetchOverview()`
- `fetchTimeline()`
- `fetchTopErrors()`

Nhưng `refreshDashboard()` vẫn lặp logic gọi service/mapping riêng.

### Required changes
- Reuse các hàm fetch con trong `refreshDashboard()`
- Hoặc xóa các hàm không cần thiết nếu không dùng
- Tránh duplication mapping và error handling

### Verify
- Dashboard composable gọn hơn
- Logic map response chỉ nằm ở một chỗ chính

---

## Priority 5 — Polish

### Task 8: Rà lại i18n keys và empty/error states

**Files khả năng liên quan:**
- [frontend/src/components/inspector/reports/InspectorInspectionsSection.vue](../frontend/src/components/inspector/reports/InspectorInspectionsSection.vue)
- các file locale tương ứng

### Required changes
- Kiểm tra các key đã tồn tại đầy đủ chưa:
  - `inspector.noFilteredResults`
  - `inspector.clearFilters`
  - `common.retry`
  - `common.errorLoadData`
- Đảm bảo logic hiển thị đúng thứ tự:
  - loading
  - error
  - data
  - filtered empty
  - true empty

### Verify
- User luôn thấy trạng thái đúng
- Không có text fallback sai hoặc thiếu i18n

---

### Task 9: Locale hóa `formatMonth()`

**File:** [frontend/src/components/inspector/reports/InspectorTimelineChart.vue](../frontend/src/components/inspector/reports/InspectorTimelineChart.vue)

### Problem
Timeline label đang hardcode theo kiểu `T1`, `T2`.

### Required changes
- Format theo locale hiện tại nếu app có đổi ngôn ngữ runtime
- Hoặc ít nhất không hardcode cứng tiếng Việt

### Verify
- Timeline label phù hợp với ngôn ngữ hiển thị của app

---

### Task 10: Gộp helper `formatDate()` để tránh lặp

**Files:**
- [frontend/src/components/inspector/reports/InspectorInspectionsTable.vue](../frontend/src/components/inspector/reports/InspectorInspectionsTable.vue)
- [frontend/src/components/inspector/reports/InspectorInspectionsMobileList.vue](../frontend/src/components/inspector/reports/InspectorInspectionsMobileList.vue)

### Required changes
- Gom `formatDate()` vào helper chung hoặc util nhẹ
- Không tạo abstraction thừa

### Verify
- Không còn duplicate helper nhỏ ở desktop/mobile list

---

## Required Execution Order

1. Fix paginated response mapping
2. Luôn gửi `page/per_page`
3. Bỏ toast global `window.__inspectorReportsToast`
4. Chuẩn hóa `refreshAll()`
5. Xác minh rồi thêm hoặc dọn `batch filter`
6. Ổn định `overview` default shape
7. Reuse fetch functions của dashboard
8. Rà i18n / empty state / month label / formatDate

---

## Acceptance Criteria

### Correctness
- [ ] Paginated response map đúng vào `inspections`
- [ ] Request đầu tiên luôn gửi `page/per_page`
- [ ] Search/list không bị sai dữ liệu do response shape

### Architecture
- [ ] Không còn dùng `window.__inspectorReportsToast`
- [ ] `refreshAll()` có flow rõ ràng
- [ ] Không còn state/filter thừa không dùng

### UX
- [ ] Toast success/error vẫn hoạt động đúng
- [ ] Empty/error states rõ ràng
- [ ] Timeline label không hardcode sai ngôn ngữ

### Maintainability
- [ ] `overview` có shape ổn định
- [ ] Dashboard composable giảm duplication
- [ ] Helper format ngày không bị lặp ở nhiều component

---

## Prompt for Claude Code

Hãy xử lý follow-up fixes cho inspector reports theo đúng thứ tự ưu tiên trong file này.

Yêu cầu bắt buộc:
- sửa correctness trước
- không đổi behavior ngoài ý muốn
- không làm vỡ mobile layout
- không để lại state hoặc code chết
- sau khi sửa, kiểm tra lỗi cho các file đã thay đổi
