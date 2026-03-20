# Language Selection — Inspector Checklist

## Overview

Inspector có thể chọn ngôn ngữ hiển thị (VN/EN/KH) cho checklist items trong quá trình kiểm tra. Ngôn ngữ được chọn persist trong `localStorage`, giữ nguyên xuyên suốt phiên làm việc.

## Design Decisions

| Decision | Choice | Reason |
|---|---|---|
| Cách chọn | Dropdown ở header | Touch-friendly, không chiếm diện tích |
| Mặc định | English (`en`) | User confirmed |
| Persistence | `localStorage` (`inspection_lang`) | Không lưu vào user account |
| Re-fetch khi đổi ngôn ngữ | Không | Frontend tự chọn `content` từ 3 bản dịch đã có |
| Lưu vào user account | Không | User confirmed |

## Data Flow

```
Backend (InspectionController::show)
  └── checklist_items[] → category_vn/en/kh + content_vn/en/kh
  └── inspection.details[].item → category_vn/en/kh + content_vn/en/kh

Frontend
  └── useInspectionLang() → currentLang (localStorage)
  └── getContent(item) → item[`content_${lang}`] || item.content_en
  └── getCategory(item) → item[`category_${lang}`] || item.category || 'Chung'
```

## Database

- Migration: `2026_03_20_100000_add_category_multilingual_to_checklist_items.php`
- Thêm 3 cột: `category_vn`, `category_en`, `category_kh` (nullable string)

## Files Changed

### Backend
- `backend/database/migrations/2026_03_20_100000_add_category_multilingual_to_checklist_items.php` **(MỚI)**
- `backend/app/Models/ChecklistItem.php` — thêm 3 trường vào `$fillable`
- `backend/app/Http/Controllers/Api/InspectionController.php`
  - Transform `checklist_items` thêm `category_vn/en/kh` + `content_vn/en/kh`
  - Transform `inspection.details[].item` thêm `category_vn/en/kh`
- `backend/app/Http/Controllers/Api/ChecklistController.php`
  - `show()`: transform items thêm `category_vn/en/kh`
  - `storeItem()` / `updateItem()`: validation thêm 3 trường mới
  - `duplicate()`: copy đầy đủ 3 trường category

### Frontend
- `frontend/src/composables/useInspectionLang.js` — thêm `getCategory()`
- `frontend/src/views/inspector/InspectionView.vue` — thêm dropdown + `getCategory()` + `getContent()`
- `frontend/src/components/inspection/InspectionDetailReadonly.vue` — thêm dropdown + `getCategory()` + `getContent()`
- `frontend/src/views/admin/ChecklistDetailView.vue` — thêm 3 input category +o modal form, hiển thị category bản dịch bên dưới mỗi item

## API Response

### `GET /api/plans/{planId}/inspection`

```json
{
  "data": {
    "id": 1,
    "details": [
      {
        "id": 1,
        "item_id": 1,
        "is_failed": false,
        "item": {
          "id": 1,
          "category": "Vệ sinh",
          "category_vn": "Vệ sinh",
          "category_en": "Cleanliness",
          "category_kh": "ស្រស់",
          "content_vn": "...",
          "content_en": "...",
          "content_kh": "...",
          "max_score": 10,
          "is_critical": false
        }
      }
    ]
  },
  "checklist_items": [
    {
      "id": 1,
      "category": "Vệ sinh",
      "category_vn": "Vệ sinh",
      "category_en": "Cleanliness",
      "category_kh": "ស្រស់",
      "content_vn": "...",
      "content_en": "...",
      "content_kh": "...",
      "max_score": 10,
      "is_critical": false
    }
  ],
  "plan": { ... }
}
```

## Usage

```js
import { useInspectionLang } from '@/composables/useInspectionLang.js'

// Dùng trong <script setup>
const { currentLang, LANG_OPTIONS, getContent, getCategory } = useInspectionLang()

// Template
// {{ getContent(item) }}
// {{ getCategory(item) }}
// :value="currentLang" @change="currentLang = $event.target.value"
```
