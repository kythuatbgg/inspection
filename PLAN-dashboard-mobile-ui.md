# Cải thiện UI Mobile cho DashboardView

## Goal
Tối ưu hóa bảng điều khiển (Dashboard) của Inspector để áp dụng chuẩn sắc nét của `mobile-webview-design`, đồng thời thiết lập layout Grid linh hoạt (Responsive) hỗ trợ hiển thị đẹp mắt trên cả thiết bị di động (Pro Max layout) và màn hình rộng (Desktop layout).

## Tasks
- [ ] Task 1: Tái cấu trúc Wrapper & Desktop Grid → Verify: Container chính có `min-w-0`, `pb-24` (Mobile) và `mx-auto max-w-5xl` (Desktop). Layout grid giãn thành `md:grid-cols-4` khi màn hình rộng.
- [ ] Task 2: Bo tròn sâu (Modern Radius) & Polish thẻ Welcome → Verify: Header gradient có độ bo `rounded-[24px]`.
- [ ] Task 3: Cải thiện lưới thống kê (Stats Grid) → Verify: Card Tổng nhiệm vụ nổi bật, padding ôm chuẩn `p-4` đến `p-5`, typography Typography chuẩn nét.
- [ ] Task 4: Nâng cấp Card hiển thị Lô kiểm tra (Active Batches) → Verify: Thanh tiến độ dày hơn (`h-2`), hiệu ứng tương tác mượt, nhãn Status được thiết kế dạng badge nhỏ tinh tế.

## Done When
- [ ] Dashboard hiển thị siêu mượt, dễ quyét mắt (scanability) và các nút chạm thân thiện ở kích thước 375px.
- [ ] Trên Desktop/Tablet, màn hình không bị bể dòng hoặc card bị kéo dài quá mức.
