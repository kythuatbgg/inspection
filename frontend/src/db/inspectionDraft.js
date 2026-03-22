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
