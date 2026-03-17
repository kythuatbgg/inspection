# THÔNG TIN DỰ ÁN: FIELD SERVICE MANAGEMENT (FSM) APP - FBB INSPECTION

## 1. TỔNG QUAN DỰ ÁN (PROJECT OVERVIEW)
Xây dựng một hệ thống Web App (PWA) quản lý và thực hiện kiểm tra bảo dưỡng hạ tầng viễn thông (Tủ cáp GPON, trạm BTS). 
Ứng dụng phục vụ 2 đối tượng:
- **Admin/Quản lý:** Lên kế hoạch (chia batch), giao việc bảo dưỡng tủ cáp theo trạm BTS, xem báo cáo.
- **Nhân viên hiện trường (Inspector):** Đi tuyến, check lỗi theo checklist đa ngôn ngữ, chụp ảnh minh chứng có gắn tọa độ (watermark). Yêu cầu chạy được khi mất mạng (Offline-first).

## 2. STACK CÔNG NGHỆ (TECH STACK)
- **Backend (API & Admin Control Panel):** Laravel 11.x, PHP 8.2+, MySQL/PostgreSQL.
  - Auth: Laravel Sanctum.
  - Cấu trúc: RESTful API.
- **Frontend (Mobile Web App dành cho nhân viên):** Vue.js 3 (Composition API), Vite.
  - State Management: Pinia.
  - PWA: `vite-plugin-pwa` (Service Worker cho offline caching).
  - CSS Framework: Tailwind CSS (hoặc bộ UI component tương thích Vue).
  - Local Database: Dexie.js (wrapper cho IndexedDB).
  - Geolocation & Camera: HTML5 API, Canvas API.

## 3. THIẾT KẾ CƠ SỞ DỮ LIỆU (DATABASE SCHEMA)
Hệ thống sử dụng các bảng chính sau trên Server (Laravel Eloquent Models):

### 3.1. Master Data
- **`users`**: `id`, `name`, `username`, `password`, `role` (admin/inspector), `lang_pref` (vn/en/kh).
- **`cabinets`**: `cabinet_code` (PK), `bts_site`, `name`, `type`, `lat`, `lng`.

### 3.2. Checklist System
- **`checklists`**: `id`, `name`, `min_pass_score` (default: 80), `max_critical_allowed` (default: 1).
  - *Data mẫu: Phụ lục 2.6 (Bảo dưỡng tủ), Phụ lục 2.7 (Lắp mới).*
- **`checklist_items`**: `id`, `checklist_id`, `category`, `content_vn`, `content_en`, `content_kh`, `max_score` (int), `is_critical` (boolean).

### 3.3. Planning & Execution (Nghiệp vụ cốt lõi)
- **`inspection_batches`** (Kế hoạch/Đợt kiểm tra): `id`, `name`, `user_id` (người được giao), `checklist_id`, `start_date`, `end_date`, `status` (pending/active/completed).
- **`plan_details`** (Chi tiết tủ trong đợt): `id`, `batch_id`, `cabinet_code`, `status` (planned/done).
- **`inspections`** (Phiên kiểm tra thực tế): `id`, `user_id`, `checklist_id`, `plan_detail_id`, `cabinet_code`, `lat`, `lng`, `total_score`, `critical_errors_count`, `final_result` (PASS/FAIL), `created_at`.
- **`inspection_details`** (Kết quả từng câu hỏi): `id`, `inspection_id`, `item_id`, `is_failed` (boolean), `score_awarded`, `image_url`.

## 4. LOGIC NGHIỆP VỤ CỐT LÕI (CORE BUSINESS LOGIC)

### 4.1. Thuật toán chấm điểm (Scoring Engine)
Thực thi tại Vue.js (lúc offline) và Verify lại tại Laravel (lúc submit API):
1. **Total Score**: Bằng tổng `score_awarded` của các câu hỏi (câu nào `is_failed = true` thì `score_awarded = 0`, ngược lại bằng `max_score`).
2. **Critical Errors**: Đếm số câu có `is_failed = true` VÀ `is_critical = true`.
3. **Kết luận**: `PASS` nếu (`Total Score >= 80` AND `Critical Errors < 2`). Ngược lại là `FAIL`.

