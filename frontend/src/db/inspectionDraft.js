import Dexie from 'dexie'

export const db = new Dexie('FBBInspectionDB')

db.version(1).stores({
  cabinets: 'cabinet_code, bts_site, name',
  checklists: 'id, name',
  checklistItems: 'id, checklist_id, category',
  batches: 'id, user_id, status',
  planDetails: 'id, batch_id, cabinet_code, status',
  inspections: '++id, plan_detail_id, cabinet_code, sync_status, created_at',
  inspectionDetails: '++id, inspection_id, item_id'
})

// v2: add sync metadata columns (non-destructive migration)
db.version(2).stores({
  cabinets: 'cabinet_code, bts_site, name',
  checklists: 'id, name',
  checklistItems: 'id, checklist_id, category',
  batches: 'id, user_id, status',
  planDetails: 'id, batch_id, cabinet_code, status',
  inspections: '++id, plan_detail_id, cabinet_code, sync_status, created_at',
  inspectionDetails: '++id, inspection_id, item_id'
}).upgrade(tx => {
  // Add nullable columns to existing inspections rows
  return tx.table('inspections').toCollection().modify(row => {
    if (row.sync_status === undefined) row.sync_status = 'draft'
    if (row.sync_error === undefined) row.sync_error = null
    if (row.sync_retry_count === undefined) row.sync_retry_count = 0
  })
})

// ── Sync Status Constants ────────────────────────────────────────────────
export const SYNC_DRAFT = 'draft'     // never synced
export const SYNC_PENDING = 'pending' // failed, retry scheduled
export const SYNC_SYNCED = 'synced'   // server confirmed
export const SYNC_FAILED = 'failed'   // exhausted, requires action

// ── Save / Read ─────────────────────────────────────────────────────────

/**
 * Lưu inspection draft vào IndexedDB (Dexie).
 * Ảnh được lưu dưới dạng base64 string để không phụ thuộc network.
 *
 * @param {Object} inspection — { plan_detail_id, checklist_id, cabinet_code, lat, lng, overall_photos[], details[] }
 * @returns {Promise<number>} local auto-increment id
 */
export async function saveDraft(inspection) {
  const { details = [], ...header } = inspection

  const localId = await db.inspections.add({
    ...header,
    sync_status: SYNC_DRAFT,
    sync_error: null,
    sync_retry_count: 0,
    created_at: new Date().toISOString(),
  })

  for (const detail of details) {
    await db.inspectionDetails.add({
      inspection_id: localId,
      ...detail,
    })
  }

  return localId
}

/**
 * Lấy tất cả inspection chưa sync (draft + pending).
 */
export async function getDrafts() {
  const drafts = await db.inspections
    .where('sync_status')
    .anyOf([SYNC_DRAFT, SYNC_PENDING])
    .toArray()

  const withDetails = await Promise.all(
    drafts.map(async (draft) => {
      const details = await db.inspectionDetails
        .where('inspection_id')
        .equals(draft.id)
        .toArray()
      return { ...draft, details }
    })
  )

  return withDetails
}

/**
 * Lấy inspection đang ở trạng thái draft (chưa sync, chưa pending).
 */
export async function getPendingInspections() {
  return db.inspections
    .where('sync_status')
    .equals(SYNC_DRAFT)
    .toArray()
}

/**
 * Lấy inspection đang ở trạng thái pending (đang retry).
 */
export async function getPendingRetryInspections() {
  return db.inspections
    .where('sync_status')
    .equals(SYNC_PENDING)
    .toArray()
}

/**
 * Lấy inspection đang ở trạng thái failed (exhausted).
 */
export async function getFailedInspections() {
  return db.inspections
    .where('sync_status')
    .equals(SYNC_FAILED)
    .toArray()
}

// ── Status Transitions ──────────────────────────────────────────────────

/**
 * Đánh dấu inspection đã sync thành công.
 */
export async function markAsSynced(localId) {
  await db.inspections.update(localId, {
    sync_status: SYNC_SYNCED,
    sync_error: null,
  })
}

/**
 * Đánh dấu inspection đang chờ retry (sau fail đầu tiên).
 */
export async function markAsPending(localId) {
  const row = await db.inspections.get(localId)
  await db.inspections.update(localId, {
    sync_status: SYNC_PENDING,
    sync_retry_count: (row?.sync_retry_count ?? 0) + 1,
  })
}

/**
 * Đánh dấu inspection đã exhausted — không retry nữa, cần action từ user.
 * @param {number} localId
 * @param {string|null} errorMsg
 */
export async function markAsFailed(localId, errorMsg = null) {
  await db.inspections.update(localId, {
    sync_status: SYNC_FAILED,
    sync_error: errorMsg,
  })
}

// ── Master Data Sync (Phase 7) ──────────────────────────────────────────

/**
 * Fetch cabinets from API → bulkPut into Dexie cabinets table.
 */
export async function syncCabinets() {
  const { default: api } = await import('@/services/api')
  const response = await api.get('/cabinets')
  const items = response.data?.data ?? response.data ?? []
  if (items.length) {
    await db.cabinets.bulkPut(items)
  }
}

