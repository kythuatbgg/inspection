# PLAN: PWA Implementation — FBB Inspection App

## Context

Dự án FBB Inspection đã có nền tảng tốt nhưng **PWA chưa được triển khai**. Spec yêu cầu Offline-first với Service Worker, Dexie.js, và Watermark Camera. Một số module đã có sẵn nhưng chưa được kết nối.

---

## Hiện trạng khảo sát

### ✅ Đã có sẵn
| Module | File | Ghi chú |
|--------|------|---------|
| Dexie.js schema | `src/db/index.js` | 7 tables: cabinets, checklists, batches, planDetails, inspections... |
| `syncService` helpers | `src/db/index.js` | `syncCabinets()`, `getPendingInspections()`, `markAsSynced()` — **chưa gọi POST sync** |
| Backend `/sync` endpoint | `backend/app/Http/Controllers/SyncController.php` | ✅ hoàn chỉnh, nhận `image_base64`, idempotent |
| Draft auto-save | `InspectionView.vue` | localStorage — cần chuyển sang Dexie |
| Camera + compression | `MobileImageUploader.vue` | `browser-image-compression` + SHA-256 dedup |
| Pinia stores | `src/stores/` | auth, batches, inspections, checklists |

### ❌ Chưa có
| Module | Ảnh hưởng |
|--------|-----------|
| `vite-plugin-pwa` | Không có Service Worker, không cài được PWA |
| `public/` folder + manifest | Không có Install prompt, không hiện "Add to Home Screen" |
| Offline inspection write to Dexie | Inspection thất bại khi offline → mất dữ liệu |
| Offline photo storage (base64) | Ảnh không lưu được khi offline |
| POST `/sync` từ frontend | Backend sync endpoint có sẵn nhưng frontend không gọi |
| Watermark Canvas API | Ảnh không đóng dấu GPS + thời gian + mã tủ |
| Online/offline UI | Không biết app đang offline |

---

## Goals

### Functional goals
- Inspector có thể **tạo inspection đầy đủ khi không có mạng**
- Ảnh được nén + watermark + lưu base64 vào IndexedDB khi offline
- Khi có mạng trở lại → tự động đẩy toàn bộ draft lên `/sync`
- User có thể cài app vào điện thoại qua "Add to Home Screen"
- App hoạt động với icon offline trên màn hình home

### Performance goals
- Inspections draft không bị mất khi mạng chậm hoặc mất kết nối
- Không block UI khi sync đang chạy background
- Không gửi ảnh trùng (đã có SHA-256 dedup)

### Non-goals
- Không triển khai Web Push Notifications (Notification API) — có thể thêm sau
- Không thay đổi backend API contract (đã ổn định)
- Không generic hóa hệ thống sync quá mức cần thiết

---

## Proposed Architecture

### 1. PWA Layer
```
vite.config.js               ← thêm vite-plugin-pwa
public/
  manifest.json              ← name, icons, theme_color, display
  icons/                    ← 192x192, 512x512 PNG
src/
  sw.js hoặc do plugin gen  ← Service Worker (Workbox)
```

### 2. Offline Data Layer
```
src/db/
  index.js                  ← DEXIE SCHEMA (đã có, chỉ cần kích hoạt)
  syncService.js            ← MỞ RỘNG: gọi POST /sync khi online
```

### 3. Watermark Camera
```
src/composables/
  useWatermark.js           ← Canvas API: nén + watermark Lat/Lng + thời gian + mã tủ
```

### 4. Offline Sync Orchestration
```
src/composables/
  useOfflineSync.js         ← Online/offline detection, sync queue, background push
src/stores/
  syncStore.js              ← Pinia: draftCount, lastSyncAt, isSyncing
```

### 5. Inspection Draft → Dexie
```
Khi submit:
  1. Lưu inspection vào Dexie (sync_status = 'draft')
  2. Lưu details + base64 photos vào Dexie
  3. Nếu online → push ngay
  4. Nếu offline → chờ online event
```

---

## Implementation Phases

## Phase 1 — PWA Foundation

### Task 1.1: Cài `vite-plugin-pwa`
```bash
npm install -D vite-plugin-pwa
```

### Task 1.2: Tạo `public/` folder
```
public/
  manifest.json
  icons/
    icon-192.png
    icon-512.png
    apple-touch-icon.png
```

