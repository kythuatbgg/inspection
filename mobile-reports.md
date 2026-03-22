# Reports Mobile Optimization

## Goal
Optimize the Reports view (both reports tab and statistics tab) for mobile devices by replacing tables with stacked cards, improving the search/filter layout, and applying touch-friendly patterns.

## Tasks
- [ ] Task 1: Refactor the header & filters for mobile (stack the batch select, search input, and lang select in a mobile-friendly way). → Verify: On `< 768px`, inputs stack vertically and are full width.
- [ ] Task 2: Create a mobile card layout for the Reports tab (replace table with `mobile-list` containing `mobile-card` items). → Verify: Each inspection is a card showing cabinet, BTS, score, result, and export actions.
- [ ] Task 3: Refactor the Statistics tab layout for mobile (stack chart/summary cards and data tables). → Verify: Summary cards display beautifully stacked on mobile.
- [ ] Task 4: Create a mobile card layout for the Statistics tab tables (Overview/By BTS/By Inspector/By Error). → Verify: Tables are replaced/augmented with `d-md-none` cards on mobile screens.
- [ ] Task 5: Apply consistent CSS utility classes from `mobile-webview-design` (`app-container`, `content-area`, `mobile-action-bar` etc) and touch-friendly paddings. → Verify: Pages render correctly without overflow on `375px` width.

## Done When
- [ ] The Reports view is fully usable on mobile without horizontal scrolling.
- [ ] Both "Biên bản" and "Thống kê" tabs render well on small screens.
- [ ] `ux_audit.py` passes for mobile CSS.
