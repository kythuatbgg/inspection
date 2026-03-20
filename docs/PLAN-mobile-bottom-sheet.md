# Bottom Sheet for Mobile Selects

## Goal
Replace native `<select>` dropdowns with a touch-friendly Bottom Sheet on mobile views. Native selects feel cramped and inconsistent on phones — a bottom sheet gives larger tap targets, better visual hierarchy, and a premium feel.

## Affected Selects

| View | Element | v-model | Options |
|------|---------|---------|---------|
| `UsersView.vue` L103 | Role filter | `filters.role` | Tất cả, Admin, Quản lý, Inspector, Staff |
| `UsersView.vue` L384 | Form role (modal) | `form.role` | Admin, Quản lý, Inspector, Staff |
| `CabinetsView.vue` L212 | Per-page (desktop only) | `pagination.per_page` | 10, 20, 50, 100 |
| `BatchesView.vue` L24 | Status filter | `filters.status` | Tất cả, Đang chờ, Đang kiểm tra, Hoàn thành |

> [!NOTE]
> `CabinetsView` per-page select is `hidden md:flex` (desktop only) — skip for now.

## Tasks

- [ ] **Task 1**: Create `MobileBottomSheet.vue` component in `src/components/common/`
  - Props: `modelValue`, `options[]`, `label`, `placeholder`
  - Emits: `update:modelValue`
  - Mobile: renders a trigger button + slide-up overlay with radio-style option list
  - Desktop: renders a normal `<select>` (unchanged behavior)
  - Verify: component renders without errors

- [ ] **Task 2**: Replace `<select>` in `UsersView.vue` role filter (L103) with `<MobileBottomSheet>`
  - Verify: tap filter on mobile → bottom sheet slides up → select option → list filters

- [ ] **Task 3**: Replace `<select>` in `UsersView.vue` form role (L384) with `<MobileBottomSheet>`
  - Verify: open Add/Edit modal on mobile → role field shows bottom sheet

- [ ] **Task 4**: Replace `<select>` in `BatchesView.vue` status filter (L24) with `<MobileBottomSheet>`
  - Verify: tap filter on mobile → bottom sheet slides up → select option → list filters

## Component Design

```
┌─────────────────────────────────┐
│  Trigger Button (looks like     │  ← Replaces <select> on mobile
│  current select but tappable)   │
└─────────────────────────────────┘

         ↓ On tap ↓

┌─────────────────────────────────┐
│ ████████ overlay ████████████████│
│                                 │
│ ┌─────────────────────────────┐ │
│ │  ── drag handle ──          │ │  ← Bottom Sheet
│ │  Label                      │ │
│ │  ┌───────────────────────┐  │ │
│ │  │ ○ Option 1            │  │ │
│ │  │ ● Option 2 (selected) │  │ │  ← Radio-style options
│ │  │ ○ Option 3            │  │ │
│ │  └───────────────────────┘  │ │
│ └─────────────────────────────┘ │
└─────────────────────────────────┘
```

**Key specs:**
- `min-h-[56px]` tap targets for each option
- Selected option has checkmark icon + primary color
- Backdrop overlay with `bg-black/50`
- Slide-up animation via CSS `transform: translateY()`
- Close on backdrop tap or option select

## Done When
- [ ] All 3 mobile selects use bottom sheet on phones
- [ ] Desktop behavior unchanged (normal select)
- [ ] Tap targets ≥ 48px
- [ ] Smooth slide-up/down animation