**manifest.json:**
```json
{
  "name": "FBB Inspection",
  "short_name": "FBB",
  "description": "Field inspection app for FBB telecom infrastructure",
  "start_url": "/",
  "display": "standalone",
  "background_color": "#ffffff",
  "theme_color": "#2563eb",
  "orientation": "portrait",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

### Task 1.3: Cấu hình `vite.config.js`
```js
import { VitePWA } from 'vite-plugin-pwa'

export default defineConfig({
  plugins: [
    vue(),
    VitePWA({
      registerType: 'autoUpdate',
      includeAssets: ['icons/*.png'],
      manifest,
      workbox: {
        globPatterns: ['**/*.{js,css,html,ico,png,svg,woff2}'],
        runtimeCaching: [
          {
            urlPattern: /^https:\/\/fonts\.googleapis\.com/,
            handler: 'StaleWhileRevalidate',
          },
          {
            urlPattern: /\/api\//,
            handler: 'NetworkFirst',
            options: {
              cacheName: 'api-cache',
              networkTimeoutSeconds: 5,
            },
          },
        ],
      },
    }),
  ],
  // ...
})
```

### Task 1.4: Generate PWA icons
Tạo 2 icon PNG đơn giản (192x192, 512x512) — có thể dùng favicon hiện tại hoặc tạo placeholder.

### Task 1.5: Đăng ký Service Worker trong `main.js`
```js
import { registerSW } from 'virtual:pwa-register'
const updateSW = registerSW({
  onNeedRefresh() { /* optional: show "Update available" toast */ },
  onOfflineReady() { console.log('App ready to work offline') },
})
```

**Verify:**
- Build (`npm run build`) tạo ra `dist/sw.js`
- DevTools → Application → Service Workers → thấy SW đăng ký
- Lighthouse PWA score ≥ 75

---

## Phase 2 — Dexie: Kích hoạt Offline Inspection Write

### Task 2.1: Mở rộng Dexie schema

**Thêm columns vào `inspections` table:**
- `overall_photos` (array of base64 strings)
- `lat`, `lng`
- `sync_status` — `'draft'` | `'pending'` | `'synced'`
- `error` — lỗi sync gần nhất

**Thêm columns vào `inspectionDetails` table:**
- `image_base64`
- `note`
- `is_failed`
- `score_awarded`

### Task 2.2: Viết `src/db/inspectionDraft.js`

```js
// Lưu inspection draft vào Dexie
async function saveDraft(inspectionData) {
  const { id: localId } = await db.inspections.add({
    ...inspectionData,
    sync_status: 'draft',
    created_at: new Date().toISOString(),
  })

  // Lưu từng detail item vào inspectionDetails
  for (const detail of inspectionData.details) {
    await db.inspectionDetails.add({
      inspection_id: localId,
      ...detail,
    })
  }

  return localId
}

// Lấy tất cả draft
async function getDrafts() {
  return db.inspections.where('sync_status').anyOf(['draft', 'pending']).toArray()
}
```

### Task 2.3: Chuyển `InspectionView.vue` sang dùng Dexie

**Khi submit:**
1. Lưu toàn bộ inspection (kể cả ảnh base64) vào Dexie
2. Nếu online → gọi `syncService.pushDrafts()`
3. Nếu offline → hiện toast "Đã lưu offline, sẽ đồng bộ khi có mạng"

**Khi mount:**
1. Check pending drafts trong Dexie
2. Nếu có → hiện badge "Có {n} biên bản chưa đồng bộ"
3. Tự động trigger sync khi online

**Verify:**
- Tắt network trong DevTools → tạo inspection → vẫn lưu được
- Bật network → tự động sync trong 5-10s

---

## Phase 3 — Backend Sync Integration

### Task 3.1: Viết `syncService.pushDrafts()` trong `src/db/syncService.js`

```js
import api from '@/services/api'

