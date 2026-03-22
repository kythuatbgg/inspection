import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { saveDraft, getPendingInspections, markAsSynced } from '@/db/inspectionDraft'

describe('inspectionDraft', () => {
  let db

  beforeEach(async () => {
    // Dynamically import to get real db (no dexie mock needed)
    const mod = await import('@/db/inspectionDraft')
    db = mod.db
    // Clear tables between tests
    await db.inspections.clear()
    await db.inspectionDetails.clear()
  })

  afterEach(async () => {
    await db?.inspections.clear()
    await db?.inspectionDetails.clear()
  })

  describe('saveDraft', () => {
    it('writes inspection row with sync_status draft', async () => {
      const id = await saveDraft({ plan_detail_id: 5, cabinet_code: 'CAB001' })
      expect(id).toBeDefined()
      const rows = await db.inspections.toArray()
      expect(rows).toHaveLength(1)
      expect(rows[0]).toMatchObject({
        plan_detail_id: 5,
        cabinet_code: 'CAB001',
        sync_status: 'draft',
      })
      expect(typeof rows[0].created_at).toBe('string')
    })

    it('writes inspectionDetails for each detail', async () => {
      const details = [
        { item_id: 1, is_failed: false, score_awarded: 10 },
        { item_id: 2, is_failed: true, image_base64: 'abc', note: 'broken' },
      ]
      await saveDraft({ plan_detail_id: 1, details })
      const detailRows = await db.inspectionDetails.toArray()
      expect(detailRows).toHaveLength(2)
      expect(detailRows[0]).toMatchObject({ item_id: 1, is_failed: false })
      expect(detailRows[1]).toMatchObject({
        item_id: 2,
        is_failed: true,
        image_base64: 'abc',
        note: 'broken',
      })
    })

    it('writes overall_photos base64 array', async () => {
      await saveDraft({ plan_detail_id: 1, overall_photos: ['img1', 'img2'], details: [] })
      const rows = await db.inspections.toArray()
      expect(rows[0].overall_photos).toEqual(['img1', 'img2'])
    })

    it('returns local auto-increment id', async () => {
      const id1 = await saveDraft({ plan_detail_id: 1 })
      const id2 = await saveDraft({ plan_detail_id: 2 })
      expect(id2).toBeGreaterThan(id1)
    })
  })

  describe('getPendingInspections', () => {
    it('returns draft inspections only', async () => {
      await saveDraft({ plan_detail_id: 1 })
      await db.inspections.add({ plan_detail_id: 2, sync_status: 'synced', created_at: new Date().toISOString() })
      const pending = await getPendingInspections()
      expect(pending).toHaveLength(1)
      expect(pending[0].sync_status).toBe('draft')
    })

    it('returns empty when no drafts', async () => {
      const pending = await getPendingInspections()
      expect(pending).toHaveLength(0)
    })
  })

  describe('markAsSynced', () => {
    it('updates inspection to synced status', async () => {
      const id = await saveDraft({ plan_detail_id: 1 })
      await markAsSynced(id)
      const rows = await db.inspections.toArray()
      expect(rows[0].sync_status).toBe('synced')
    })
  })
})
