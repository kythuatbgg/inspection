# Dark Mode UI Redesign

## Goal
Chuyển toàn bộ giao diện từ Light Mode (Neutral Clean Admin) sang Dark Mode OLED professional, giữ nguyên logic/API. Backup có sẵn tại branch `backup/light-mode-ui`.

## Design System

| Token | Light (cũ) | Dark (mới) |
|-------|-----------|------------|
| **Background** | `gray-50` | `#0A0E1A` (slate-950 tùy chỉnh) |
| **Surface** | `white` | `#111827` (gray-900) |
| **Surface Elevated** | `white border-gray-200` | `#1F2937` (gray-800) |
| **Border** | `gray-200` | `gray-700/50` |
| **Text Primary** | `gray-900` | `gray-100` |
| **Text Secondary** | `gray-500` | `gray-400` |
| **Primary** | `#2563EB` (blue-600) | `#3B82F6` (blue-500) — sáng hơn trên nền tối |
| **Accent/CTA** | — | `#F97316` (orange-500) |
| **Success** | `green-600` | `#22C55E` (green-500) |
| **Danger** | `red-600` | `#EF4444` (red-500) |
| **Font Heading** | Inter | **Fira Code** |
| **Font Body** | Inter | **Fira Sans** |

## Tasks

### Phase 1: Foundation (3 files)
- [ ] `tailwind.config.js` — Đổi palette sang dark tokens, thêm Fira Code/Sans font
- [ ] `style.css` — Import Google Fonts, đổi body bg/text sang dark, cập nhật `.card`, `.btn-*`, `.input-field`
- [ ] `index.html` — Thêm `<link>` Google Fonts

### Phase 2: Layouts (2 files)
- [ ] `AdminLayout.vue` — Sidebar: `bg-gray-900`, header: `bg-gray-900/80 backdrop-blur`, overlay dark
- [ ] `InspectorLayout.vue` — Tương tự AdminLayout

### Phase 3: Shared Components (6 files)
- [ ] `LoginView.vue` — Full dark bg, input styles
- [ ] `GlobalLoading.vue` — Dark spinner
- [ ] `ImageViewerModal.vue` — Dark overlay
- [ ] `MobileBottomSheet.vue` — Dark surface
- [ ] `MobileImageUploader.vue` — Dark upload area
- [ ] `InspectionDetailReadonly.vue` — Dark cards, icons giữ nguyên

### Phase 4: Admin Views (10 files)
- [ ] `DashboardView.vue`
- [ ] `BatchesView.vue` + `BatchDetailView.vue` + `BatchFormModal.vue`
- [ ] `CabinetsView.vue` + `CabinetDetailView.vue` + `CabinetFormModal.vue`
- [ ] `ChecklistsView.vue` + `ChecklistDetailView.vue`
- [ ] `UsersView.vue` + `UserDetailView.vue`
- [ ] `ProfileView.vue` + `SettingsView.vue`

### Phase 5: Inspector Views (4 files)
- [ ] `DashboardView.vue` (inspector)
- [ ] `TasksView.vue`
- [ ] `BatchDetailView.vue` (inspector)
- [ ] `InspectionView.vue`

### Phase X: Verification
- [ ] Build thành công `npm run build`
- [ ] Mở trình duyệt kiểm tra tất cả trang
- [ ] Kiểm tra responsive mobile
- [ ] Nếu không ưng → `git checkout backup/light-mode-ui` để rollback

## Quy tắc đổi class

| Light class | → Dark class |
|-------------|-------------|
| `bg-gray-50` | `bg-[#0A0E1A]` |
| `bg-white` | `bg-gray-900` |
| `border-gray-200` | `border-gray-700/50` |
| `text-gray-900` | `text-gray-100` |
| `text-gray-500` | `text-gray-400` |
| `text-gray-700` | `text-gray-300` |
| `bg-gray-100` (hover) | `bg-gray-800` |
| `shadow-sm` | `shadow-lg shadow-black/20` |
| `bg-primary-50` | `bg-primary-500/10` |

## Notes
- Không thay đổi logic, API, hoặc cấu trúc component
- Chỉ thay đổi Tailwind classes và CSS
- Rollback an toàn: `git checkout backup/light-mode-ui -- frontend/`
