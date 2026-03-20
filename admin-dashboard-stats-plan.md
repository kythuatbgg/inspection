# Admin Dashboard Stats Enhancement

## Goal
Nâng cấp và bổ sung đầy đủ các chỉ số nghiệp vụ trên Admin Dashboard theo chuẩn Swiss Minimalism, phân loại rõ ràng theo Thống kê Lô (Batches) và Thống kê Tủ Cáp (Plans), bao gồm cả tỷ lệ phần trăm và trạng thái kiểm tra.

## Tasks
- [ ] Task 1: Cập nhật API Backend (`/dashboard/stats`) để trả về cấu trúc dữ liệu tổng hợp mới bao gồm 2 nhóm chính: `batches` (total, completed, completed_percent, pending_approval) và `plans` (total, completed, completed_percent, passed, failed). → Verify: Dùng Insomnia/Postman hoặc Network Tab gọi API thấy response chứa cấu trúc dữ liệu mới.
- [ ] Task 2: Cập nhật logic `fetchData()` trong `frontend/src/views/admin/DashboardView.vue` nhằm tiếp nhận và map chính xác cấu trúc dữ liệu mới. → Verify: Vue Devtools hiển thị đúng các giá trị trong biến `stats` reactive.
- [ ] Task 3: Thiết kế nhóm thẻ KPI "Lô kiểm tra" trên giao diện Admin: Hiển thị Tổng số lô (Hero number) kèm các sub-metrics (Số lô hoàn thành, tỷ lệ %, và số lô đã xong tủ nhưng đang chờ duyệt). → Verify: UI dùng `grid-cols-2` hoặc `grid-cols-4`, thẻ hiển thị các số phụ ở góc dưới / dòng text nhạt.
- [ ] Task 4: Thiết kế nhóm thẻ KPI "Tủ cáp" trên giao diện Admin: Hiển thị Tổng số tủ, Số tủ hoàn thành (%), và chi tiết phân loại (Đạt / Không đạt) bằng các viền cảnh báo nhẹ (`text-success`, `text-danger`). → Verify: Thẻ Lưới cân đối theo đúng luật Swiss (không box trong box quá rườm rà).
- [ ] Task 5: Kiểm tra và hoàn thiện Responsive (Tablet/Mobile) tránh việc chữ bị tràn khi nhồi nhét nhiều sub-metrics vào 1 card. → Verify: Resize trình duyệt hiển thị tốt, có thể cân nhắc chuyển flex-col cho các dòng sub-metrics.

## Done When
- [ ] Giao diện Admin Dashboard cung cấp cho người quản trị 2 mảng thông tin rạch ròi: Tiến độ Lô (với % và số lượng chờ duyệt) và Kết quả Tủ cáp (tiến độ %, đạt, không đạt) mà không phá vỡ Grid chuẩn của Swiss Minimalism.
