# Replace All Icons with Lucide

## Goal
Thay thế toàn bộ các thẻ `<svg>` vẽ icon cứng (hardcoded) trong dự án Vue 3 thành các component từ thư viện `lucide-vue-next` để đồng bộ thiết kế, dễ bảo trì và làm nhẹ template.

## Tasks
- [x] Task 1: Cài đặt thư viện `lucide-vue-next` → Verify: Chạy lại `npm run dev` không lỗi.
- [x] Task 2: Thay thế icon ở các Layout (`AdminLayout.vue`, `InspectorLayout.vue`) → Verify: Sidebar và Header hiển thị đúng icon mới.
- [x] Task 3: Thay thế icon ở nhóm Admin Views (`CabinetsView`, `BatchesView`, `BatchDetailView`, `UsersView`, `SettingsView`, v.v.) → Verify: Các data table, button và thanh search hiển thị mượt mà.
- [x] Task 4: Thay thế icon ở nhóm Inspector Views (`DashboardView`, `TasksView`, `InspectionView`, v.v.) → Verify: Các icon trạng thái, list hiển thị chuẩn xác.
- [x] Task 5: Thay thế icon ở các Components dùng chung (`MobileBottomSheet`, `MobileDatePicker`, `*Modal.vue`) & `LoginView` → Verify: Đảm bảo không bỏ sót các action nhỏ.

## Done When
- [x] Hệ thống hiển thị 100% bằng Lucide Icon.
- [x] Không còn thẻ `<svg>` thô nào bị sót ở giao diện chức năng chính.
- [x] Dự án không bị lỗi Warning import (Unknown element) trên Terminal.
