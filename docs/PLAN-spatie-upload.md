# Chuyển đổi Upload sang Spatie Media Library

## 1. Mục tiêu (Goal)
Chuyển hoàn toàn hệ thống upload ảnh tự build (Laravel Storage thuần) sang **Spatie Media Library** để:
- Quản lý ảnh gắn trực tiếp vào Model (Polymorphic relation)
- Tự động tạo thumbnail/responsive images 
- Tự động xóa file khi xóa record
- Quản lý nhiều collection ảnh (overall_photos, failure_proof)

## 2. Hiện trạng (Current State)
| Thành phần | Cách hoạt động hiện tại |
|---|---|
| `UploadController.php` | Nhận file → `$file->store('images/inspections', 'public')` → trả URL |
| `inspections.overall_photos` | Cột JSON chứa mảng URL string |
| `inspection_details.image_url` | Cột string chứa 1 URL |
| `MobileImageUploader.vue` | Gọi `POST /api/upload` → nhận URL → emit lên parent |
| `SyncController.php` | Cũng dùng `image_url` khi sync offline |

## 3. Kế hoạch triển khai (Implementation Plan)

### Phase 1: Cài đặt Spatie Media Library
- `composer require spatie/laravel-medialibrary`
- `php artisan vendor:publish --provider="Spatie\MediaLibrary\MediaLibraryServiceProvider" --tag="medialibrary-migrations"`
- `php artisan migrate`

### Phase 2: Cập nhật Models

#### `Inspection.php`
- Implement `HasMedia` interface, use `InteractsWithMedia` trait
- Đăng ký collection: `overall_photos` (nhiều ảnh, min 4)
- Xóa cột `overall_photos` JSON khỏi migration (dùng Spatie thay thế)

#### `InspectionDetail.php`
- Implement `HasMedia` interface, use `InteractsWithMedia` trait
- Đăng ký collection: `failure_proof` (1 ảnh duy nhất)
- Giữ cột `image_url` tạm thời để backward compatible, sau migrate data thì xóa

### Phase 3: Cập nhật Controllers

#### `UploadController.php` → Giữ lại nhưng refactor
- Thay đổi: Upload file vào thư mục temp trước
- Khi submit inspection, move file từ temp vào Spatie media collection
- HOẶC: Upload trực tiếp vào model tạm (cần có model ID trước)

#### Phương án thay thế (Khuyến nghị): Upload trực tiếp khi submit
- `UploadController` vẫn lưu vào temp storage
- Khi `InspectionController@store` được gọi, dùng URL từ temp để `addMediaFromUrl()` hoặc `addMediaFromDisk()` vào collection Spatie

#### `InspectionController.php`
- `store()`: Sau khi tạo `Inspection`, gọi `addMedia()` cho overall_photos
- Sau khi tạo từng `InspectionDetail`, gọi `addMedia()` cho failure_proof
- `show()`: Trả kèm `->getMedia('overall_photos')` và `->getMedia('failure_proof')`

### Phase 4: Cập nhật Frontend

#### `MobileImageUploader.vue`
- Không cần đổi nhiều — vẫn gọi `POST /api/upload` để lấy URL/path
- URL trả về có thể thay đổi format (Spatie dùng `/storage/media/...`)

#### `InspectionView.vue`
- Không đổi logic — vẫn gửi array URL trong payload
- Backend tự xử lý chuyển URL thành Spatie media

### Phase 5: Migration dữ liệu cũ
- Script artisan để migrate `overall_photos` JSON → Spatie collection
- Script artisan để migrate `inspection_details.image_url` → Spatie collection
- Sau khi verify → Tạo migration xóa cột `overall_photos` và `image_url`

## 4. Files bị ảnh hưởng

| File | Hành động |
|---|---|
| `composer.json` | Thêm `spatie/laravel-medialibrary` |
| `Inspection.php` | Implement `HasMedia`, `InteractsWithMedia`, registerMediaCollections |
| `InspectionDetail.php` | Implement `HasMedia`, `InteractsWithMedia`, registerMediaCollections |
| `UploadController.php` | Refactor hoặc giữ nguyên (upload temp) |
| `InspectionController.php` | Thêm logic `addMedia()` khi store, `getMedia()` khi show |
| `SyncController.php` | Cập nhật sync logic |
| `MobileImageUploader.vue` | Có thể cần cập nhật URL format |
| `InspectionView.vue` | Minimal changes |

## 5. Rủi ro & Lưu ý
- ⚠️ Spatie lưu vào bảng `media` riêng → cần chạy migration
- ⚠️ Dữ liệu cũ nếu có cần migrate sang Spatie
- ⚠️ `UploadController` hiện tại sẽ cần refactor vì Spatie cần gắn media vào 1 Model cụ thể — không upload "trôi nổi" được
- ✅ Frontend gần như không đổi — vẫn gửi URL, backend tự handle
