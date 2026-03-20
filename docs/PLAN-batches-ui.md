# Batches View Mobile UI Optimization

## Goal
Optimize the `BatchesView.vue` interface for responsive mobile web views by applying the patterns from the `mobile-webview-design` skill (Header pattern, Search pattern, List pattern, and FAB pattern).

## Findings
Current `BatchesView.vue` issues on mobile:
1. Uses a raw desktop `<table>` which forces horizontal scrolling or compresses columns.
2. The "Tạo lô mới" button takes up valuable header space on mobile.
3. Search and Status filter inputs are generic `max-w-xs` instead of full-width mobile fields.

## Tasks

- [ ] **Task 1**: Refactor Header & Add FAB (Header & Action Pattern)
  - Split header: Desktop keeps "Tạo lô mới" inline. Mobile hides it.
  - Add a **Floating Action Button (FAB)** at `bottom-6 right-6` for "Tạo lô mới" on mobile (`md:hidden`).
  - Add `pb-24 md:pb-0` to the main container to prevent content from hiding under the FAB.
  - Verify: FAB appears on mobile, triggers "Tạo lô mới".

- [ ] **Task 2**: Refactor Search & Filters (Search Pattern)
  - Apply `flex-col md:flex-row` to the filter container.
  - Make Search input and Status `<MobileBottomSheet>` full-width on mobile (`w-full`), keeping `md:w-auto` for desktop.
  - Ensure `min-h-[52px]` and touch-friendly borders/radius (`rounded-[16px] md:rounded-xl`).
  - Add search icon inside the search input.
  - Verify: Filters stack beautifully on mobile with large tap targets.

- [ ] **Task 3**: Convert Table to Mobile Cards (List Pattern)
  - Keep the `<table>` but hide it on mobile (`hidden md:table`).
  - Create a new mobile-only `<div>` list loop for `batches`.
  - **Card Content**:
    - Primary: Tên lô (`batch.name`) + Mã lô (`#id`)
    - Metadata: Số tủ (`plans_count`), Ngày tạo (`created_at`)
    - Footer: Status badge + "Chi tiết" button (`min-h-[48px]`).
  - Add an Empty State block adhering to the Empty State pattern.
  - Verify: Responsive switch between table (desktop) and stacked cards (mobile).

## Socratic Gate (Questions for User)
> *The backend currently doesn't seem to support pagination for Batches (it returns an unpaginated array). Do you want me to also implement Backend + Frontend Pagination for Batches in this task, or strictly stick to UI refactoring?*

## Done When
- [ ] List views are fully responsive (Cards on mobile, Table on desktop)
- [ ] FAB is used for primary creation action on mobile without overlapping content
- [ ] Touch targets are at least 48px
- [ ] Visual hierarchy meets `mobile-webview-design` standards
