# Desktop Custom Select Dropdown

## Goal
Replace the native `<select>` on **desktop** with a custom-styled dropdown inside `MobileBottomSheet.vue`, creating a unified premium select component for both desktop and mobile.

## Current State
- `MobileBottomSheet.vue` renders **native `<select>`** on desktop (≥768px) and **bottom sheet** on mobile (<768px)
- `CabinetsView.vue` L212 has a standalone native `<select>` for per-page pagination
- All other selects in the app already use `MobileBottomSheet`

## Tasks

- [ ] Task 1: Upgrade desktop rendering in `MobileBottomSheet.vue`
  - Replace native `<select>` with a custom trigger button + floating dropdown panel
  - Trigger: styled button showing selected label + chevron icon
  - Dropdown: absolute-positioned panel with `rounded-xl`, `shadow-lg`, `border`, smooth fade+scale animation
  - Each option: hover highlight, checkmark for selected item, `min-h-[40px]`
  - Click outside to close (use `@click` on backdrop or `v-click-outside` pattern)
  - Keyboard support: close on `Escape`
  - Verify: all existing uses in UsersView and BatchesView display custom dropdown on desktop

- [ ] Task 2: Replace CabinetsView per-page `<select>` with `MobileBottomSheet`
  - Verify: per-page dropdown shows custom styled options on desktop

## Verification
- Open UsersView on desktop → tap "Tất cả vai trò" → custom dropdown appears with animation
- Open UsersView → Add User modal → role field shows custom dropdown
- Open BatchesView → status filter shows custom dropdown
- Open CabinetsView → pagination per-page shows custom dropdown
- Resize to mobile → all above revert to bottom sheet behavior (unchanged)

## Done When
- [ ] Zero native `<select>` elements remain in the app
- [ ] Desktop dropdown feels premium (animation, shadow, hover states)
- [ ] Mobile bottom sheet behavior is completely unchanged
