# Cabinet import/export flow

## Scope
Tài liệu này tổng hợp toàn bộ flow `cabinet` liên quan đến:
- tải file mẫu import
- import danh sách cabinet từ file CSV/XLSX/XLS
- export danh sách cabinet hiện có
- export file kết quả sau import

Mục tiêu là để Claude Code có thể đọc nhanh, hiểu đúng luồng hiện tại, và biết rõ các điểm đang lệch giữa UI, API, model, và test.

---

## 1. Entry points

### Frontend
- View chính: [CabinetsView.vue](file:///d:/DEV/Claude/inspection/frontend/src/views/admin/CabinetsView.vue)
- Service gọi API: [cabinetService.js](file:///d:/DEV/Claude/inspection/frontend/src/services/cabinetService.js)
- Form CRUD phụ trợ: [CabinetFormModal.vue](file:///d:/DEV/Claude/inspection/frontend/src/components/admin/CabinetFormModal.vue)
- Detail view: [CabinetDetailView.vue](file:///d:/DEV/Claude/inspection/frontend/src/views/admin/CabinetDetailView.vue)

### Backend
- Route API: [api.php](file:///d:/DEV/Claude/inspection/backend/routes/api.php)
- Controller xử lý: [CabinetController.php](file:///d:/DEV/Claude/inspection/backend/app/Http/Controllers/Api/CabinetController.php)
- Model persistence: [Cabinet.php](file:///d:/DEV/Claude/inspection/backend/app/Models/Cabinet.php)
- Feature test: [CabinetControllerTest.php](file:///d:/DEV/Claude/inspection/backend/tests/Feature/CabinetControllerTest.php)

---

## 2. API routes liên quan cabinet

Các route đều nằm trong nhóm `auth:sanctum`, nên mọi import/export đều yêu cầu user đã đăng nhập.

- `GET /api/cabinets/template` → `downloadTemplate()`
- `GET /api/cabinets/map` → `map()`
- `POST /api/cabinets/import` → `import()`
- `GET /api/cabinets/export` → `export()`
- `GET|POST /api/cabinets/export-result` → `exportResult()`
- `Route::apiResource('cabinets', CabinetController::class)`
  - `GET /api/cabinets` → list
  - `GET /api/cabinets/{cabinetCode}` → detail
  - `POST /api/cabinets` → create
  - `PUT /api/cabinets/{cabinetCode}` → update
  - `DELETE /api/cabinets/{cabinetCode}` → delete

Nguồn: [api.php#L15-L28](file:///d:/DEV/Claude/inspection/backend/routes/api.php#L15-L28)

---

## 3. Model cabinet thực tế

`Cabinet` đang dùng:
- primary key: `cabinet_code`
- non-incrementing key
- mass assignable chỉ có:
  - `cabinet_code`
  - `bts_site`
  - `lat`
  - `lng`
  - `note`

Nguồn: [Cabinet.php#L15-L35](file:///d:/DEV/Claude/inspection/backend/app/Models/Cabinet.php#L15-L35)

### Hệ quả quan trọng
Các field như `name`, `type` đang được controller export/test nhắc đến, nhưng **không có trong `fillable` hiện tại**. Điều này làm xuất hiện lệch pha giữa code và test.

---

## 4. Flow list cabinet nền tảng

Đây là flow nền vì import/export đều quay lại list này.

### Frontend flow
1. `CabinetsView.vue` gọi `fetchCabinets()` khi `onMounted()`.
2. `fetchCabinets()` build params từ:
   - `pagination.current_page`
   - `pagination.per_page`
   - `filters.search`
   - `filters.area` → map sang `bts_site`
3. `cabinetService.getCabinets(params)` gọi `GET /cabinets`.
4. Backend `index()`:
   - filter `bts_site` nếu có
   - search theo `cabinet_code` nếu có
   - paginate theo `per_page`
   - `orderBy('cabinet_code')`
5. Response paginator trả thẳng về frontend.
6. Frontend gán:
   - `cabinets.value = response.data || []`
   - pagination meta từ `current_page`, `per_page`, `total`, `last_page`, `from`, `to`

### Điểm cần nhớ
- Search UI hiện ghi chung là `Tìm kiếm...`, nhưng backend chỉ search `cabinet_code`, không search `name` hay `bts_site`.
- Map view dùng route riêng `GET /cabinets/map`.

Nguồn:
- [CabinetsView.vue#L447-L474](file:///d:/DEV/Claude/inspection/frontend/src/views/admin/CabinetsView.vue#L447-L474)
- [cabinetService.js#L9-L12](file:///d:/DEV/Claude/inspection/frontend/src/services/cabinetService.js#L9-L12)
- [CabinetController.php#L17-L35](file:///d:/DEV/Claude/inspection/backend/app/Http/Controllers/Api/CabinetController.php#L17-L35)

---

## 5. Flow download template import

### Frontend
1. User mở import modal từ nút `Import`.
2. Trong modal bấm `Tải file mẫu`.
3. `downloadTemplate()` gọi `cabinetService.downloadTemplate()`.
4. Service gọi `GET /cabinets/template` với `responseType: 'blob'`.
5. Frontend tạo `blob URL`, tạo thẻ `a`, tải file tên `cabinet_template.csv`.

### Backend
`downloadTemplate()` stream CSV trực tiếp:
- Header: `cabinet_code,bts_site,lat,lng,note`
- Có 2 dòng sample data
- Trả về:
  - `Content-Type: text/csv`
  - `Content-Disposition: attachment; filename="cabinet_template.csv"`
- Có BOM UTF-8 để Excel đọc tiếng Việt tốt hơn

### Kết luận
- Dù comment/test có chỗ nói Excel, implementation thực tế đang là **CSV template**, không phải `.xlsx`.

Nguồn:
- [cabinetService.js#L89-L94](file:///d:/DEV/Claude/inspection/frontend/src/services/cabinetService.js#L89-L94)
- [CabinetController.php#L109-L134](file:///d:/DEV/Claude/inspection/backend/app/Http/Controllers/Api/CabinetController.php#L109-L134)

---

## 6. Flow import cabinet

### Frontend UI flow
1. User mở modal import trong `CabinetsView.vue`.
2. Chọn file bằng input `accept=".csv,.xlsx,.xls"`.
3. `handleFileSelect()` lưu file vào `selectedFile` và reset `importResult`.
4. Bấm `Import` → `importCabinets()` chạy.
5. Frontend:
   - set `importing = true`
   - reset `importResult`
   - set progress giả lập tăng dần tới 90%
   - tạo `FormData` với key `file`
6. Gọi `cabinetService.importCabinets(formData)`.
7. Nếu thành công:
   - clear progress interval
   - set `importProgress = 100`
   - lưu `importResult = { imported, failed, message }`
   - modal không tự đóng
8. Nếu lỗi:
   - set `importResult` fallback với message lỗi
9. `finally`:
   - `importing = false`
   - `importProgress = 0`

### Backend flow
`import(Request $request)`:
1. Validate file `csv,xlsx,xls`.
2. CSV thì đọc `str_getcsv`, Excel thì đọc qua `PhpSpreadsheet`.
3. Bỏ header row đầu tiên.
4. Map cột:
   - `0` → `cabinet_code`
   - `1` → `bts_site`
   - `2` → `lat`
   - `3` → `lng`
   - `4` → `note`
5. Validate từng dòng.
6. `updateOrCreate` theo `cabinet_code`.
7. Lưu `results` vào session key `cabinet_import_results`.
8. Trả JSON gồm `message`, `imported`, `failed`, `errors`, `results`.

### Persistence behavior
- Import upsert theo `cabinet_code`
- Không xóa record cũ không có trong file
- Không xử lý `name`, `type`

Nguồn:
- [CabinetsView.vue#L517-L562](file:///d:/DEV/Claude/inspection/frontend/src/views/admin/CabinetsView.vue#L517-L562)
- [cabinetService.js#L64-L74](file:///d:/DEV/Claude/inspection/frontend/src/services/cabinetService.js#L64-L74)
- [CabinetController.php#L168-L280](file:///d:/DEV/Claude/inspection/backend/app/Http/Controllers/Api/CabinetController.php#L168-L280)

---

## 7. Flow export kết quả import

### Frontend
1. Sau import, UI hiện nút `Tải file kết quả chi tiết` nếu có kết quả.
2. `downloadImportResult()` gọi `cabinetService.exportResult()`.
3. Service gọi `GET /cabinets/export-result` với `responseType: 'blob'`.
4. Frontend tải file `import_results.csv`.

### Backend
`exportResult()`:
- đọc session `cabinet_import_results`
- stream CSV với header `cabinet_code,bts_site,status,error`

### Ràng buộc cần chú ý
- Flow này phụ thuộc session hiện tại.
- Nếu request import và download không giữ đúng session/cookie, file kết quả có thể rỗng.

Nguồn:
- [CabinetsView.vue#L433-L445](file:///d:/DEV/Claude/inspection/frontend/src/views/admin/CabinetsView.vue#L433-L445)
- [cabinetService.js#L100-L105](file:///d:/DEV/Claude/inspection/frontend/src/services/cabinetService.js#L100-L105)
- [CabinetController.php#L298-L325](file:///d:/DEV/Claude/inspection/backend/app/Http/Controllers/Api/CabinetController.php#L298-L325)

---

## 8. Flow export danh sách cabinet

### Frontend
1. User bấm `Export`.
2. `exportCabinets()` gọi `cabinetService.exportCabinets()`.
3. Service gọi `GET /cabinets/export`.
4. Frontend lấy `result.data`, rồi tự build CSV bằng `join(',')`.
5. Tạo `Blob(text/csv)` và tải file `cabinets.csv`.

### Backend
`export()`:
1. Lấy toàn bộ `Cabinet::all()`.
2. Tạo mảng 2 chiều `csvData`.
3. Header hiện tại gồm:
   - `cabinet_code`
   - `bts_site`
   - `name`
   - `type`
   - `lat`
   - `lng`
   - `created_at`
   - `updated_at`
4. Trả JSON `{ data, message }`.

### Hệ quả thực tế
- Backend không stream file CSV trực tiếp.
- Frontend tự ráp CSV nên có rủi ro sai format nếu dữ liệu chứa dấu phẩy, dấu nháy, hoặc newline.
- `name`, `type` đang lệch với model hiện tại.

Nguồn:
- [CabinetsView.vue#L564-L577](file:///d:/DEV/Claude/inspection/frontend/src/views/admin/CabinetsView.vue#L564-L577)
- [cabinetService.js#L76-L82](file:///d:/DEV/Claude/inspection/frontend/src/services/cabinetService.js#L76-L82)
- [CabinetController.php#L139-L163](file:///d:/DEV/Claude/inspection/backend/app/Http/Controllers/Api/CabinetController.php#L139-L163)

---

## 9. Các mismatch / risk chính

### A. Search test lệch implementation
Backend chỉ search `cabinet_code`, nhưng test mô tả search theo `name or code`.

### B. Validation test create lệch controller
Test yêu cầu `name`, `type` là bắt buộc, nhưng controller hiện không validate hai field này.

### C. Update test lệch model
Test update `name`, nhưng `Cabinet` không fillable `name`.

### D. Frontend service test lệch contract thật
`getCabinets()` thật trả paginator object, nhưng test đang kỳ vọng trả thẳng list data.

### E. Template test đã stale
Test kỳ vọng file spreadsheet, nhưng implementation thật là CSV `text/csv`.

### F. Export CSV ở frontend không escape dữ liệu
Dùng `join(',')` dễ làm hỏng CSV nếu có ký tự đặc biệt.

### G. Export import-result phụ thuộc session
Có rủi ro file rỗng nếu session không được giữ đúng.

---

## 10. Handoff ngắn cho Claude Code

Nếu sửa flow cabinet import/export, nên làm theo thứ tự:
1. Chốt schema thật của `cabinets`, đặc biệt `name`, `type`
2. Chốt contract API list/export/template/export-result
3. Đồng bộ test với implementation hoặc sửa implementation theo spec mong muốn
4. Ưu tiên đưa export CSV về backend stream thay vì frontend tự ráp
5. Review cách lưu kết quả import nếu cần tránh phụ thuộc session

---

## 11. Tóm tắt

Flow hiện tại đã có đủ import/template/export/export-result cho cabinet, nhưng đang lệch giữa `frontend`, `controller`, `model`, và `tests`; điểm rủi ro lớn nhất là `name/type` không đồng bộ, test search sai contract, và export CSV đang được frontend tự ráp chuỗi thay vì backend stream chuẩn.
