---
name: fbb-design-system
description: FBB Inspection app design system reference. Use when building or modifying any UI component to ensure visual consistency. Hand-crafted Swiss Minimalism on Tailwind CSS.
---

# FBB Inspection — Design System

> **Philosophy:** Clean, data-first, professional. Swiss Minimalism principles applied to an enterprise inspection tool.
> **Rule:** This is a **hand-crafted design system**. No third-party admin templates (no Sing App, no Vuetify, no Nuxt UI components). All UI is custom-built.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Vue 3 (Composition API, `<script setup>`) |
| Build | Vite |
| CSS | **Tailwind CSS v3** (utility-first) |
| Icons | **Lucide Icons** (`lucide-vue-next`) — no emoji, no inline SVG |
| Font | **Inter** (Google Fonts) — `font-sans` and `font-heading` |
| State | Pinia |
| i18n | vue-i18n (Vietnamese + English) |
| Routing | Vue Router |

---

## Color Palette

Defined in `tailwind.config.js`:

```
Primary:   Blue scale (#EFF6FF → #1E3A8A), anchor: #2563EB (600)
Success:   #16A34A (green)
Danger:    #DC2626 (red)
Warning:   #D97706 (amber)
Accent:    #F97316 (orange)
Neutrals:  Tailwind Slate scale (bg-slate-50 base, text-slate-900 headings)
```

### Usage Rules
- **Primary actions:** `bg-primary-600`, hover `bg-primary-700`
- **Active nav items:** `bg-primary-50 text-primary-700`
- **Page background:** `bg-slate-50`
- **Cards:** `bg-white` with `border border-slate-200` and `shadow-sm`
- **Text hierarchy:** `text-slate-900` (headings) → `text-slate-600` (body) → `text-slate-400` (muted/placeholder)
- **Semantic badges:** `badge-pass` (green bg), `badge-fail` (red bg), `badge-pending` (slate bg)

---

## Typography

```
Font Family:  Inter, system-ui, sans-serif
Headings:     font-heading (same Inter, but used as semantic marker)
Monospace:    'SF Mono', 'Monaco', 'Consolas', monospace — for IDs, codes, BTS sites
```

### Scale (commonly used)
- Page title: `text-xl` or `22px`, `font-bold` (`700`)
- Section title: `text-sm` or `14px`, `font-semibold` (`600`)
- Body: `text-sm` (`13-14px`), `font-medium` (`500`)
- Labels: `text-xs` (`11px`), `font-semibold`, `uppercase`, `letter-spacing: 0.5px`, `text-slate-500`
- KPI numbers: `text-[22px]`, `font-extrabold` (`800`)

---

## Layout Architecture

```
┌─────────────────────────────────────────────┐
│ Sidebar (w-64, fixed left, white, border-r) │ Content Area (flex-1, min-w-0)
│  ┌─ Logo (ShieldCheck + "FBB Inspection")   │  ┌─ Top Header (h-16, white, shadow-sm)
│  ├─ Nav Items (router-links, rounded-lg)    │  │   Mobile hamburger | Page Title | Status
│  └─ User Info + Lang Switch (bottom)        │  ├─ <main> (p-4 lg:p-8)
│                                             │  │   └─ <router-view />
│  Mobile: slide-in with overlay              │  └─
└─────────────────────────────────────────────┘
```

- **Desktop:** Sidebar always visible (`lg:translate-x-0`)
- **Mobile:** Sidebar hidden, toggled via hamburger, with `bg-slate-900/50` overlay
- **Content area:** Has `min-w-0` to prevent flex blowout

---

## Global Component Classes

Defined in `src/style.css` via `@layer components`:

| Class | Purpose |
|-------|---------|
| `.input-field` | Standard text input — rounded-lg, border, focus ring |
| `.btn-primary` | Primary button — blue bg, white text, shadow-sm |
| `.btn-secondary` | Secondary button — white bg, border, slate text |
| `.card` | Content card — white, rounded-lg, shadow-sm, border, p-6 |

---

