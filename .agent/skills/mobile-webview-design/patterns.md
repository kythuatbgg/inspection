# Mobile Webview Patterns

Use these patterns as reusable guides when redesigning responsive admin and CRUD screens for phones.

## 1. Split Header Pattern

### Use when
- desktop header contains more than 3 actions
- view toggle + import/export + add button are all present

### Desktop
- keep full toolbar
- allow denser action grouping

### Mobile
- first row: title + compact toggle
- second row: 1-2 secondary actions
- primary create action: move to FAB if header feels crowded

### Why
This keeps hierarchy clear and prevents a single overloaded toolbar.

---

## 2. Toggle Pill Pattern

### Use when
- mobile needs to switch between list/map, grid/list, or similar modes

### Structure
- neutral rounded container
- 2 compact icon buttons or icon+label buttons
- active state uses solid inner pill

### Why
Feels native, compact, and easier to scan than desktop tabs squeezed into a row.

---

## 3. Floating Search Pattern

### Use when
- search is primary on mobile
- filters are limited to 1-2 high-value controls

### Structure
- full-width input
- leading search icon
- large touch height
- soft background and strong focus state

### Why
Search should feel like a primary tool, not a desktop input awkwardly resized.

---

## 4. Table-to-Card Pattern

### Use when
- desktop screen uses tables or dense row layouts

### Structure
Each card should include:
- primary identifier at top
- contextual sublabel below
- grouped detail block in soft neutral container
- dedicated action row at bottom

### Why
Cards let users scan vertically and separate information into digestible chunks.

---

## 5. Bottom Action Row Pattern

### Use when
- each item needs 1-2 quick actions
- actions are important and should not be hidden behind menus

### Structure
- max 2 main actions in one row
- each button gets `flex-1`
- text label included
- destructive action gets red-tinted treatment

### Why
Icon-only actions are harder to scan and easier to mis-tap on mobile.

---

## 6. Mobile FAB Pattern

### Use when
- the primary action is “add/create/new”
- top header already contains secondary actions
- keeping the CTA in the header reduces clarity

### Structure
- fixed bottom-right button
- 56px-ish square/circle target
- strong contrast
- visible shadow

### Watch out
- do not block pagination or bottom nav
- ensure enough bottom spacing in content
- if the page has mobile pagination near the bottom, reserve extra bottom margin so the FAB never sits on top of the `next` / `previous` controls
- when necessary, increase both content `pb-*` and pagination container bottom margin together instead of moving only the FAB

---

## 7. Mobile Pagination Pill Pattern

### Use when
- list has multiple pages
- desktop pagination is too dense for phones

### Structure
- context text above
- rounded pill container
- previous button
- current page / total pages in center
- next button

### Why
This is simpler than a full page number strip and works better one-handed.

---

## 8. Empty State Card Pattern

### Use when
- lists can be empty
- filters may return no results

### Structure
- centered icon or illustration
- one clear empty-state title
- one short helper sentence
- optional next step hint

### Why
Empty states should reassure and guide, not just report absence.

---

## 9. Mobile Map/List Admin Pattern

### Use when
- admin page supports both list and map modes

### Mobile recommendation
- keep toggle near title
- keep map container full width with generous radius
- avoid extra toolbars above the map
- retain search and secondary actions in separate stacked groups

---

## 10. Practical Screen Order

A strong mobile admin screen often follows this order:

1. title + mode toggle
2. secondary actions
3. search/filter card
4. content cards
5. pagination
6. floating primary action

This sequence supports scanning and one-handed usage.

---

## 11. Pagination Text Overflow Pattern

### Use when
- desktop pagination has labels like "Hiển thị" and "/trang" alongside a per-page selector
- these labels wrap or break onto new lines at narrow widths

### Structure
- use `whitespace-nowrap` on label text elements
- give the surrounding container enough `min-w` or use `flex-shrink-0`
- on mobile, consider hiding verbose labels entirely and showing only the selector + page info
- prefer `gap-2` over margin hacks to keep items aligned

### Why
Pagination is a tiny UI region. One broken word wrap makes the whole bar look broken.

---

## 12. Inspection/Detail Modal Pattern

### Use when
- tapping a list item should reveal detailed content (inspection results, item breakdowns, photos)
- desktop uses a centered modal overlay
- mobile needs full-screen takeover for readability

### Structure
- **Desktop**: `fixed inset-0 bg-black/50` backdrop + centered card `max-w-4xl max-h-[90vh]`
- **Mobile**: `fixed inset-0 bg-white` full-screen sheet with sticky header + scrollable body
- header: title + close button (X icon, `active:scale-95`)
- body: `overflow-y-auto overscroll-contain` to prevent background scroll bleed
- optional sticky footer for primary actions (approve/reject)

### Why
Detail views with checklists, scores, and photos need space. Cramming them into a half-screen modal on mobile is unreadable.

---

## 13. Score & Critical Error Badge Pattern

### Use when
- each list item has a numeric score and a count of critical errors
- this data needs to be scannable without opening details

