# PLAN: PWA Round 1 — TDD

## Goal
PWA foundation + Dexie inspection write + sync push. Mỗi feature được viết test trước.

---

## TDD Setup: Thêm test config cho Dexie + PWA composables

### Task 1: Setup test cho composables và Dexie
Tạo `frontend/tests/composables/useOfflineSync.spec.js` với mock `navigator.onLine` và mock Dexie:
```js
import { describe, it, expect, vi, beforeEach } from 'vitest'
import { mount } from '@vue/test-utils'
// Mock Dexie
vi.mock('@/db/index', () => ({ default: { inspections: { where: vi.fn() } }))
// Mock api
vi.mock('@/services/api', () => ({ default: { post: vi.fn() }))
```

→ Verify: `npm test` chạy không lỗi với test file mới.

---

## TDD Cycle 1: Offline Detection

### Task 2: Viết test — `useOfflineSync.spec.js`
```js
it('isOnline = true when navigator.onLine is true')
it('isOnline = false when navigator.offline event fires')
it('sync() calls pushDrafts when online')
it('sync() skips when offline')
it('draftCount reflects pending inspections from Dexie')
```

### Task 3: Implement — `src/composables/useOfflineSync.js`
```js
export function useOfflineSync() {
  const isOnline = ref(navigator.onLine)
  window.addEventListener('online', () => { isOnline.value = true; sync() })
  window.addEventListener('offline', () => { isOnline.value = false })
  // ...
}
```
→ Verify: `npm test` pass. DevTools → Application → Service Workers thấy SW registered.

---

## TDD Cycle 2: Dexie Inspection Write

### Task 4: Viết test — `src/db/inspectionDraft.spec.js`
```js
import { db } from '@/db/index'
it('saveDraft() writes inspection with sync_status = draft')
it('saveDraft() writes inspectionDetails rows')
it('getDrafts() returns only draft + pending inspections')
it('markAsSynced() updates sync_status to synced')
```

### Task 5: Implement — `src/db/inspectionDraft.js`
```js
export async function saveDraft(inspection) {
  const localId = await db.inspections.add({ ...inspection, sync_status: 'draft' })
  for (const detail of inspection.details ?? []) {
    await db.inspectionDetails.add({ inspection_id: localId, ...detail })
  }
  return localId
}
export async function getDrafts() {
  return db.inspections.where('sync_status').anyOf(['draft', 'pending']).toArray()
}
export async function markAsSynced(localId) {
  await db.inspections.update(localId, { sync_status: 'synced' })
}
```
→ Verify: `npm test` pass. Dexie Studio (DevTools) thấy rows được tạo.

---

## TDD Cycle 3: Sync Push

### Task 6: Viết test — `src/db/syncService.spec.js`
```js
it('pushDrafts() POSTs to /sync with correct payload shape')
it('pushDrafts() maps overall_photos base64 array')
it('pushDrafts() marks drafts as synced on success')
it('pushDrafts() skips when no drafts')
it('pushDrafts() throws on network error')
```

### Task 7: Implement — Mở rộng `src/db/syncService.js`
```js
export async function pushDrafts() {
  const drafts = await getDrafts()
  if (!drafts.length) return { synced: 0 }
  const { data } = await api.post('/sync', { inspections: drafts.map(mapDraft) })
  for (const draft of drafts) {
    await markAsSynced(draft.id)
  }
  return data
}
```
→ Verify: `npm test` pass. Mock intercept POST /sync.

---

## TDD Cycle 4: PWA Plugin

### Task 8: Viết test — `vite.config.spec.js` (hoặc inline config test)
```js
it('vite.config exports VitePWA plugin')
it('manifest has required fields: name, icons, start_url, display')
it('workbox runtimeCaching includes api routes')
```

### Task 9: Cài `vite-plugin-pwa` + cấu hình `vite.config.js`
```bash
npm install -D vite-plugin-pwa
```
→ Verify: `npm run build` tạo `dist/sw.js`. Lighthouse PWA score ≥ 75.

---

## TDD Cycle 5: PWA Manifest + Service Worker Registration

### Task 10: Tạo `public/manifest.json`
```json
{ "name": "FBB Inspection", "short_name": "FBB", "start_url": "/", "display": "standalone", "theme_color": "#2563eb", "icons": [...] }
```
→ Verify: DevTools → Application → Manifest thấy manifest load đúng.

### Task 11: Đăng ký SW trong `main.js`
```js
import { registerSW } from 'virtual:pwa-register'
registerSW({ onOfflineReady() { console.log('App ready to work offline') } })
```
→ Verify: DevTools → Application → Service Workers: SW active.

---

## Done When
- [ ] `npm test` pass cho tất cả test files mới
- [ ] `npm run build` tạo SW + manifest
- [ ] DevTools thấy SW registered + manifest
- [ ] Lighthouse PWA ≥ 75
- [ ] Dexie Studio thấy inspection rows được ghi khi offline

## Notes
- Chạy `npm test` sau mỗi Task để cycle TDD ngắn
- Dùng `vi.mock()` Vitest cho Dexie và Axios
- PWA icons: dùng placeholder SVG → PNG đơn giản nếu chưa có assets
