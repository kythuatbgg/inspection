import { describe, it, expect, vi, beforeEach } from 'vitest'

// Mock inspectionDraft before importing useMasterDataCache
vi.mock('@/db/inspectionDraft', () => ({
  syncCabinets: vi.fn().mockResolvedValue(undefined),
  syncChecklists: vi.fn().mockResolvedValue(undefined),
  syncBatches: vi.fn().mockResolvedValue(undefined),
}))

import { useMasterDataCache } from '@/composables/useMasterDataCache'
import { syncCabinets, syncChecklists, syncBatches } from '@/db/inspectionDraft'

describe('useMasterDataCache', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('exports', () => {
    it('is a function', () => {
      expect(typeof useMasterDataCache).toBe('function')
    })
  })

  describe('return shape', () => {
    it('returns isCaching, cacheError, cacheMasterData', () => {
      const { isCaching, cacheError, cacheMasterData } = useMasterDataCache()
      expect(isCaching.value).toBe(false)
      expect(cacheError.value).toBe(null)
      expect(typeof cacheMasterData).toBe('function')
    })
  })

  describe('cacheMasterData', () => {
    it('calls syncCabinets, syncChecklists, syncBatches concurrently', async () => {
      const { cacheMasterData } = useMasterDataCache()
      await cacheMasterData()
      expect(syncCabinets).toHaveBeenCalled()
      expect(syncChecklists).toHaveBeenCalled()
      expect(syncBatches).toHaveBeenCalledWith(null)
    })

    it('passes userId to syncBatches when provided', async () => {
      const { cacheMasterData } = useMasterDataCache()
      await cacheMasterData(42)
      expect(syncBatches).toHaveBeenCalledWith(42)
    })

    it('sets isCaching to true during execution', async () => {
      syncCabinets.mockImplementation(() => new Promise(r => setTimeout(r, 10)))
      const { isCaching, cacheMasterData } = useMasterDataCache()
      const promise = cacheMasterData()
      expect(isCaching.value).toBe(true)
      await promise
      expect(isCaching.value).toBe(false)
    })

    it('sets isCaching to false even if an error occurs', async () => {
      syncCabinets.mockRejectedValue(new Error('oops'))
      const { isCaching, cacheError, cacheMasterData } = useMasterDataCache()
      await cacheMasterData()
      expect(isCaching.value).toBe(false)
      expect(cacheError.value).toBe('oops')
    })

    it('sets cacheError on failure', async () => {
      syncCabinets.mockRejectedValue(new Error('cabinet failed'))
      const { cacheError, cacheMasterData } = useMasterDataCache()
      await cacheMasterData()
      expect(cacheError.value).toBe('cabinet failed')
    })

    it('does not re-enter if already caching (idempotent guard)', async () => {
      let callCount = 0
      syncCabinets.mockImplementation(async () => { callCount++; await new Promise(r => setTimeout(r, 20)) })
      const { cacheMasterData } = useMasterDataCache()
      const p1 = cacheMasterData()
      const p2 = cacheMasterData() // should be ignored
      await Promise.all([p1, p2])
      expect(callCount).toBe(1)
    })
  })
})
