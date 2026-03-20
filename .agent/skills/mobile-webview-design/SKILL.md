---
name: mobile-webview-design
description: Reusable design skill for responsive mobile web views in admin panels and web applications. Focuses on touch-first layouts, mobile-only markup patterns, thumb-zone actions, stacked cards, search bars, floating actions, and mobile pagination for modern web UIs.
allowed-tools: Read, Write, Edit, Glob, Grep, Bash
---

# Mobile Webview Design

> **Philosophy:** Mobile web is not desktop shrunk down. Prioritize touch, hierarchy, rhythm, and clarity.
> **Core Principle:** Separate mobile behavior from desktop behavior when shared markup starts hurting usability.

---

## When to Use This Skill

Use this skill when the target is:
- a **responsive web app** viewed on phones
- admin screens, dashboards, CRUD pages, tables, search/filter pages
- list/detail screens that need **mobile-specific UI**, not only smaller CSS
- any screen where desktop patterns become crowded, tiny, or hard to tap on mobile

Do **not** use this as a replacement for native-mobile skills such as `mobile-design`.

---

## Read This First

Before using this skill:
1. Read this `SKILL.md`
2. Read `patterns.md`
3. Read `checklist.md`
4. If the task is broader visual redesign, also read `.agent/skills/frontend-design/SKILL.md`

---

## Scope Definition

This skill is for **mobile webview optimization**, especially inside existing web codebases.

### Primary goals
- Increase tap comfort
- Reduce visual crowding
- Improve scanability
- Separate mobile action hierarchy from desktop toolbar logic
- Avoid hidden or overlapping controls
- Preserve desktop quality while giving mobile its own interaction model

### Typical affected areas
- page headers
- segmented list/map toggles
- search and filter bars
- table-to-card conversions
- pagination
- sticky or floating primary actions
- empty states
- import/export/admin actions

---

## Core Principles

### 1. Mobile deserves its own structure
If desktop markup becomes cramped on mobile, split it.

**Examples:**
- desktop toolbar → mobile stacked action rows
- desktop table → mobile cards
- desktop pagination → mobile-only pagination pill
- desktop inline CTA row → mobile FAB or thumb-zone action

### 2. Touch targets come first
Every mobile interaction should feel easy on one hand.

**Minimum rules:**
- target size: `44px` absolute minimum, `48px+` preferred
- spacing between actions: `8px` minimum
- primary actions should not be tiny icon-only controls unless visually obvious

### 3. Reduce horizontal complexity
Mobile users scan vertically.

Prefer:
- stacked cards
- single-column groups
- grouped metadata blocks
- short labels + clear values

Avoid:
- compressed grids
- too many side-by-side buttons
- desktop table columns squeezed into mobile

### 4. Thumb-zone actions beat top-heavy actions
The most important mobile action should be easy to reach.

Use:
- bottom-positioned CTA when appropriate
- FAB for create/add flows
- mobile pagination near the bottom of content
- action buttons grouped near natural thumb reach

When FABs and bottom pagination coexist, reserve enough vertical space so the floating action never overlaps `next` / `previous` controls.

### 5. Prevent Flex Blowout
When using `w-full` components inside flex layouts (like a main layout wrapper), always ensure the flex child has `min-w-0`. Without it, flex items cannot shrink below their intrinsic content size, causing 100vw components to push the entire page horizontally off-screen.

### 6. Mobile should feel intentional, not simply smaller
Good mobile UI is not reduced desktop UI.

It should have:
- stronger hierarchy
- cleaner spacing
- fewer simultaneous decisions
- clearer action grouping

---

## Mobile Patterns to Prefer

### Header pattern
Split desktop and mobile headers when needed.

**Desktop may contain:**
- title
- view mode toggle
- import/export buttons
- primary add button

**Mobile should prefer:**
- concise title
- compact toggle pill
- separate row for secondary actions
- primary add action moved to FAB if that reduces clutter

### Search pattern
Use a full-width mobile search bar with:
- internal leading icon
- `min-h-[52px]` or larger
- soft container background
- large radius for a modern touch-first look

### List pattern
Convert desktop table rows to stacked mobile cards.

Each card should contain:
- strong primary identifier
- secondary context line
- grouped metadata block
- clear action row at the bottom

