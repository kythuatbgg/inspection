# Phân tích & Kế hoạch (PLAN-loading-spinner)

## 1. Mục tiêu (Goal)
- Thay thế hoặc bổ sung hiệu ứng loading hiện tại (thường là skeleton `animate-pulse`) bằng Loading Spinner chuẩn của hệ thống trong quá trình gọi API (load data).
- Cải thiện UX để người dùng nhận biết rõ ràng khi hệ thống đang xử lý dữ liệu.

## 2. Phạm vi (Scope)
- **Global vs Local**: Xác định xem Loading Spinner sẽ hiển thị toàn màn hình (Global Overlay Spinner) mỗi khi gọi API, hay chỉ hiển thị cục bộ (Local Spinner) thay cho các khối Skeleton hiện tại.
- **Component**: Sử dụng UI Component đã quy định (ví dụ `<UIcon name="i-heroicons-arrow-path" class="animate-spin" />` hoặc CSS Spinner có sẵn).
- Các view bị ảnh hưởng: `DashboardView.vue`, `BatchDetailView.vue`, `InspectionView.vue`, `TasksView.vue` và các trang Admin nếu có yêu cầu.

## 3. Các thành phần kỹ thuật cần cập nhật
1. **Tạo Component Spinner (nếu dùng Global)**:
   - Tạo `GlobalLoading.vue` (Overlay chứa spinner quay).
2. **Cập nhật Logic State (Store / Axios Interceptor)**:
   - Cách 1: Gắn vào Axios Interceptor (`frontend/src/services/api.js`) để tự động show/hide spinner toàn cục.
   - Cách 2: Sửa trực tiếp biến `const loading = ref(true)` ở từng component để render Spinner thay vì Skeleton.
3. **Cập nhật UI trong các Views**:
   - Xóa `div.animate-pulse` (Skeleton) và thay bằng `<div class="flex justify-center p-10"><UIcon name="..." class="animate-spin" /></div>` trong các thẻ `<template v-if="loading">`.

## 4. Socratic Gate (Cần User xác nhận trước khi code)
Vui lòng trả lời các câu hỏi sau để chốt phương án:
1. **Bạn muốn Loading Spinner dạng nào?** 
   - *Option A*: Global Overlay (Phủ mờ mờ toàn màn hình khi đang gọi bất kỳ API nào, block thao tác).
   - *Option B*: Local Spinner (Hiện 1 vòng xoay quay quay nhỏ ở giữa trang thay thế cho các bộ khung xám Skeleton hiện tại).
2. **Có giữ lại Skeleton không?** Hay muốn xóa bỏ hoàn toàn trạng thái hiển thị khung xám (Skeleton) để xài Spinner 100%?
3. **Bạn muốn apply Spinner cho toàn bộ project (gắn qua API Interceptor) hay chỉ sửa trong 4 file của Inspector Portal vừa làm?**

## 5. Các bước triển khai (Implementation Plan)
- **Bước 1**: Chờ user xác nhận Scope từ Socratic Gate.
- **Bước 2**: Implement Spinner Component hoặc setup interceptor.
- **Bước 3**: Refactor các thẻ `<template v-if="loading">` trong các Views.
- **Bước 4**: Chạy `python .agent/scripts/ux_audit.py .` để verify tính mượt mà của UX.