export async function pushDrafts() {
  const drafts = await db.inspections
    .where('sync_status')
    .equals('draft')
    .toArray()

  if (!drafts.length) return { synced: 0 }

  // Map Dexie format → API format
  const payload = drafts.map(draft => ({
    plan_detail_id: draft.plan_detail_id,
    checklist_id: draft.checklist_id,
    cabinet_code: draft.cabinet_code,
    lat: draft.lat,
    lng: draft.lng,
    overall_photos: draft.overall_photos, // base64 array
    details: draft.details.map(d => ({
      item_id: d.item_id,
      is_failed: d.is_failed,
      score_awarded: d.score_awarded,
      image_base64: d.image_base64, // base64
      note: d.note,
    })),
  }))

  const { data } = await api.post('/sync', { inspections: payload })

  // Mark all as synced
  const ids = drafts.map(d => d.id)
  await db.inspections.where('id').anyOf(ids).modify({ sync_status: 'synced' })

  return data
}
```

### Task 3.2: Cập nhật `SyncController.php` nếu cần

Kiểm tra `SyncController` hiện tại xem đã nhận `overall_photos` array chưa. Nếu chưa → thêm field vào validation + xử lý lưu ảnh.

### Task 3.3: Idempotency — tránh sync trùng

Backend đã check `plan_detail_id` đã có inspection → skip. Đảm bảo frontend không retry vô tận.

**Verify:**
- POST /sync với draft → nhận `{ synced: N }` response
- Dexie inspection có `sync_status = 'synced'`

---

## Phase 4 — Online/Offline Detection + Auto Sync

### Task 4.1: Viết `src/composables/useOfflineSync.js`

```js
import { ref, onMounted, onUnmounted } from 'vue'
import { pushDrafts } from '@/db/syncService'

export function useOfflineSync() {
  const isOnline = ref(navigator.onLine)
  const isSyncing = ref(false)
  const draftCount = ref(0)
  const lastSyncAt = ref(null)

  async function sync() {
    if (!isOnline.value || isSyncing.value) return
    isSyncing.value = true
    try {
      await pushDrafts()
      lastSyncAt.value = new Date().toISOString()
    } finally {
      isSyncing.value = false
      await refreshDraftCount()
    }
  }

  async function refreshDraftCount() {
    const { getPendingInspections } = await import('@/db/syncService')
    const pending = await getPendingInspections()
    draftCount.value = pending.length
  }

  // Auto-sync khi online
  function onOnline() {
    isOnline.value = true
    sync()
  }

  function onOffline() {
    isOnline.value = false
  }

  onMounted(() => {
    window.addEventListener('online', onOnline)
    window.addEventListener('offline', onOffline)
    refreshDraftCount()
    // Sync ngay nếu đang online
    if (navigator.onLine) sync()
  })

  onUnmounted(() => {
    window.removeEventListener('online', onOnline)
    window.removeEventListener('offline', onOffline)
  })

  return { isOnline, isSyncing, draftCount, lastSyncAt, sync, refreshDraftCount }
}
```

### Task 4.2: Thêm Sync Status UI

**Trong InspectorLayout hoặc Header:**
- 🟢 "Online" / 🔴 "Offline" badge khi detect offline
- 🔄 "Syncing..." khi đang đồng bộ
- 📤 "{n} biên bản chưa đồng bộ" badge nếu `draftCount > 0`
- ✅ "Đồng bộ thành công lúc {time}" toast

**Verify:**
- Tắt wifi → hiện "Offline" banner → vẫn tạo được inspection
- Bật wifi → tự đồng bộ trong 5s → badge mất

---

## Phase 5 — Watermark Camera

### Task 5.1: Viết `src/composables/useWatermark.js`

```js
import imageCompression from 'browser-image-compression'

export async function captureWithWatermark(file, { lat, lng, cabinetCode }) {
  return new Promise((resolve, reject) => {
    const img = new Image()
    img.onload = async () => {
      const canvas = document.createElement('canvas')
      canvas.width = img.width
      canvas.height = img.height
      const ctx = canvas.getContext('2d')

      // Vẽ ảnh gốc
      ctx.drawImage(img, 0, 0)

      // Watermark box
      const boxH = 50
      ctx.fillStyle = 'rgba(0,0,0,0.6)'
      ctx.fillRect(0, img.height - boxH, img.width, boxH)

      // Text
      ctx.fillStyle = '#ffffff'
      ctx.font = `${Math.max(12, img.width * 0.02)}px monospace`
      const timestamp = new Date().toLocaleString('vi-VN')
      const coordText = `${lat?.toFixed(6) ?? '—'}, ${lng?.toFixed(6) ?? '—'}`
      ctx.fillText(`📍 ${coordText}`, 10, img.height - 30)
      ctx.font = `${Math.max(10, img.width * 0.015)}px monospace`
      ctx.fillText(`${cabinetCode} | ${timestamp}`, 10, img.height - 10)

      // Nén + xuất JPEG 0.6
      canvas.toBlob(async (blob) => {
        try {
          const compressed = await imageCompression(
            new File([blob], 'watermarked.jpg', { type: 'image/jpeg' }),
            { maxSizeMB: 0.5, maxWidthOrHeight: 1920, useWebWorker: true }
          )
          const base64 = await blobToBase64(compressed)
          resolve(base64)
        } catch (e) {
          reject(e)
        }
      }, 'image/jpeg', 0.75)
    }
    img.onerror = reject
    img.src = URL.createObjectURL(file)
  })
}