### "Pro Max" Stats Grid pattern
When displaying 4-5 key statistics (e.g. Dashboard metrics), do **not** use tall `grid-cols-2` that take up 3 rows, and do **not** force users to scroll horizontally.
Instead, use a "Pro Max" layered design to fit everything on one screen:
- 1 prominent full-width card on top for the Primary/Total metric.
- A compact `2x2` grid (`grid-cols-2 gap-3` with smaller padding) below it for the secondary metrics.

### Action pattern
For mobile action buttons:
- prefer 2 main actions per row max
- use full-width or `flex-1` buttons
- keep icon + label together
- use destructive styling only for destructive action

### Pagination pattern
Create mobile-only pagination instead of shrinking desktop pagination.

Preferred mobile model:
- previous / current-page / next
- large pill container
- large circular buttons
- current page shown clearly
- optional context text above

### Empty state pattern
Do not leave a single weak text line.

Prefer:
- icon or illustration
- clear title
- short helper explanation
- optional next step suggestion

### Universal Custom Select pattern
Native `<select>` dropdowns are hostile on mobile (tiny text, no hierarchy) and inconsistent across desktop browsers.

**Replace all native `<select>` elements with a unified component that:**
- On **desktop** (≥768px): renders a custom floating dropdown panel with animation, hover states, click-outside dismissal, and Escape key support
- On **mobile** (<768px): renders a trigger button → tap → slide-up overlay with radio-style option list

**Component design specs:**
- Trigger: full-width button styled like the original select, `min-h-[52px]`
- Sheet: `Teleport` to `<body>`, `rounded-t-[28px]`, drag handle bar, title, scrollable options
- Each option: `min-h-[56px]`, primary-color checkmark for selected item
- Animation: CSS `translateY(100%)` slide-up with `cubic-bezier(0.32, 0.72, 0, 1)`
- Backdrop: `bg-black/50`, tap to dismiss
- Lock body scroll while open (`document.body.style.overflow = 'hidden'`)

**Implementation approach:**
- Create a single reusable `MobileBottomSheet.vue` component in `components/common/`
- Accept `v-model`, `options[]` (supports `{value, label}` objects or plain strings), `label`, `placeholder`
- Use `window.innerWidth < 768` check with resize listener for mobile detection
- Integrate into filter selects and form selects across all admin views

### Universal Custom Date Picker pattern
Browser-native `<input type="date">` wheels and calendars differ wildly across iOS/Android and desktop browsers, often looking out of place and offering poor UX.

**Replace all `<input type="date">` elements with a unified component that:**
- On **desktop** (≥768px): renders a floating calendar popover (fixed positioning `z-[100]` to avoid clipping, relative to trigger `getBoundingClientRect()`).
- On **mobile** (<768px): renders a full-screen, bottom-anchored sheet with a drag handle, title, action buttons, and large touch-friendly calendar cells.

**Component design specs:**
- Trigger: full-width button with a calendar icon, matching the style of text inputs, `min-h-[52px]`.
- Calendar grid: 7 columns starting with Sunday (CN). Touch targets for days must be large (e.g. `aspect-square w-full max-w-[40px]`).
- Visual states: Disabled dates (opacity 40%), Selected date (High contrast primary background), Today (Subtle primary background with border).
- Mobile Actions: Include a "Hôm nay" (Today) quick-select button at the bottom of the sheet, and a "Xong" (Done) button in the header.
- State Syncing: Month navigation should not mutate the selected date until a day is explicitly tapped.

**Implementation approach:**
- Create a single reusable `MobileDatePicker.vue` component in `components/common/`.
- Handle native Date object iteration to generate padding days for previous/next months to maintain a stable 6-row or exact-fit grid.
- Integrate into date filters, start/end date range selectors, and form inputs.

### FAB + Pagination coexistence pattern
When a FAB (`fixed bottom-6 right-6`) and mobile pagination both exist on the same page, the FAB will overlap the pagination's Next button when the user scrolls to the bottom.

**Mandatory safeguards:**
- Root container: add `pb-24 md:pb-0` to create bottom clearance on mobile
- Mobile pagination block: use `mb-24` (not `mb-6`) to push it above the FAB zone
- Always test by scrolling to the very bottom of a paginated list on a narrow viewport

---

## Anti-Patterns

Never do these when optimizing mobile web views:

