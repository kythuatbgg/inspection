# Add and Remove Cabinets in Batch

## Goal
Cho phép Admin và Manager thêm hoặc xóa tủ thiết bị khỏi lô kiểm tra khi lô chưa kết thúc (`status !== 'completed'`).

## Constraints & Considerations
- **Thêm tủ**: Mở Modal có tính năng **Tìm kiếm và chọn tủ (Search & Select)** để dễ dàng chọn tủ từ danh sách database (không bắt nhập tay dễ sai mã). Sau khi chọn, gọi API Gửi mảng các `cabinet_codes` đã chọn để tạo mới các bản ghi `PlanDetail`.
- **Xóa tủ**: Cần chặn xóa hoặc yêu cầu xác nhận 2 lớp nếu tủ đó đã được kiểm tra (tức là đã có `inspection` data đi kèm). Để an toàn, API sẽ xóa `PlanDetail` (và tự động cascade xóa `inspection` nếu database setup cascade, hoặc phải xóa thủ công `inspection` trước khi xóa `PlanDetail`).

## Tasks
- [ ] Task 1: Backend - Thêm hàm `addCabinets` và `removeCabinet` trong `BatchController.php` (hoặc tạo controller mới).
- [ ] Task 2: Backend - Đăng ký 2 API route `POST /batches/{batch}/cabinets` và `DELETE /batches/{batch}/plans/{plan}` trong `routes/api.php` với middleware `manager`.
- [ ] Task 3: Frontend - Thêm hàm gọi API `addCabinetsToBatch` và `removeCabinetFromBatch` vào `batchService.js`.
- [ ] Task 4: Frontend - Giao diện `BatchDetailView.vue`: thêm bộ **Search & Select tủ thiết bị** để chọn và bấm "Thêm tủ" (lý tưởng: gọi API filter exclude các tủ đã có trong lô).
- [ ] Task 5: Frontend - Giao diện `BatchDetailView.vue`: thêm nút "Xóa" ở từng dòng tủ (chặn nếu `batch.status === 'completed'`).

## Done When
- [ ] Admin/Manager có thể Mở modal -> Nhập mã tủ -> Bấm Thêm -> Lô xuất hiện thêm tủ vừa nhập.
- [ ] Admin/Manager có thể bấm nút Xóa ở 1 tủ -> Xác nhận -> Tủ bị loại khỏi lô.
- [ ] Khi lô đã kết thúc (`completed`), các nút Thêm/Xóa tủ biến mất.
