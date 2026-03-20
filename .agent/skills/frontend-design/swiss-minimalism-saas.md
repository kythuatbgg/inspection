# Archetype: Swiss Minimalism SaaS

> **A highly functional, data-dense, maximum-clarity design system optimized for B2B SaaS, Admin Dashboards, and Enterprise Tools.**

## 1. Core Philosophy
- **Function over Form:** Every pixel must serve the data. No decorative elements.
- **High Signal-to-Noise Ratio:** Remove gradients, heavy shadows, and organic shapes.
- **Grid-obsessed:** Crisp alignment, sharp geometric boundaries.

## 2. Design Tokens

### Typography: "The Swiss Type"
- **Font Family:** `Inter` (or `Fira Sans` / `Roboto`) globally.
- **Hierarchy:** Strong contrast in font weights rather than just sizes.
  - Headings: `font-bold tracking-tight text-slate-900`
  - Body: `font-normal text-slate-800`
  - Meta/Muted: `font-medium text-slate-500 text-[11px] uppercase tracking-wide`

### Color Palette: "SaaS Trust"
- **Background:** `bg-slate-50` (A cool, professional off-white, never pure white for large areas).
- **Surface:** `bg-white` (For cards, modals, sidebars).
- **Primary:** `blue-600` (`#2563EB`) - The universal standard for B2B trust and action.
- **Text:** `slate-900` (Headlines), `slate-800` (Body), `slate-500` (Muted).
- **Borders:** `slate-200` (Subtle definition).
- **Status:** Standard semantic colors (`success` green, `danger` red) but muted backgrounds (e.g., `bg-success/10 text-success`).

### Shape & Geometry: "Crisp & Sharp"
- **Border Radius:** Maximum `rounded-lg` (8px). Never use `rounded-xl` or `rounded-2xl` or full pills (except for micro-badges).
- **Borders:** Consistent `border border-slate-200` on surfaces instead of relying heavily on shadows.
- **Shadows:** Extremely minimal. Use `shadow-sm` purely to detach surfaces from the `slate-50` background.

## 3. UI Patterns

### Layouts
- **Sidebar:** Clean white background, right border only `border-r border-slate-200`. No drop shadow on the sidebar.
- **Header:** White background, thin bottom border. Clean, left-aligned typography.

### Data Tables / Lists
- Zero vertical borders.
- Thin horizontal dividers (`border-slate-100` or `slate-200`).
- Hover states on rows: `hover:bg-slate-50` (very subtle).

### Dashboard KPI / Stats Cards
- **Grid Layout**: Always use a crisp multi-column grid (`grid-cols-2 lg:grid-cols-4 gap-4`).
- **Card Structure**: Standalone `bg-white border border-slate-200 rounded-lg p-4 shadow-sm` or `.card`.
- **Interaction**: Subtle depth on hover (`hover:shadow-md transition-shadow`).
- **Internal Content**: Left-align with Icon container (`w-10 h-10 rounded-lg bg-{color}-100 flex items-center shrink-0`), bold metric right (`text-2xl font-bold text-slate-900 tracking-tight font-heading`), and muted label (`text-sm text-slate-500 font-medium`).
- **Consistency**: Keep the layout decoupled across sections (Admin and Inspector should use the same card structures rather than completely custom UI constructs like complex divided panels).

### Forms & Inputs
- **Inputs:** `bg-white border-slate-300 rounded-lg`. Focus state: `ring-2 ring-primary-500/20 border-primary-500`.
- **Primary Buttons:** Solid `bg-primary-600`, `rounded-lg`, `font-medium`, `shadow-sm`.
- **Secondary Buttons:** Outline or ghost: `bg-white border-slate-300 text-slate-700`.

## 4. When to Use
- Admin Panels
- Complex Data Dashboards
- B2B Enterprise Software
- Tools requiring long user sessions (low eye strain)

## 5. Anti-Patterns (Do NOT do this here)
- ❌ **No Glassmorphism** (backdrop-blur is allowed only strictly for sticky headers/modals if needed, but solid is preferred).
- ❌ **No Mesh Gradients** or glowing orbs.
- ❌ **No Heavy Drop Shadows** (`shadow-lg` / `shadow-xl`).
- ❌ **No Extra Rounded Corners** (`rounded-2xl` looks too playful/B2C).
- ❌ **No emojis** as structural icons. Use geometric SVGs (Lucide/Heroicons) with consistent stroke widths (`stroke-2`).
