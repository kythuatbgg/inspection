# View Inspection Details for Review

## Goal
Tạo chức năng xem chi tiết kết quả kiểm tra (hình ảnh tổng quan, điểm số, các lỗi vi phạm và hình minh họa) dạng Slideover/Modal trong giao diện Admin Batch Detail để người quản lý có thể xem kỹ trước khi Duyệt/Từ chối.

## Tasks
- [x] Task 1: Tạo component `frontend/src/components/inspection/InspectionDetailReadonly.vue` chuyên hiển thị dữ liệu read-only dạng tóm tắt báo cáo kiểm tra (ảnh tổng quan, thông tin tủ, các hạng mục đạt/chưa đạt kèm ảnh minh họa) → Verify: Component load được dữ liệu inspection và render form tĩnh đẹp mắt.
- [x] Task 2: Chỉnh sửa `frontend/src/views/admin/BatchDetailView.vue` tích hợp Nuxt UI `<USlideover>` (hoặc Modal). Cấu hình biến trạng thái `isOpenSlideover` và `currentInspectionId` → Verify: Đã thêm thẻ `<USlideover>` và load được component `InspectionDetailReadonly`.
- [x] Task 3: Thêm nút "Xem" (View) vào danh sách lưới (Desktop table và Mobile cards) của `BatchDetailView.vue`. Khi bấm vào nút này sẽ gọi API sửa dụng tool `api.get('/plans/{planId}/inspection')` để lấy toàn bộ dữ liệu chi tiết kiểm tra và mở Slideover → Verify: Click nút mở ra Slideover hiển thị đúng dữ liệu (chỉ hiển thị nếu tủ có inspection).
- [x] Task 4: Thêm panel 2 nút "Duyệt" và "Từ chối" cố định ở phần Footer của `USlideover` (hoặc ở dưới cùng) để người quản lý thao tác trực tiếp mà không cần đóng cửa sổ xem chi tiết → Verify: Các nút thao tác call API `reviewPlan` thành công, lưu log, tải lại danh sách tủ và đóng Slideover.

## Done When
- [x] Quản lý có thể xem chi tiết tường tận một lượt kiểm tra tủ (bao gồm điểm, phần đạt, phần lỗi + ảnh), từ đó ra quyết định Duyệt/Từ chối dễ dàng.
