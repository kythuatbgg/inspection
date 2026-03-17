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

// Helper functions
export const syncService = {
  async syncCabinets() {
    const { data } = await fetch('/api/cabinets').then(r => r.json())
    await db.cabinets.bulkPut(data)
    return data.length
  },

  async syncChecklists() {
    const { data } = await fetch('/api/checklists').then(r => r.json())
    await db.checklists.bulkPut(data)
    return data.length
  },

  async syncBatches() {
    const { data } = await fetch('/api/batches').then(r => r.json())
    await db.batches.bulkPut(data)
    return data.length
  },

  async syncPlanDetails(batchId) {
    const { data } = await fetch(`/api/batches/${batchId}/plans`).then(r => r.json())
    await db.planDetails.bulkPut(data)
    return data.length
  },

  async getPendingInspections() {
    return await db.inspections
      .where('sync_status')
      .equals('draft')
      .toArray()
  },

  async markAsSynced(inspectionId) {
    await db.inspections.update(inspectionId, { sync_status: 'synced' })
  },

  async clearAll() {
    await Promise.all([
      db.cabinets.clear(),
      db.checklists.clear(),
      db.checklistItems.clear(),
      db.batches.clear(),
      db.planDetails.clear(),
      db.inspections.clear(),
      db.inspectionDetails.clear()
    ])
  }
}
