# Tối ưu flow temp/permanent cho upload ảnh inspection

## Goal
Làm rõ vòng đời file upload, sửa số liệu cleanup cho đúng, và giảm orphan file mà không làm chậm luồng submit inspection.

## Tasks
- [ ] Sửa thống kê orphan file trong backend → Verify: `GET /admin/storage-stats` chỉ đếm file không còn được `Inspection.overall_photos` hoặc `InspectionDetail.image_url` tham chiếu.
- [ ] Validate đầu vào cleanup API → Verify: `POST /admin/storage-cleanup` chỉ nhận `hours` hợp lệ (`0, 1, 6, 24, 72, 168` hoặc integer >= 0 theo rule đã chọn) và trả lỗi 422 khi gửi giá trị sai.
- [ ] Tối ưu UI cleanup admin → Verify: [frontend/src/views/admin/SettingsView.vue](frontend/src/views/admin/SettingsView.vue) mặc định chọn `24 giờ`, và khi chọn `0` phải có xác nhận trước khi chạy.
- [ ] Làm rõ khái niệm temp/used trong giao diện → Verify: màn hình settings hiển thị đúng nhãn hoặc tách rõ `orphan files` và `used files`, không còn hiểu nhầm toàn bộ `images/inspections` là file tạm.
- [ ] Thiết kế lại upload vào thư mục temp riêng → Verify: file mới upload đi vào `images/temp`, không đi thẳng vào thư mục permanent.
- [ ] Chốt flow finalize khi submit inspection → Verify: khi lưu inspection thành công, ảnh hợp lệ được move từ temp sang thư mục permanent phù hợp và DB chỉ lưu path permanent.
- [ ] Bổ sung xoá file temp khi user bỏ ảnh trước lúc submit → Verify: thao tác xoá ảnh trong form gọi API xoá temp file chưa dùng và file biến mất khỏi storage.
- [ ] Dọn nợ kỹ thuật media cũ → Verify: xác định rõ giữ hay bỏ Spatie Media trong flow inspection; code, stats, và log không còn trạng thái lai giữa URL trực tiếp và media library.
- [ ] Kiểm tra scheduler cleanup thực tế → Verify: môi trường chạy `schedule:run` hoặc `schedule:work`, và command `uploads:cleanup --hours=24` thực sự chạy được theo lịch.

## Done When
- [ ] Số liệu cleanup phản ánh đúng orphan file thực tế.
- [ ] Flow upload có ranh giới rõ giữa temp và permanent.
- [ ] User xoá ảnh không còn để lại orphan file không cần thiết.
- [ ] Cleanup tự động và thủ công đều an toàn, dễ hiểu, và dễ vận hành.

## Notes
- Ưu tiên làm trước 4 việc đầu vì ít rủi ro, sửa nhanh, và xử lý được sai logic hiện tại.
- Việc move `temp -> permanent` trên cùng disk local thường không chậm đáng kể; chi phí chính vẫn là upload ban đầu, không phải bước move.
- Nếu muốn giảm độ phức tạp triển khai, có thể chia 2 đợt: (1) sửa stats/validation/UI, (2) tách temp/permanent + API xoá temp file.
