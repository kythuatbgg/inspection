import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import {
  db,
  SYNC_DRAFT,
  SYNC_PENDING,
  SYNC_SYNCED,
  SYNC_FAILED,
  saveDraft,
  getPendingInspections,
  getPendingRetryInspections,
  getFailedInspections,
  markAsSynced,
  markAsPending,
  markAsFailed,
} from '@/db/inspectionDraft'

describe('inspectionDraft (real Dexie)', () => {
  beforeEach(async () => {
    await db.inspections.clear()
    await db.inspectionDetails.clear()
  })

  afterEach(async () => {
    await db.inspections.clear()
    await db.inspectionDetails.clear()
  })

  // ── Constants ─────────────────────────────────────────────────
  describe('sync status constants', () => {
    it('SYNC_DRAFT is "draft"', () => { expect(SYNC_DRAFT).toBe('draft') })
    it('SYNC_PENDING is "pending"', () => { expect(SYNC_PENDING).toBe('pending') })
    it('SYNC_SYNCED is "synced"', () => { expect(SYNC_SYNCED).toBe('synced') })
    it('SYNC_FAILED is "failed"', () => { expect(SYNC_FAILED).toBe('failed') })
  })

  // ── saveDraft ────────────────────────────────────────────────
  describe('saveDraft', () => {
    it('writes inspection row with sync_status=draft', async () => {
      const id = await saveDraft({ plan_detail_id: 5, cabinet_code: 'CAB001' })
      expect(id).toBeDefined()
      const rows = await db.inspections.toArray()
      expect(rows).toHaveLength(1)
      expect(rows[0]).toMatchObject({
        plan_detail_id: 5,
        cabinet_code: 'CAB001',
        sync_status: 'draft',
        sync_error: null,
        sync_retry_count: 0,
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

  // ── getPendingInspections ─────────────────────────────────────
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

  // ── getPendingRetryInspections ────────────────────────────────
  describe('getPendingRetryInspections', () => {
    it('returns pending inspections only', async () => {
      await db.inspections.add({ plan_detail_id: 1, sync_status: 'pending', created_at: new Date().toISOString() })
      await db.inspections.add({ plan_detail_id: 2, sync_status: 'draft', created_at: new Date().toISOString() })
      const pending = await getPendingRetryInspections()
      expect(pending).toHaveLength(1)
      expect(pending[0].sync_status).toBe('pending')
    })
  })

  // ── getFailedInspections ──────────────────────────────────────
  describe('getFailedInspections', () => {
    it('returns failed inspections only', async () => {
      await db.inspections.add({ plan_detail_id: 1, sync_status: 'failed', sync_error: 'token_expired', created_at: new Date().toISOString() })
      await db.inspections.add({ plan_detail_id: 2, sync_status: 'synced', created_at: new Date().toISOString() })
      const failed = await getFailedInspections()
      expect(failed).toHaveLength(1)
      expect(failed[0].sync_status).toBe('failed')
      expect(failed[0].sync_error).toBe('token_expired')
    })
  })

  // ── markAsSynced ─────────────────────────────────────────────
  describe('markAsSynced', () => {
    it('updates inspection to synced status and clears error', async () => {
      const id = await saveDraft({ plan_detail_id: 1, sync_error: 'old_error' })
      await markAsSynced(id)
      const rows = await db.inspections.toArray()
      expect(rows[0].sync_status).toBe('synced')
      expect(rows[0].sync_error).toBeNull()
    })
  })

  // ── markAsPending ─────────────────────────────────────────────
  describe('markAsPending', () => {
    it('increments sync_retry_count', async () => {
      const id = await saveDraft({ plan_detail_id: 1 })
      await markAsPending(id)
      const rows = await db.inspections.toArray()
      expect(rows[0].sync_status).toBe('pending')
      expect(rows[0].sync_retry_count).toBe(1)
    })

    it('increments from existing count', async () => {
      const id = await db.inspections.add({
        plan_detail_id: 1,
        sync_status: 'pending',
        sync_retry_count: 3,
        created_at: new Date().toISOString(),
      })
      await markAsPending(id)
      const rows = await db.inspections.toArray()
      expect(rows[0].sync_retry_count).toBe(4)
    })
  })

  // ── markAsFailed ─────────────────────────────────────────────
  describe('markAsFailed', () => {
    it('sets status=failed with error message', async () => {
      const id = await saveDraft({ plan_detail_id: 1 })
      await markAsFailed(id, 'token_expired')
      const rows = await db.inspections.toArray()
      expect(rows[0].sync_status).toBe('failed')
      expect(rows[0].sync_error).toBe('token_expired')
    })

    it('sets status=failed with null error', async () => {
      const id = await saveDraft({ plan_detail_id: 1 })
      await markAsFailed(id, null)
      const rows = await db.inspections.toArray()
      expect(rows[0].sync_status).toBe('failed')
      expect(rows[0].sync_error).toBeNull()
    })
  })
})