- Reusing dense desktop toolbars unchanged on mobile
- Shrinking 5-8 controls into a single row
- Keeping desktop pagination numbers when they become cramped
- Using tiny icon buttons for important actions
- Leaving fixed bars that cover content without enough spacing
- Forcing mobile users to interpret too many columns at once
- Hiding the primary action in a crowded header
- Using hover-only affordances as meaning carriers
- Treating `sm:` and `md:` tweaks as enough when structure is the real problem
- **Flex blowout negligence**: Wrapping mobile views in `flex-1` layout containers without `min-w-0`, causing horizontal scroll bleeding.
- **Native selects/datepickers anywhere**: Keeping browser-default `<select>` or `<input type="date">` anywhere in the app. Always use robust custom components instead.
- **FAB overlapping pagination**: Placing a fixed FAB at `bottom-6 right-6` without adding `pb-24`/`mb-24` to the content and pagination blocks below it.
- **Pagination label wrapping**: Leaving verbose labels like "Hiển thị" and "/trang" without `whitespace-nowrap`, causing desktop per-page selectors to break layout.
- **Inline SVG icons**: Keeping hardcoded `<svg>` elements instead of using a consistent icon library. This leads to inconsistent sizing, strokes, and makes bulk updates impossible.
- **Scores buried in detail views**: Hiding total scores and critical error counts inside modals when they are the primary decision-making data. Show them at the list level.
- **Color-only status indicators**: Using only color (red/green) to indicate pass/fail without text labels. This fails accessibility and is ambiguous in some lighting.
- **Login redirect mismatch**: Not testing the full login→redirect→dashboard flow after API response format changes. The `response.data.data` envelope is the #1 silent breaker.
- **Localhost API in tunnel environments**: Using hardcoded `localhost` API URLs that fail when accessed through Cloudflare Tunnel or ngrok. Always proxy through Vite dev server.

---

## Execution Framework

For every mobile webview task, go through this order:

1. **Audit the desktop assumptions**
   - Which parts are desktop-first?
   - Which controls become cramped?

2. **Separate structure where needed**
   - Use mobile-only and desktop-only blocks when necessary

3. **Rebuild hierarchy for mobile**
   - Title → actions → search → content → pagination → FAB

4. **Re-check touch and spacing**
   - All critical controls finger-friendly

5. **Verify document flow and constraints**
   - No floating element blocks content
   - No hidden bottom content under fixed controls
   - Fix all "flex blowouts" by ensuring `min-w-0` on flex children that wrap main content

6. **Test the narrow viewport mentally and practically**
   - 375px width (and down to 320px)
   - one-handed interaction
   - fast scanning

---

## Recommended Class Heuristics

These are heuristics, not hard laws:

- tap targets: `min-h-[48px]` to `min-h-[56px]`
- modern mobile radius: `rounded-[14px]` to `rounded-[24px]`
- content card spacing: `p-4` to `p-5`
- grouped metadata block: subtle neutral background such as `bg-gray-50/80`
- floating CTA: bottom-right, shadowed, high-contrast, non-intrusive
- when mobile pagination exists near the bottom, pair floating CTA usage with extra content bottom padding such as `pb-24` or `pb-28` and give the pagination block its own bottom margin

---

## Quick Decision Guide

### Keep shared markup if:
- only spacing changes
- only font size changes
- controls remain readable and easy to tap

### Split mobile/desktop markup if:
- header has too many actions
- desktop table is not readable on mobile
- pagination becomes crowded
- search/filter region becomes multi-row noise
- primary action loses prominence
- `<select>` elements have more than 3 options (use bottom sheet instead)

---

## Done Criteria

A mobile webview optimization is only done when:
- mobile actions are easy to tap
- visual hierarchy is cleaner than desktop, not just smaller
- no major control overlaps content
- list content is scannable vertically
- primary action is obvious
- pagination is usable one-handed
- empty state is intentional
- zero native `<select>` or `<input type="date">` elements remain; all use custom unified components
- FAB does not overlap any interactive element when scrolled to bottom
- all icons use a single library — no inline `<svg>` remnants
- score/critical error data visible at list level, not hidden in modals
- all API services handle response envelope unwrapping consistently
- API configuration is tunnel/proxy-safe for development
- modals use full-screen on mobile, centered overlay on desktop
- review/approval actions have adequate touch targets and confirmation steps

---

## Related Skills

- `frontend-design` → broader visual and UX direction
- `mobile-design` → native mobile apps, not responsive web screens
- `web-design-guidelines` → post-implementation audit

---

> **Remember:** The best mobile web screens feel edited, not compressed. If a desktop layout feels crowded on a phone, redesign the structure instead of defending it.
