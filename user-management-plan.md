# User Management với UI/UX Design

## Goal
Xây dựng chức năng User Management hoàn chỉnh với UX tối ưu theo tiêu chuẩn Field Service App

## Tasks

### Phase 1: Backend - User API

- [x] **Task 1:** Tạo UserController với CRUD methods → Verify: File tồn tại trong `app/Http/Controllers/Api/`

- [x] **Task 2:** Thêm routes vào `routes/api.php` (GET/POST/PUT/DELETE /users) → Verify: Routes đã thêm

### Phase 2: Frontend - User Service

- [x] **Task 3:** Tạo `src/services/userService.js` với getUsers, createUser, updateUser, deleteUser → Verify: File tồn tại

- [x] **Task 4:** Viết tests cho userService.js → Verify: `npm test` pass (22 tests)

### Phase 3: UI/UX Design (theo ui-ux-pro-max)

- [x] **Task 5:** Thiết kế UsersView với:
  - Table với touch-friendly (min-height 56px)
  - Search + Filter by role
  - Modal cho Create/Edit
  - Delete confirmation dialog
  → Verify: UI matches UX guidelines

- [x] **Task 6:** Thiết kế ProfileView với:
  - Form chỉnh sửa thông tin cá nhân
  - Đổi mật khẩu
  - Avatar upload
  → Verify: Form validation works

### Phase 4: Implementation

- [x] **Task 7:** Update UsersView.vue kết nối API → Verify: Hiển thị danh sách users

- [x] **Task 8:** Update ProfileView.vue kết nối API → Verify: Lưu thông tin thành công

## Done When
- [x] Admin có thể CRUD users
- [x] Users có thể xem/sửa profile
- [x] UI tuân thủ UX guidelines (touch 56px, accessible)
- [x] Tests pass (22 tests)

## UX Guidelines Applied
- Touch targets: 56px minimum
- Form labels with proper for attributes
- Loading states for async operations
- Error messages near form fields
- Confirmation dialogs for destructive actions

## Summary
- **Backend:** UserController + Routes
- **Frontend:** userService.js + UsersView.vue + ProfileView.vue
- **Tests:** 22 tests passing
- **Roles:** Admin, Manager, Inspector, Staff