### Structure
- **Desktop table**: inline badges below the pass/fail stats row
  - Score: `bg-primary-50 text-primary-700 px-2 py-0.5 rounded text-xs font-bold`
  - Critical errors (>0): `bg-red-50 text-red-700` with `AlertTriangle` icon
  - Critical errors (0): `bg-gray-50 text-gray-500 border border-gray-200`
- **Mobile card**: horizontal row of two mini-cards below the 3-column stats grid
  - Left card: score on `bg-primary-50`
  - Right card: critical count, conditionally `bg-red-50 border-red-100` or neutral

### Why
Scores and critical errors are decision-driving data. They should be visible at the list level, not buried in a detail modal.

---

## 14. Icon Standardization Pattern

### Use when
- project uses hardcoded inline `<svg>` icons scattered across many components
- icons are inconsistent in size, stroke, and visual weight

### Strategy
1. Choose one icon library (e.g., `lucide-vue-next`, `heroicons`)
2. Write a mapping script that extracts SVG `d` path data → maps to library component names
3. Run automated replacement preserving Vue directives (`v-if`, `v-else`, `@click`, `:class`)
4. Verify build succeeds after replacement
5. Clean up extraction scripts

### Lessons learned
- Automated scripts must preserve Vue template directives on SVG elements (e.g., `v-if="loading"` on a spinner icon)
- `Loader2` icons should auto-receive `animate-spin` class
- Always run a production build (`npm run build`) after bulk replacement — Vue compiler catches structural issues like orphaned `v-else`

---

## 15. Nested Card with Map Link Pattern

### Use when
- each list item represents a physical location (cabinet, pole, site)
- location has coordinates but a full map view is overkill

### Structure
- show area/address as secondary text
- add a `MapPin` icon link to Google Maps: `https://www.google.com/maps?q=${lat},${lng}`
- use `target="_blank"` and `rel="noopener"`
- if no coordinates exist, show a dash `—` instead
- icon should be blue, small (`w-4 h-4`), and tappable with `p-1 -m-1` for expanded touch area

### Why
Inspectors in the field need quick access to navigation. A single tap should open directions — no need for an in-app map for this use case.

---

## 16. "Pro Max" Batch Stats Grid Pattern

### Use when
- a batch/project detail page needs to show progress + 4 secondary metrics
- mobile has limited width for 5 equal columns

### Structure
- **Desktop**: `grid-cols-5` with all metrics equally sized
- **Mobile**: split into 2 visual tiers
  - Tier 1: full-width primary metric card (e.g., "3/4 tủ đã kiểm") with a large circular progress indicator
  - Tier 2: `grid-cols-2` compact cards for pass/fail/reviewed/pending counts
- Primary metric card uses `flex items-center justify-between` on mobile, `flex-col text-center` on desktop
- Numbers should be `text-2xl font-black` on mobile, `text-3xl` on desktop

### Why
Five equal cards on mobile become tiny and unreadable. The 1+4 layout creates visual hierarchy and breathability.

---

## 17. API Response Envelope Awareness Pattern

### Use when
- backend wraps all responses in `{ message, data: { ... } }` format
- frontend services need to unwrap correctly

### Rules
- auth store and API services must handle `response.data.data || response.data` pattern
- test login flow end-to-end after any API response format change
- when backend uses a wrapper like `ApiResponse::success()`, the actual payload is at `response.data.data`, not `response.data`

### Why
This mismatch is the #1 cause of "login succeeds but redirect fails" bugs — token is `undefined` because it was read from the wrong nesting level.

---

## 18. Tunnel & Proxy Safe API Configuration

### Use when
- dev environment uses Cloudflare Tunnel, ngrok, or similar
- `localhost` API URLs break when accessed from external domains

### Structure
- In dev mode: force `API_URL = '/api'` and use Vite proxy to forward to backend
- In production: auto-detect if `window.location.hostname !== 'localhost'` and rewrite
- Vite config proxy: `'/api': { target: 'http://localhost:8000', changeOrigin: true }`
- Dev server must bind to `0.0.0.0` (`--host`) for LAN/tunnel access

### Why
Hardcoded `localhost` API URLs cause "connection refused" errors through tunnels. The proxy pattern makes the app tunnel/LAN-safe by default.

---

## 19. Review Action Hierarchy Pattern

### Use when
- admin needs to approve/reject items in a list (inspection results, submissions, etc.)
- both desktop and mobile need review capabilities

### Structure
- **Desktop table**: inline "Duyệt" / "Từ chối" buttons in the last column, hidden when already reviewed
- **Mobile card**: full-width action row at card bottom with `flex gap-3`
  - Approve button: `bg-green-600 text-white flex-1 min-h-[48px] rounded-[14px]`
  - Reject button: `bg-white border border-red-200 text-red-600 flex-1`
- After review: show status badge + optional "Undo" icon button
- Rejection should open a note modal (not inline input)

### Why
Review actions are high-stakes. They need clear visual weight, adequate touch targets, and confirmation steps (especially for rejection with notes).
