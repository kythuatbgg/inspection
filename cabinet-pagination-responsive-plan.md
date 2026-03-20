# Cabinet Pagination & Responsive UI Plan

## Goal
Thêm phân trang cho cabinets list và responsive UI: Table list cho webview, card list cho mobile view.

## Architecture
- Backend: Laravel pagination với `paginate()`
- Frontend: Responsive layout với Tailwind breakpoints
- Web (>1024px): Table list với pagination controls
- Mobile (<1024px): Card list với pagination như hiện tại

---

## Task 1: Backend - Add Pagination

**Files:**
- Modify: `backend/app/Http/Controllers/Api/CabinetController.php`

**Step 1:** Update `index()` method để hỗ trợ pagination

```php
public function index(Request $request): JsonResponse
{
    $perPage = $request->input('per_page', 20);
    $cabinets = Cabinet::paginate($perPage);

    return response()->json($cabinets);
}
```

**Step 2:** Verify - Run API test
```bash
curl "http://localhost:8000/api/cabinets?per_page=10"
```

Expected: Response có `data`, `total`, `per_page`, `current_page`, `last_page`

---

## Task 2: Frontend - Update Service

**Files:**
- Modify: `frontend/src/services/cabinetService.js`

**Step 1:** Update `getCabinets()` để handle pagination params

```javascript
async getCabinets(params = {}) {
  const response = await api.get('/cabinets', { params })
  return response.data
}
```

---

## Task 3: Frontend - Responsive List View

**Files:**
- Modify: `frontend/src/views/admin/CabinetsView.vue`

**Step 1:** Thêm reactive variables cho pagination

```javascript
const pagination = ref({
  currentPage: 1,
  perPage: 20,
  total: 0,
  lastPage: 1
})
```

**Step 2:** Cập nhật `fetchCabinets()` để pass pagination params

**Step 3:** Thêm responsive layout

```vue
<!-- Web (>1024px): Table -->
<div v-if="isDesktop" class="hidden lg:block">
  <table>...</table>
  <!-- Pagination -->
</div>

<!-- Mobile (<1024px): Cards -->
<div v-else class="lg:hidden">
  <div v-for="cabinet in cabinets" class="card">...</div>
  <!-- Pagination -->
</div>
```

---

## Task 4: UI/UX - Apply /ui-ux-pro-max

**Requirements:**
- Touch targets: min 56px
- Table: alternating rows, hover states
- Cards: consistent spacing, shadow
- Pagination: large buttons (48px min), clear current page
- Loading states: skeleton/spinner

---

## Done When
- [ ] API trả về paginated data
- [ ] Web view hiển thị table với pagination
- [ ] Mobile view hiển thị cards với pagination
- [ ] UI đạt UX standards (touch targets, spacing)
