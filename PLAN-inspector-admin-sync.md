# Đồng bộ Inspector UI theo chuẩn Admin UI (Minimalist & Swiss Design)

## Goal
Loại bỏ hoàn toàn các mảng tối (như Sidebar nền đen `bg-slate-900`) hoặc các thành phần shadow cầu kỳ trên Inspector UI, đưa toàn bộ quy chuẩn thiết kế về **Minimalist & Swiss Design** giống exacly như `AdminLayout.vue`.
Điều này bao gồm Sidebar sáng màu, phong cách Typography tinh tế, các thẻ User nhỏ gọn theo khối bo góc, và Icon phẳng (không padding).

## Các thay đổi chính

### 1. `InspectorLayout.vue` (Desktop Sidebar)
- Đổi nền `bg-slate-900 text-white` -> `bg-white border-r border-slate-200`.
- Chỉnh sửa vùng Logo: Sử dụng Icon Lucide nguyên bản `text-primary-600`, không dùng box background bao quanh.
- Item điều hướng (Nav): Hover state `bg-slate-50 text-slate-900`, Active state `bg-primary-50 text-primary-700 font-medium`.
- Vùng User Profile (dưới cùng): Bọc trong khối bo góc `rounded-lg bg-primary-100` cho Avatar, text xám cho Sub-title, nút Logout flat `text-slate-400 hover:text-slate-600 hover:bg-slate-200/50`.

### 2. `InspectorLayout.vue` (Desktop & Mobile Main Layout)
- Thêm `Top Header` chuẩn cho nền Desktop (như `AdminLayout.vue`) với chiều cao `h-16`, chứa `pageTitle` và nút báo trạng thái Online. (Hoặc giữ nguyên Header gộp nếu Master-detail yêu cầu).
- Chỉnh sửa Mobile Header: Chuyển Icon logo thành dạng phẳng `text-primary-600`, Title dùng `font-heading`.

### 3. Cập nhật các Views (Dashboard, Tasks)
- Bỏ bớt bóng mờ `shadow-md`, sử dụng màu trắng tĩnh `bg-white`, đường viền cực thanh `border-slate-200`.
- Typography bọc bằng `font-heading` theo quy chuẩn Admin.

## Verification
- Resize màn hình để thấy Sidebar desktop màu trắng, không còn lệch tông so với Admin.
- Mobile header tối giản.
