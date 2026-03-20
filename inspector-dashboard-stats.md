# Inspector Dashboard Stats Optimization

## Goal
Tối ưu lại khu vực hiển thị số liệu (Stats/KPIs) trên `DashboardView.vue` theo triết lý "Function over Form" của Swiss Minimalism, giảm thiểu tình trạng "box fatigue" (quá nhiều thẻ viền bo) bằng cách gộp thành một bảng mạch lạc (Unified Insight Panel) với layout chia cột kết hợp tinh gọn các chỉ số quan trọng nhất.

## Tasks
- [ ] Task 1: Gộp 5 thẻ thống kê rời rạc hiện tại thành một thẻ tổng quan duy nhất (Unified Panel) sử dụng cấu trúc `grid` kết hợp `divide-x divide-slate-200` (trên Desktop) và `grid-cols-2` hoặc `divide-y` (trên Mobile) nhằm giảm thiểu border/shadow rối mắt. → Verify: Dashboard chỉ còn 1 dải báo cáo thông minh ở đầu trang.
- [ ] Task 2: Cấu trúc lại các chỉ số cốt lõi thành 4 KPI chính (VD: Lô đang xử lý, Tổng nhiệm vụ (tủ), Đã hoàn thành, Tiến độ tổng) thay thế bố cục Hero cũ. → Verify: Lược bỏ các chỉ số thừa (như Chưa kiểm tra - có thể tự ngầm hiểu).
- [ ] Task 3: Nâng cấp Typography chuẩn Swiss cho các stat (Sử dụng nhãn `text-slate-500 uppercase tracking-widest` và biến số `text-3xl md:text-4xl font-heading text-slate-900`). → Verify: Hierarchy các lớp chữ cực kỳ phân minh.
- [ ] Task 4: Bổ sung Micro-Visuals linh hoạt: Chuyển stat "Tổng tiến độ" từ định dạng Text đơn thuần thành một kết hợp giữa Text và thanh mini Progress Bar. → Verify: Phần hiển thị % đi kèm với thanh trạng thái tự làm đầy trực quan.
- [ ] Task 5: Rà soát Responsive layout đảm bảo bảng thống kê vẫn hiển thị dạng Grid (ví dụ 2x2) rõ ràng, cân đối trên màn hình Mobile. → Verify: Thay đổi kích thước trình duyệt để kiểm tra dòng kẻ phân cách.

## Done When
- [ ] Khu vực số liệu trên cùng của `DashboardView.vue` đã trở thành một Unified Panel nguyên khối, sạch sẽ, không thừa đường viền và phân cấp thông tin xuất sắc chuẩn Swiss Design.