function blobToBase64(blob) {
  return new Promise((resolve) => {
    const reader = new FileReader()
    reader.onloadend = () => resolve(reader.result)
    reader.readAsDataURL(blob)
  })
}
```

### Task 5.2: Tích hợp vào `MobileImageUploader.vue`

**Khi user chọn/chụp ảnh:**
```js
import { captureWithWatermark } from '@/composables/useWatermark'

async function onFileSelected(file) {
  // Lấy GPS trước
  const position = await getCurrentPosition()

  const base64 = await captureWithWatermark(file, {
    lat: position.lat,
    lng: position.lng,
    cabinetCode: props.cabinetCode,
  })

  // Lưu base64 (không upload ngay)
  emit('update:modelValue', base64)
}
```

### Task 5.3: Fallback khi GPS không lấy được
Nếu `navigator.geolocation.getCurrentPosition()` fail → watermark vẫn vẽ ảnh, tọa độ để "—"

**Verify:**
- Chụp ảnh → mở ảnh → thấy watermark góc phải dưới: `{lat}, {lng}` + `{cabinetCode}` + timestamp
- Ảnh nén < 500KB

---

## Phase 6 — Install Prompt (Add to Home Screen)

### Task 6.1: Thêm `beforeinstallprompt` listener

```js
// Trong App.vue hoặc InspectorLayout
const deferredPrompt = ref(null)

window.addEventListener('beforeinstallprompt', (e) => {
  e.preventDefault()
  deferredPrompt.value = e
  showInstallBanner.value = true
})

async function installApp() {
  if (!deferredPrompt.value) return
  deferredPrompt.value.prompt()
  const { outcome } = await deferredPrompt.value.userChoice
  if (outcome === 'accepted') {
    showInstallBanner.value = false
  }
  deferredPrompt.value = null
}
```

### Task 6.2: UI Install Banner

```html
<div v-if="showInstallBanner" class="fixed bottom-20 left-4 right-4 bg-primary-600 text-white p-4 rounded-xl shadow-lg flex items-center gap-3 z-50">
  <Smartphone class="w-6 h-6" />
  <div class="flex-1">
    <p class="font-semibold text-sm">Cài đặt FBB Inspection</p>
    <p class="text-xs opacity-80">Dùng offline, truy cập nhanh từ màn hình chính</p>
  </div>
  <button @click="installApp" class="bg-white text-primary-600 px-3 py-1.5 rounded-lg text-sm font-bold">Cài</button>
  <button @click="showInstallBanner = false" class="opacity-60">✕</button>
</div>
```

**Verify:**
- Chrome/Safari mobile → nhìn thấy banner "Cài đặt FBB Inspection"
- Nhấn Cài → xuất hiện native A2HS dialog
- Sau khi cài → icon app xuất hiện trên home screen

---

## Phase 7 — Precache Master Data (Cache-First)

### Task 7.1: Mở rộng Dexie sync

**Khi login thành công:**
```js
import { syncCabinets, syncChecklists, syncBatches } from '@/db/syncService'

