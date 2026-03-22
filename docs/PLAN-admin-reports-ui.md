# Project Plan: Admin Reports UI Code Review

## 1. Goal
Review the newly refactored Admin Reports UI code, focusing on component splits (`AdminReportsTab`, `AdminStatisticsTab`, `AdminExportTab`), composables extraction, and ensuring the interface follows correct styling standards, responsiveness, and consistent component structures without external UI frameworks like Nuxt UI.

## 2. Target Files to Review
### Views & Components
- `frontend/src/views/admin/ReportsView.vue`
- `frontend/src/components/admin/reports/AdminReportsTab.vue`
- `frontend/src/components/admin/reports/AdminStatisticsTab.vue`
- `frontend/src/components/admin/reports/AdminExportTab.vue`

### Composables
- `frontend/src/composables/useAdminReportsSearch.js`
- `frontend/src/composables/useAdminReportsStats.js`
- `frontend/src/composables/useAdminReportsExport.js`
- `frontend/src/composables/useDownloadState.js`

## 3. Verification & Review Criteria
1. **Architecture & State Management**: Check if the logic is cleanly decoupled and state is properly synced between parent and child components (props/emits).
2. **Styling & CSS Conventions**: Ensure the implementation uses consistent styling methodologies (custom CSS `.btn`, `.card`, etc., mixed sensibly with utility classes).
3. **Mobile Responsiveness**: Verify the layout maintains usability on mobile screens (stacked interfaces, proper padding, legible text).
4. **Performance**: Check for efficient fetching, avoiding memory leaks on tab switches (e.g., proper use of `onUnmounted`).

## 4. Work Breakdown (Phase Implementation)
- **Phase 1: Deep Code Scan**: Read the contents of all newly created components and composables.
- **Phase 2: UI & Formatting Audit**: Scan templates for inconsistent class names or structural flaws. Ensure mobile UI looks clean.
- **Phase 3: Recommendations & Feedback**: Generate a structured code review report for the user, detailing what's good, what needs fixing, and code snippets for improvements.

## 5. Socratic Questions (Pending User Input)
Before I proceed with the full code analysis and suggest modifications:
1. **Action Mode**: Nếu phát hiện code có thể tối ưu hơn (ví dụ: memory leak, cấu trúc props/emit, tái sử dụng style), bạn muốn mình **tự động fix code luôn** hay chỉ **viết báo cáo (report)** để bạn tự sửa?
2. **Focus Area**: Hệ thống Reports hiện tại được chia thành Search, Statistics, và Export. Có phần logic hay giao diện cụ thể nào bạn đang cảm thấy chưa ổn và muốn mình soi kỹ hơn không?
3. **Style guidelines**: Hiện tại đang dùng CSS class tay (`.btn`, `.card`...). Bạn có muốn kiểm tra và đồng bộ lại các class này thành các utility (Taiwind) hoặc chuyển hẳn các style cục bộ trong scope style thành class chung không?