/**
 * Fetch checklists from API → bulkPut into Dexie checklists table.
 * Also fetches and stores checklist items into checklistItems table.
 */
export async function syncChecklists() {
  const { default: api } = await import('@/services/api')
  const response = await api.get('/checklists')
  const checklists = response.data?.data ?? response.data ?? []
  if (!checklists.length) return

  await db.checklists.bulkPut(checklists)

  await Promise.all(
    checklists.map(async (cl) => {
      try {
        const res = await api.get(`/checklists/${cl.id}/items`)
        const items = res.data?.data ?? res.data ?? []
        if (items.length) {
          await db.checklistItems.bulkPut(
            items.map(item => ({ ...item, checklist_id: cl.id }))
          )
        }
      } catch {
        // Skip items if fetch fails
      }
    })
  )
}

/**
 * Fetch batches for a given userId → bulkPut into Dexie batches table.
 * Also fetches and stores planDetails for each batch.
 */
export async function syncBatches(userId) {
  const { default: api } = await import('@/services/api')
  const response = await api.get('/batches', {
    params: userId ? { user_id: userId } : {}
  })
  const batches = response.data?.data ?? response.data ?? []
  if (!batches.length) return

  await db.batches.bulkPut(batches)

  await Promise.all(
    batches.map(async (batch) => {
      try {
        const res = await api.get(`/batches/${batch.id}/plans`)
        const plans = res.data?.data ?? res.data ?? []
        if (plans.length) {
          await db.planDetails.bulkPut(
            plans.map(p => ({ ...p, batch_id: batch.id }))
          )
        }
      } catch {
        // Skip plans if fetch fails
      }
    })
  )
}

// ── Push Drafts ─────────────────────────────────────────────────────────

/**
 * Sync a single inspection to backend.
 * Returns { status: 'synced'|'failed', reason?: string }
 */
async function syncOneInspection(draft) {
  const { default: api } = await import('@/services/api')

  const details = await db.inspectionDetails
    .where('inspection_id')
    .equals(draft.id)
    .toArray()

  const payload = {
    plan_detail_id: draft.plan_detail_id,
    checklist_id: draft.checklist_id,
    cabinet_code: draft.cabinet_code,
    lat: draft.lat,
    lng: draft.lng,
    overall_photos: draft.overall_photos ?? [],
    details: details.map(({ image_base64, note, is_failed, score_awarded, item_id }) => ({
      item_id,
      is_failed,
      score_awarded,
      image_base64,
      note,
    })),
  }

  await api.post('/sync', { inspections: [payload] })
  return { status: 'synced' }
}

/**
 * Push all 'draft' inspections (never-synced) to backend.
 * Handles per-inspection errors — one fail does not block others.
 *
 * @returns {{ synced: number, pending: number, failed: number, reason: string|null }}
 */
export async function pushDrafts() {
  const drafts = await getPendingInspections()
  if (!drafts.length) return { synced: 0, pending: 0, failed: 0, reason: null }

  let synced = 0
  let pending = 0
  let failed = 0
  let reason = null

  for (const draft of drafts) {
    try {
      const result = await syncOneInspection(draft)
      if (result.status === 'synced') {
        await markAsSynced(draft.id)
        synced++
      }
    } catch (err) {
      const status = err?.response?.status

      if (status === 401) {
        // Token hết hạn — không retry, báo user
        await markAsFailed(draft.id, 'token_expired')
        reason = 'token_expired'
        failed++
        break // không sync thêm nữa
      } else {
        // Network / 5xx / timeout — đánh dấu pending để retry
        await markAsPending(draft.id)
        pending++
      }
    }
  }

  return { synced, pending, failed, reason }
}

/**
 * Retry all 'pending' inspections (previously failed, now retrying).
 * Stops at MAX_RETRIES — marks exhausted as 'failed'.
 *
 * @param {number} maxRetries — max retry_count before marking failed
 * @returns {{ synced: number, pending: number, failed: number }}
 */
export async function retryPendingInspections(maxRetries = 5) {
  const pending = await getPendingRetryInspections()
  if (!pending.length) return { synced: 0, pending: 0, failed: 0 }

  let synced = 0
  let stillPending = 0
  let failed = 0

  for (const draft of pending) {
    const count = draft.sync_retry_count ?? 0

    if (count >= maxRetries) {
      // Exhausted — đánh dấu failed, không retry nữa
      await markAsFailed(draft.id, 'max_retries')
      failed++
      continue
    }

    try {
      const result = await syncOneInspection(draft)
      if (result.status === 'synced') {
        await markAsSynced(draft.id)
        synced++
      }
    } catch (err) {
      const status = err?.response?.status

      if (status === 401) {
        await markAsFailed(draft.id, 'token_expired')
        failed++
        break
      } else {
        // Increment retry count via markAsPending (adds 1)
        await markAsPending(draft.id)
        stillPending++
      }
    }
  }

  return { synced, pending: stillPending, failed }
}
