# Phân tích & Kế hoạch (PLAN-inspection-form)

## 1. Mục tiêu (Goal)
- Hoàn thiện và nâng cấp logic xử lý bên trong màn hình **Inspection Form** (Kiểm tra Tủ Cáp).
- Bổ sung các tính năng còn thiếu trong quá trình đánh giá (ví dụ: upload ảnh chụp minh chứng lỗi, ghi chú, lưu nháp).

## 2. Tình trạng hiện tại (Current State)
- Form kiểm tra (`InspectionView.vue`) hiện đã load checklist từ API, cho phép chấm điểm Đạt/Không đạt và tính toán được điểm số tổng/số lỗi nghiêm trọng.
- Nút "Lưu kết quả" đã được nối với API `POST /inspections`.
- **Thiếu:** Chưa có phần đính kèm hình ảnh minh chứng khi một hạng mục bị đánh giá "Không đạt" (database `inspection_details` có cột `image_url`).

## 3. Socratic Gate (Cần User xác nhận trước khi code)
Vui lòng trả lời các câu hỏi sau để chốt phạm vi công việc:
1. **Upload ảnh minh chứng:** Khi user chọn "✗ Không đạt", có bắt buộc phải chụp/upload ảnh lỗi không? Hay chỉ là optional (không bắt buộc)?
2. **Hiển thị Camera hay Album:** Bạn muốn sử dụng Native Camera của điện thoại (chụp trực tiếp) hay muốn cho phép chọn ảnh từ thư viện (gallery), hay cả hai?
3. **Lưu trữ ảnh:** Ảnh sẽ được upload thẳng qua API riêng biệt trả về URL trước khi submit, hay gửi kèm dạng `multipart/form-data` lúc ấn Gửi bài kiểm tra?
4. **Ghi chú (Notes):** Ngoài ảnh ra, có cần thêm 1 ô Text input để user gõ ghi chú văn bản giải thích lý do Không đạt không? (Hiện db chưa có cột note cho từng item, nếu cần phải báo để thêm column).

## 4. Các bước triển khai dự kiến (Draft Implementation Plan)
- **Bước 1**: Chờ user trả lời Socratic Gate.
- **Bước 2**: [Backend] Nếu có upload ảnh hoặc notes, bổ sung API xử lý upload/cập nhật migration (nếu cần).
- **Bước 3**: [Frontend] Thêm UI Box upload ảnh (Image Uploader Component) mở ra bên dưới hạng mục mỗi khi chọn trạng thái "Không đạt".
- **Bước 4**: [Frontend] Xử lý hàm upload ảnh (gọi API upload file hoặc nhúng Base64) và map URL/Base64 vào payload `POST /inspections`.
- **Bước 5**: Kiểm tra cẩn thận UX/UI trên Mobile View (Mobile Webview Design chuẩn gập của Nuxt UI/Tailwind).
