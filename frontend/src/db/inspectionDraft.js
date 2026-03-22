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
    sync_status: 'draft',
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
 * Lấy tất cả inspection chưa sync.
 * Chỉ trả về `draft` và `pending` — không trả `synced`.
 */
export async function getDrafts() {
  const drafts = await db.inspections
    .where('sync_status')
    .anyOf(['draft', 'pending'])
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
    .equals('draft')
    .toArray()
}

/**
 * Đánh dấu inspection đã sync thành công.
 */
export async function markAsSynced(localId) {
  await db.inspections.update(localId, { sync_status: 'synced' })
}

// ── Master Data Sync (Phase 7) ─────────────────────────────────────────

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

  // Fetch items for each checklist concurrently
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

  // Fetch plan_details for each batch concurrently
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

// ── Lazy imports: only resolved when pushDrafts is called ──

/**
 * Push all pending inspections lên backend /api/sync.
 * Map inspectionDetails rows từ Dexie vào payload shape backend yêu cầu.
 * Sau khi sync thành công → đánh dấu từng draft là synced.
 */
export async function pushDrafts() {
  const { default: api } = await import('@/services/api')

  const drafts = await getPendingInspections()

  if (!drafts.length) return { synced: 0 }

  const withDetails = await Promise.all(
    drafts.map(async (draft) => {
      const details = await db.inspectionDetails
        .where('inspection_id')
        .equals(draft.id)
        .toArray()
      return { ...draft, details }
    })
  )

  const payload = withDetails.map(({ details, ...draft }) => ({
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
  }))

  const { data } = await api.post('/sync', { inspections: payload })

  await Promise.all(drafts.map(d => markAsSynced(d.id)))

  return data
}
