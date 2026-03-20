# User Search Enhancement

## Goal
Allow searching users by **name, email, and username** (case-insensitive, PostgreSQL compatible).

## Tasks
- [ ] Task 1: Add `email` field to backend search query in `UserController.php` → Verify: `curl /api/users?search=gmail` returns matching users
- [ ] Task 2: Update frontend search placeholder in `UsersView.vue` to mention email → Verify: Placeholder reads "Tìm tên, email hoặc tên đăng nhập..."
- [ ] Task 3: Verify end-to-end search works on browser → Verify: Type partial email, see matching results

## Done When
- [ ] Searching by partial email returns correct users
- [ ] Existing name/username search still works
