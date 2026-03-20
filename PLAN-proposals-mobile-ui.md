# Cải thiện UI Mobile cho ProposalsView

## Goal
Đồng bộ ngôn ngữ thiết kế (Minimalist & Swiss Design) của trang Quản lý Đề xuất (`ProposalsView.vue`) với các màn hình đã làm trước đó (`TasksView`, `BatchDetailView`), tối ưu trải nghiệm chạm trên Mobile.

## Tasks
- [ ] Task 1: Cập nhật Header & Nút "Tạo đề xuất" → Verify: Nút tạo nổi bật, bo góc chuẩn `rounded-xl`, kích thước chữ đồng quy.
- [ ] Task 2: Tái cấu trúc thanh Tabs (Chờ duyệt, Đã duyệt, Từ chối) → Verify: Cuộn ngang mượt mà (`scrollbar-hide`), màu sắc tab active thống nhất với hệ thống.
- [ ] Task 3: Redesign Card hiển thị lô đề xuất → Verify: Bố cục Flexbox rõ ràng, Icon Lucide thay thế hiển thị sắc nét, Badges trạng thái dùng palette màu chuẩn (Emerald/Amber/Red).
- [ ] Task 4: Cải thiện Empty/Loading States → Verify: Kế thừa block Empty State chuẩn với Icon lớn và text xám nhạt như `TasksView`.

## Done When
- [ ] Giao diện thẻ bài (Card) hiển thị gọn gàng, không vỡ layout trên màn hình nhỏ.
- [ ] Các trạng thái Đang tải (Loading) và Trống (Empty) đồng nhất 100% với giao diện chung.
- [ ] Code sạch sẽ, loại bỏ class CSS thừa.