### 4.2. Logic Đa ngôn ngữ (i18n)
- Dữ liệu câu hỏi lấy từ DB có sẵn 3 cột ngôn ngữ (`content_vn`, `content_en`, `content_kh`).
- Tại Vue, Pinia store lưu `currentLanguage`. 
- UI tự động render nội dung câu hỏi dựa trên key ngôn ngữ tương ứng mà không cần reload API.

## 5. CÁC TÍNH NĂNG KỸ THUẬT QUAN TRỌNG (IMPLEMENTATION GUIDELINES FOR CLAUDE)

### 5.1. Offline-First Architecture (Vue + Dexie.js)
- **Kịch bản**: Nhân viên ở trạm BTS không có sóng 4G.
- **Flow**: 
  1. Khi có mạng (ở văn phòng), App gọi API fetch toàn bộ `Cabinets`, `Checklists`, `Checklist_Items`, và `Plan_Details` thuộc về User đó lưu vào **IndexedDB (via Dexie.js)**.
  2. Khi kiểm tra offline, App đọc/ghi dữ liệu hoàn toàn bằng Dexie.js. Phiên bản `inspections` mới tạo ra sẽ có cờ `sync_status = 'draft'`.
  3. Cài đặt **Service Worker** (Background Sync) hoặc lắng nghe sự kiện `window.addEventListener('online')` để tự động đẩy mảng dữ liệu `draft` qua API `/api/sync` của Laravel.

### 5.2. Geolocation & Watermark Camera (Bắt buộc dùng Canvas)
- Chụp ảnh qua HTML input: `<input type="file" accept="image/*" capture="environment" />`.
- Khi user chọn/chụp ảnh xong, nạp file vào hàm xử lý HTML5 `<canvas>`:
  1. Lấy GPS bằng `navigator.geolocation.getCurrentPosition()`.
  2. Vẽ ảnh lên canvas: `ctx.drawImage()`.
  3. Đóng dấu watermark: Dùng `ctx.fillText()` vẽ Tọa độ Lat/Lng, Mã tủ, Thời gian lên góc phải bên dưới ảnh.
  4. Nén và xuất ảnh: `canvas.toDataURL('image/jpeg', 0.6)`.
  5. Lưu chuỗi Base64 vào Dexie.js để chờ đồng bộ, không upload trực tiếp khi đang ở mode offline.

## 6. USER FLOWS (LUỒNG NGƯỜI DÙNG)
1. **Admin tạo Batch**: Login Laravel Admin -> Chọn Trạm BTS -> Lấy danh sách tủ cáp tương ứng -> Tạo `Inspection Batch` khoảng 85 tủ -> Gán cho 1 user.
2. **Inspector chuẩn bị**: Login Vue PWA (lúc có mạng) -> Màn hình Dashboard load Kế hoạch (Batch) -> Dữ liệu tự động cache vào IndexedDB.
3. **Inspector đi tuyến**: Chọn tủ cáp cần làm -> App lấy GPS so sánh với tọa độ gốc -> Tick form (nếu tick "Không Đạt" vào câu Critical BẮT BUỘC trigger mở Camera) -> Submit form (Lưu Local).
4. **Đồng bộ**: Khi di chuyển ra vùng có 4G -> PWA tự động push toàn bộ Base64 images và JSON data lên Laravel API -> Đổi status thành Synced. Laravel lưu ảnh vào Storage và cập nhật database.

## 7. YÊU CẦU THỰC THI DÀNH CHO CLAUDE
1. Khởi tạo project cấu trúc Laravel api-only và Vue 3 (Vite).
2. Viết Migration và Models cho cấu trúc Database ở mục 3.
3. Setup `vite-plugin-pwa` và Dexie.js trong thư mục Frontend.
4. Implement module Canvas để đóng dấu Watermark theo đặc tả mục 5.2.
5. Viết API Sync nhận bulk data từ quá trình offline.