# Review & Fix Brief: `inspector/tasks` load chậm

## Bối cảnh
Màn hình inspector tasks hiện load chậm rõ rệt do frontend đang phát sinh quá nhiều API request theo kiểu N+1.

### Route liên quan
- [frontend/src/router/index.js](../frontend/src/router/index.js#L123-L125)

### View hiện tại
- [frontend/src/views/inspector/TasksView.vue](../frontend/src/views/inspector/TasksView.vue)

### API client liên quan
- [frontend/src/services/api.js](../frontend/src/services/api.js)
- [frontend/src/services/batchService.js](../frontend/src/services/batchService.js)

### Backend liên quan
- [backend/routes/api.php](../backend/routes/api.php#L72-L78)
- [backend/app/Http/Controllers/Api/PlanDetailController.php](../backend/app/Http/Controllers/Api/PlanDetailController.php)
- [backend/app/Http/Controllers/Api/InspectionController.php](../backend/app/Http/Controllers/Api/InspectionController.php)
- [backend/app/Http/Controllers/Api/BatchController.php](../backend/app/Http/Controllers/Api/BatchController.php)

---

## Root cause

Trong [frontend/src/views/inspector/TasksView.vue](../frontend/src/views/inspector/TasksView.vue#L132-L181), hàm `fetchData()` đang chạy theo flow:

1. Gọi API lấy danh sách batch:
   - [frontend/src/views/inspector/TasksView.vue](../frontend/src/views/inspector/TasksView.vue#L135)
2. Với mỗi batch, gọi tiếp API lấy plans:
   - [frontend/src/views/inspector/TasksView.vue](../frontend/src/views/inspector/TasksView.vue#L143)
3. Với mỗi plan có `status === 'done'`, lại gọi tiếp API inspection riêng:
   - [frontend/src/views/inspector/TasksView.vue](../frontend/src/views/inspector/TasksView.vue#L150)

### Hệ quả
Ví dụ có:
- 20 batch
- mỗi batch 10 plan
- 80 plan đã hoàn thành

Thì số request xấp xỉ:

$$
1 + 20 + 80 = 101
$$

Đây là nguyên nhân chính làm màn `inspector/tasks` load chậm.

---

## Phát hiện quan trọng

Backend của endpoint lấy plans theo batch đã eager load `inspection` sẵn.

Tại [backend/app/Http/Controllers/Api/PlanDetailController.php](../backend/app/Http/Controllers/Api/PlanDetailController.php#L22-L28):

- `->with(['cabinet', 'inspection'])`

Điều đó có nghĩa là API:
- [backend/routes/api.php](../backend/routes/api.php#L72)

đã trả về mỗi `plan` kèm `inspection`.

### Kết luận
Frontend hiện không tận dụng dữ liệu `inspection` đã có sẵn trong `/batches/{batch}/plans`, mà vẫn gọi thêm từng `/plans/{id}/inspection`.

Đây là phần cần fix đầu tiên.

---

## Yêu cầu fix

### 1. Refactor `TasksView.vue`
File cần sửa:
- [frontend/src/views/inspector/TasksView.vue](../frontend/src/views/inspector/TasksView.vue)

### Mục tiêu
- bỏ hoàn toàn việc gọi `/plans/{planId}/inspection` cho từng plan
- dùng trực tiếp `plan.inspection` lấy từ response `/batches/{batch}/plans`

### Cách đọc dữ liệu mong muốn
Khi map `plans` thành `tasks`, lấy:
- `const inspection = plan.inspection || null`
- `result = inspection?.final_result || null`
- `score = inspection?.total_score ?? null`

### Sau fix
Số request nên giảm còn:

$$
1 + N\ batches + 0\ inspection\ per\ plan
$$

Ví dụ 20 batch:

$$
1 + 20 = 21
$$

---

## Yêu cầu giữ nguyên behavior UI
Không thay đổi UX hiện tại. Chỉ tối ưu data fetching.

Giữ nguyên:
- tab filter `all`, `planned`, `done`
- danh sách task
- badge `Đã kiểm tra` / `Chưa kiểm tra`
- badge `ĐẠT` / `K.ĐẠT`
- hiển thị `score`
- click item để vào màn inspection

---

## Loading / UX issue phụ

Trong [frontend/src/services/api.js](../frontend/src/services/api.js#L35-L36), request nào không truyền `silent` sẽ bật global loading.

Trong màn tasks, hiện có nhiều request nối tiếp nên dễ gây cảm giác chậm hơn thực tế.

### Cần làm
- cân nhắc để request lấy plans theo batch dùng `silent: true`
- ưu tiên dùng `loading` local của page
- tránh việc loading overlay toàn cục nhấp nháy nhiều lần

Nếu vẫn muốn giữ loading toàn cục cho request đầu tiên lấy batches thì được, nhưng request con nên là `silent`.

---

## Review backend liên quan

### `PlanDetailController@index`
File:
- [backend/app/Http/Controllers/Api/PlanDetailController.php](../backend/app/Http/Controllers/Api/PlanDetailController.php#L12-L38)

Hiện tại endpoint này đã khá ổn cho use case `inspector/tasks` vì đã eager load:
- `cabinet`
- `inspection`

### Cần verify
Xác nhận `plan.inspection` trong response có đủ field:
- `final_result`
- `total_score`

Nếu đủ rồi thì không cần sửa backend cho flow chính.

---

## Tối ưu phụ nên note

Có một điểm N+1 khác ở backend, không phải root cause chính của `inspector/tasks`, nhưng nên note để fix sau nếu tiện.

Tại [backend/app/Http/Controllers/Api/BatchController.php](../backend/app/Http/Controllers/Api/BatchController.php#L65-L79):

Trong `show()`, code đang map `planDetails` và gọi:
- `\App\Models\Cabinet::find($plan->cabinet_code)`

Đây là query trong vòng lặp.

### Đề xuất
Nếu sửa thêm:
- eager load `planDetails.cabinet`
- dùng trực tiếp `$plan->cabinet`

Nhưng đây là tối ưu phụ, không phải blocker chính của issue hiện tại.

---

## Acceptance criteria

Sau khi fix, cần đạt:

1. Màn `inspector/tasks` load nhanh hơn rõ rệt
2. Network tab không còn spam request `/plans/{id}/inspection`
3. Dữ liệu task vẫn đúng:
   - `planId`
   - `cabinetCode`
   - `batchName`
   - `status`
   - `result`
   - `score`
   - `dateRange`
4. Filter `all`, `planned`, `done` vẫn hoạt động đúng
5. Không đổi route, không đổi UI behavior
6. Không gây regression cho inspector flow

---

## Gợi ý implement

Có thể refactor đoạn xử lý ở [frontend/src/views/inspector/TasksView.vue](../frontend/src/views/inspector/TasksView.vue#L143-L169) theo hướng:

- gọi `/batches/${batch.id}/plans` một lần cho mỗi batch
- map toàn bộ `plans` thành `tasks`
- lấy `result` và `score` trực tiếp từ `plan.inspection`
- request con nên truyền `silent: true`

Ngoài ra nên giữ code sạch:
- gom normalize task object vào một helper nhỏ nếu cần
- tránh nested `try/catch` quá sâu
- không over-engineer

---

## Deliverables mong muốn
Hãy trả về:

1. Phân tích ngắn root cause
2. File đã sửa
3. Tóm tắt số request trước / sau
4. Nếu có, note thêm tối ưu phụ ở backend `BatchController@show`

---

## Tóm tắt ngắn gọn
Issue load chậm ở `inspector/tasks` chủ yếu do frontend gọi thừa API inspection cho từng plan đã hoàn thành, trong khi endpoint `/batches/{batch}/plans` đã trả `inspection` sẵn. Cần refactor `TasksView.vue` để tận dụng `plan.inspection`, giảm request từ khoảng 101 xuống còn khoảng 21 trong ví dụ thực tế, đồng thời giảm tác động của global loading bằng cách dùng `silent: true` cho các request con.
