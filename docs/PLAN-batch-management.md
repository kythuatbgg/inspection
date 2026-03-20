# Quản lý Batch cho Admin/Manager

## Goal
Admin/Manager có thể: sửa thông tin batch, xóa batch, duyệt kết quả kiểm tra từng tủ, kết thúc batch với báo cáo tổng hợp.

## Hiện trạng

| Chức năng | Có chưa? | Ghi chú |
|---|---|---|
| Tạo batch | ✅ | `POST /batches` |
| Xem batch + danh sách tủ | ✅ | `GET /batches/{id}` |
| Sửa thông tin batch | ❌ | `update()` chỉ đổi `status`, không sửa name/date/inspector |
| Xóa batch | ❌ | Không có route `DELETE` hoạt động |
| Duyệt kết quả từng tủ | ❌ | Không xem được inspection result trong admin |
| Kết thúc batch | ❌ | Chỉ đổi status → `completed`, không có logic verify |

## Tasks

### Backend

- [ ] **1. Sửa `BatchController@update`** → cho phép sửa `name`, `start_date`, `end_date`, `user_id`, `checklist_id`
  - Chỉ cho sửa khi batch chưa `completed`
  - Verify: `PUT /batches/1` với body `{ name: "Updated" }` → 200

- [ ] **2. Thêm `BatchController@destroy`** → xóa batch + cascade xóa `plan_details` và `inspections`
  - Chỉ cho xóa khi batch status = `pending` (chưa kiểm tra)
  - Hoặc confirm force delete nếu đã có inspection data
  - Verify: `DELETE /batches/1` → 200, batch bị xóa

- [ ] **3. API xem kết quả kiểm tra tủ** → `GET /batches/{id}/results`
  - Trả về danh sách tủ kèm: kết quả inspection (pass/fail/pending), điểm, số lỗi nghiêm trọng, ảnh tổng thể
  - Dùng cho admin review
  - Verify: trả JSON với `final_result`, `total_score`, `critical_errors_count` cho mỗi tủ

- [ ] **4. API duyệt tủ** → `PATCH /plans/{plan}/review`
  - Admin xem xét inspection → approve/reject
  - Thêm cột `review_status` (`pending`, `approved`, `rejected`) + `review_note` vào `plan_details`
  - Verify: `PATCH /plans/1/review { status: "approved" }` → 200

- [ ] **5. Kết thúc batch** → `PATCH /batches/{id}/close`
  - Kiểm tra: tất cả tủ đã kiểm tra + đã duyệt
  - Set `status = completed`, set `closed_at` timestamp
  - Trả tổng kết: số tủ đạt/không đạt, tổng điểm trung bình
  - Migration: thêm `closed_at` vào `inspection_batches`, `review_status` + `review_note` vào `plan_details`
  - Verify: batch chuyển sang `completed`, không cho sửa/xóa nữa

### Frontend — `BatchDetailView.vue`

- [ ] **6. Nút "Sửa" batch** → modal/inline edit cho name, date, inspector, checklist
  - Ẩn khi batch `completed`

- [ ] **7. Nút "Xóa" batch** → confirm dialog → gọi `DELETE /batches/{id}`
  - Chỉ hiện khi `pending`

- [ ] **8. Bảng kết quả kiểm tra** → mỗi tủ hiện: mã tủ, kết quả (ĐẠT/KHÔNG ĐẠT/chưa kiểm tra), điểm, nút "Xem chi tiết" + nút "Duyệt/Từ chối"
  - Click "Xem chi tiết" → xem ảnh + checklist kết quả
  - Click "Duyệt" → gọi `PATCH review`

- [ ] **9. Nút "Kết thúc batch"** → chỉ hiện khi tất cả tủ đã duyệt → gọi `PATCH close`
  - Hiện tổng kết trước khi confirm

## Done When
- [ ] Admin có thể sửa tên/ngày/inspector của batch chưa hoàn thành
- [ ] Admin có thể xóa batch chưa kiểm tra
- [ ] Admin xem được kết quả kiểm tra từng tủ (điểm, ảnh, pass/fail)
- [ ] Admin duyệt/từ chối từng tủ với ghi chú
- [ ] Admin kết thúc batch khi tất cả đã duyệt, hiện báo cáo tổng hợp
