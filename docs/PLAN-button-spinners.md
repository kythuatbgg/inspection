# Phân tích Yêu cầu
- **Mục tiêu**: Kiểm tra tất cả các button có chứa hiệu ứng xoay (spinner - component `Loader2`), đảm bảo trạng thái loading hiển thị chính xác (chỉ xoay khi đang xử lý API data) và vô hiệu hóa (disabled) nút khi đang loading để tránh double-click.
- **Tiêu chí**: Mọi nút submit/action phải có feedback trực quan và chặn tương tác thừa.

## 🔴 Cổng Socratic (Socratic Gate)
Trước khi tiến hành sửa code, vui lòng xác nhận:
1. Bạn có muốn chuẩn hóa toàn bộ icon spinner thành `Loader2 class="w-4 h-4 animate-spin"` cho các nút thông thường không?
2. Có trường hợp nào bạn muốn ưu tiên giữ trạng thái loading ngay cả khi API đã trả về lỗi (để delay) không, hay cứ có response (thành công/lỗi) là tắt spinner luôn?
3. Bạn có muốn thêm text "Đang xử lý..." kế bên spinner không, hay chỉ hiện mỗi icon spinner thay cho text cũ?

---

## Danh sách công việc (Task Breakdown)

### Phase 1: Inspector Views
- [ ] `frontend/src/views/inspector/InspectionView.vue`
  - Nút Hoàn thành kiểm tra (submitting)

### Phase 2: Admin Views
- [ ] `frontend/src/views/admin/SettingsView.vue`
  - Nút Sao lưu / Khôi phục
- [ ] `frontend/src/views/admin/ChecklistsView.vue`
  - Nút Tạo mới (creating), Xóa (deleting)
- [ ] `frontend/src/views/admin/ChecklistDetailView.vue`
  - Nút Lưu hạng mục (savingItem)
- [ ] `frontend/src/views/admin/CabinetDetailView.vue`
  - Kiểm tra trạng thái loading page
- [ ] `frontend/src/views/admin/BatchDetailView.vue`
  - Nút Đóng lô (closing)
  - Nút Mở lại lô (reopening)
  - Nút Thêm tủ (addingCabinets)
  - Nút Xóa lô (deleting)
  - Nút Đổi kĩ thuật viên (swapping)

### Phase 3: Components & Modals
- [ ] `frontend/src/components/common/MobileImageUploader.vue`
  - Trạng thái đang tải ảnh lên
- [ ] `frontend/src/components/admin/CabinetFormModal.vue`
  - Nút Lưu tủ cáp (loading / saving)

## Phân công Agent (Agent Assignments)
- **Frontend Specialist**: Xử lý logic Vue 3 (Composition API), kiểm tra các biến `ref` boolean (ví dụ: `isSubmitting.value`) có được toggle chính xác trong thẻ `try/finally` chưa.
- **UI/UX Max**: Đảm bảo spinner hiển thị đúng kích thước, không làm giật/vỡ layout nút khi xuất hiện.

## Phase X: Verification
- [ ] Mở ứng dụng, kiểm tra thủ công từng nút được liệt kê.
- [ ] Đảm bảo khi mạng chậm, nút phải disable và hiện spinner.
- [ ] Đảm bảo khi có lỗi API, nút được nhả disable và tắt spinner.