async function cacheMasterData(userId) {
  await Promise.all([
    syncCabinets(),
    syncChecklists(),
    syncBatches(userId),
  ])
}
```

### Task 7.2: Read from Dexie khi offline

**Trong stores `batches.js`, `checklists.js`:**
```js
// Nếu offline → đọc từ Dexie
if (!navigator.onLine) {
  const local = await db.batches.where('user_id').equals(userId).toArray()
  if (local.length) { this.batches = local; return }
}
```

**Verify:**
- Login khi có mạng → data cache vào IndexedDB
- Tắt mạng → vào Batches → vẫn thấy dữ liệu

---

## File Plan

### Files to modify
| File | Change |
|------|--------|
| `frontend/package.json` | Thêm `vite-plugin-pwa` devDependency |
| `frontend/vite.config.js` | Cấu hình VitePWA plugin |
| `frontend/src/main.js` | Đăng ký Service Worker |
| `frontend/src/db/index.js` | Mở rộng Dexie schema (thêm columns) |
| `frontend/src/db/syncService.js` | Thêm `pushDrafts()`, `getPendingInspections()` |
| `frontend/src/db/inspectionDraft.js` | NEW: saveDraft, getDrafts, updateDraft |
| `frontend/src/composables/useOfflineSync.js` | NEW: online detection, auto sync |
| `frontend/src/composables/useWatermark.js` | NEW: Canvas watermark + compression |
| `frontend/src/stores/syncStore.js` | NEW: draftCount, isSyncing, lastSyncAt |
| `frontend/src/views/inspector/InspectionView.vue` | Gọi saveDraft + pushDrafts |
| `frontend/src/components/common/MobileImageUploader.vue` | Tích hợp watermark |
| `frontend/src/views/inspector/InspectorLayout.vue` | Thêm sync status UI + install banner |
| `frontend/src/App.vue` | beforeinstallprompt listener |

### Files to create
| File | Purpose |
|------|--------|
| `public/manifest.json` | PWA manifest |
| `public/icons/icon-192.png` | PWA icon 192px |
| `public/icons/icon-512.png` | PWA icon 512px |

### Backend files (nếu cần update)
| File | Change |
|------|--------|
| `backend/app/Http/Controllers/SyncController.php` | Thêm `overall_photos` vào payload validation nếu chưa có |

---

## Acceptance Criteria

### PWA
- [ ] Lighthouse PWA score ≥ 75
- [ ] "Add to Home Screen" prompt hiện trên Chrome/Safari mobile
- [ ] Service Worker cache static assets thành công

### Offline Inspection
- [ ] Tạo inspection khi offline → lưu vào IndexedDB với `sync_status = 'draft'`
- [ ] Ảnh được nén + watermark + lưu base64 (không upload server)
- [ ] Bật mạng → tự động sync trong ≤10s
- [ ] Toast "Đã đồng bộ {n} biên bản" khi sync thành công

### Offline Data Access
- [ ] Batches/Checklists vẫn hiển thị khi offline (từ Dexie)
- [ ] Sync status badge hiển thị số draft chưa đồng bộ

### Watermark
- [ ] Ảnh có watermark: tọa độ GPS + mã tủ + thời gian ở góc phải dưới
- [ ] Ảnh nén < 500KB sau watermark

### UX
- [ ] Banner offline hiện khi mất kết nối
- [ ] Badge "Đang sync..." khi đồng bộ
- [ ] Không crash khi GPS không khả dụng

---

## Recommended Execution Order

1. **Phase 1** (PWA Foundation) — cài plugin, tạo manifest, icons, đăng ký SW
2. **Phase 2** (Dexie: write path) — mở rộng schema, viết `inspectionDraft.js`
3. **Phase 3** (Backend sync integration) — viết `pushDrafts()`, kết nối `/sync`
4. **Phase 4** (Auto sync) — `useOfflineSync.js`, online/offline UI
5. **Phase 5** (Watermark) — Canvas API, tích hợp vào uploader
6. **Phase 6** (Install prompt) — A2HS banner
7. **Phase 7** (Precache master data) — cache-first cabinets/checklists/batches

---

## Delivery Strategy

### Round 1 — Core Offline (Highest Risk)
- PWA foundation + Dexie write + sync push
- Tập trung correctness trước: draft không được mất

### Round 2 — UX Polish
- Online/offline banner + sync status
- Watermark camera

### Round 3 — PWA Polish
- Install prompt
- Precache master data
- Lighthouse audit

---

## Notes

### Workbox vs. Manual SW
Ưu tiên `vite-plugin-pwa` + Workbox (tự động gen SW) thay vì viết SW thủ công. Workbox có chiến lược cache phong phú: `NetworkFirst` cho API, `CacheFirst` cho assets.

### Image Storage Cost
- `browser-image-compression` đã cài → chỉ cần dùng
- Nén JPEG 0.6 quality + resize max 1920px → ảnh thường ~100-300KB
- Base64 tăng ~33% → ảnh cuối ~150-400KB mỗi cái
- 4-8 ảnh tổng ~1-3MB per inspection → acceptable cho IndexedDB

### Security
- Token auth vẫn dùng `Authorization: Bearer` header
- Sync endpoint đã protected bởi `auth:sanctum`
- Base64 ảnh decode phía server → không trust client coordinates

---

## Done When
- [ ] PWA installable trên Android/iOS
- [ ] Inspection tạo được khi offline (không mất dữ liệu)
- [ ] Sync tự động khi online trở lại
- [ ] Ảnh có watermark GPS + timestamp
- [ ] Không làm hỏng flow hiện tại (vẫn upload được khi online)
