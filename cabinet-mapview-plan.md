# Tối ưu hóa Bản đồ Tủ cáp (Cabinet Mapview)

## Goal
Hoàn thiện giao diện của bản đồ tủ cáp theo yêu cầu: chỉ hiển thị dữ liệu đang phân trang (khớp với list), gom nhóm các tủ cùng mã `bts_site` thành một điểm duy nhất trên bản đồ, và tối ưu hiển thị responsive.

## Tasks
- [ ] Task 1: **Đồng bộ Dữ liệu Phân trang (Pagination)** -> Xóa `fetchMapData` API độc lập, sử dụng chung array `cabinets` cho cả `<CabinetMap>` và table/list. Di chuyển component Phân trang ra ngoài để cả Map và List đều dùng được.
  → Verify: Chuyển qua lại List và Map, dữ liệu hiển thị đúng 10 tủ/trang như đang query. Bấm trang 2 ở Map cũng reload lại data.
- [ ] Task 2: **Gom nhóm Tủ theo BTS Site tại Map** -> Trong `CabinetMap.vue`, xử lý gộp các tủ trong mảng theo `bts_site`. Tạo Custom `L.divIcon` hiển thị số lượng tủ tại điểm đó thay vì marker mặc định.
  → Verify: Nhìn trên bản đồ thấy các điểm màu sắc, ghi số lượng (ví dụ: BTS X có 3 tủ, hiển thị vòng tròn số 3).
- [ ] Task 3: **Popup Danh sách Tủ** -> Khi click vào cụm BTS trên vòng tròn số, hiện popup liệt kê danh sách các mã tủ của BTS đó kèm nút/liên kết "Chi tiết".
  → Verify: Click vào marker chứa số 3, popup sổ ra ghi tên BTS và liệt kê 3 tủ cáp, bấm vào nhảy sang trang chi tiết tủ.
- [ ] Task 4: **UI/UX Responsive & Loading** -> Chỉnh chiều cao map thành responsive (vd `h-[calc(100vh-300px)]`), map nằm gọn trên màn hình mobile.
  → Verify: Test trên Mobile và Desktop đảm bảo bản đồ hiển thị vừa màn hình.

## Done When
- [ ] Số lượng tủ hiển thị trên bản đồ chính xác bằng số tủ đang hiển thị ở chế độ List (ví dụ đang ở trang 1 có 10 tủ, bản đồ sẽ hiển thị 10 tủ đó).
- [ ] Hợp nhất các tủ có cùng `bts_site` vào 1 marker ghi số lượng tủ.
- [ ] Chuyển trang/tìm kiếm cũng tự động cập nhật lại bản đồ mà không cần fetch riềng.