## Custom Mobile Components (`components/common/`)

### MobileBottomSheet.vue
- **Props:** `v-model`, `options[]`, `label`, `placeholder`, `containerClass`, `triggerClass`
- **Desktop:** Custom floating dropdown (z-100, position from getBoundingClientRect)
- **Mobile:** Slide-up bottom sheet with drag handle, rounded-t-[28px], backdrop
- **Use instead of:** All native `<select>` elements

### MobileDatePicker.vue
- **Props:** `v-model` (YYYY-MM-DD), `minDate`, `label`, `placeholder`, `triggerClass`, `error`
- **Desktop:** Floating calendar popover
- **Mobile:** Full bottom sheet with large calendar grid + "Today" button
- **Use instead of:** All native `<input type="date">` elements

### MobileImageUploader.vue
- Touch-friendly image upload with preview

---

## Mobile Design Rules

Based on `mobile-webview-design` skill:

1. **Touch targets:** min-height `48px`, preferred `52px`
2. **Tables → Cards:** Use `.d-md-table` (desktop) + `.d-md-none` (mobile card list)
3. **Mobile card structure:**
   ```
   .mobile-card
     .mc-header (title + badge)
     .mc-body
       .mc-row (label + value pairs)
     .mc-actions (buttons)
   ```
4. **Filters:** Stack vertically on mobile (`flex-direction: column`)
5. **Buttons:** Full-width on mobile, min-height 48px
6. **Search input:** `min-h-[52px]`, `rounded-[16px]`, soft bg on mobile
7. **KPI Grid:** 4-col desktop → 2x2 compact grid on mobile (Pro Max pattern)
8. **Tabs:** Horizontal scroll, hidden scrollbar, `white-space: nowrap`

---

## Breakpoint Strategy

| Breakpoint | Behavior |
|-----------|----------|
| `< 768px` | Mobile: cards, stacked filters, bottom sheets, hamburger nav |
| `≥ 768px` (md) | Tablet: partial desktop |
| `≥ 1024px` (lg) | Desktop: sidebar visible, full tables, inline filters |

Key CSS classes:
```css
.d-md-none   { display: none; }      /* hidden on desktop, visible on mobile */
.d-md-table  { display: table; }     /* visible on desktop, hidden on mobile */

@media (max-width: 768px) {
  .d-md-none  { display: block !important; }
  .d-md-table { display: none !important; }
}
```

---

## Badge System

```css
.badge          { padding: 2px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; }
.badge-pass     { background: #dcfce7; color: #16a34a; }
.badge-fail     { background: #fee2e2; color: #dc2626; }
.badge-pending  { background: #f1f5f9; color: #94a3b8; }
.badge-category { background: #eff6ff; color: #2563eb; font-weight: 500; }
.badge-warning  { background: #fef3c7; color: #d97706; }
.badge-primary  { background: #dbeafe; color: #1d4ed8; }
```

---

## Anti-Patterns (NEVER DO)

- ❌ Use any third-party admin template or component library
- ❌ Use native `<select>` or `<input type="date">` — always use MobileBottomSheet / MobileDatePicker
- ❌ Use emoji as icons — always use Lucide
- ❌ Use inline `<svg>` — always import from `lucide-vue-next`
- ❌ Use purple/violet colors anywhere
- ❌ Use `font-size` smaller than `11px`
- ❌ Leave hover-only affordances on mobile
- ❌ Skip `min-w-0` on flex children wrapping full-width content

---

## Checklist Before Delivering UI

- [ ] Uses Tailwind utility classes (no custom CSS when Tailwind suffices)
- [ ] Colors from `tailwind.config.js` palette only
- [ ] Icons from `lucide-vue-next` only
- [ ] No native selects or date inputs
- [ ] Mobile touch targets ≥ 48px
- [ ] Tables have mobile card alternatives
- [ ] Filter rows stack on mobile
- [ ] Buttons full-width on mobile
- [ ] No horizontal scroll on 375px viewport
- [ ] i18n keys used (no hardcoded Vietnamese/English strings)
