import { describe, it, expect, beforeEach } from 'vitest'
import { useSyncStore } from '@/stores/syncStore'

describe('syncStore', () => {
  let store

  beforeEach(() => {
    store = useSyncStore()
    store.resetAll()
  })

  // Pinia setup stores auto-unwrap refs — access as store.isOnline not store.isOnline.value
  describe('initial state', () => {
    it('isOnline is boolean', () => {
      expect(typeof store.isOnline).toBe('boolean')
    })
    it('isSyncing defaults to false', () => {
      expect(store.isSyncing).toBe(false)
    })
    it('draftCount defaults to 0', () => {
      expect(store.draftCount).toBe(0)
    })
    it('failedCount defaults to 0', () => {
      expect(store.failedCount).toBe(0)
    })
    it('syncError defaults to null', () => {
      expect(store.syncError).toBeNull()
    })
    it('isExhausted defaults to false', () => {
      expect(store.isExhausted).toBe(false)
    })
    it('lastSyncAt defaults to null', () => {
      expect(store.lastSyncAt).toBeNull()
    })
  })

  describe('setOnline', () => {
    it('sets isOnline to true', () => {
      store.setOnline(true)
      expect(store.isOnline).toBe(true)
    })
    it('sets isOnline to false', () => {
      store.setOnline(false)
      expect(store.isOnline).toBe(false)
    })
  })

  describe('setSyncing', () => {
    it('sets isSyncing to true', () => {
      store.setSyncing(true)
      expect(store.isSyncing).toBe(true)
    })
    it('sets isSyncing to false', () => {
      store.setSyncing(true)
      store.setSyncing(false)
      expect(store.isSyncing).toBe(false)
    })
  })

  describe('setDraftCount', () => {
    it('sets draftCount', () => {
      store.setDraftCount(5)
      expect(store.draftCount).toBe(5)
    })
  })

  describe('setFailedCount', () => {
    it('sets failedCount', () => {
      store.setFailedCount(3)
      expect(store.failedCount).toBe(3)
    })
  })

  describe('setSyncError', () => {
    it('sets syncError to a message', () => {
      store.setSyncError('token_expired')
      expect(store.syncError).toBe('token_expired')
    })
    it('sets syncError to null', () => {
      store.setSyncError('token_expired')
      store.setSyncError(null)
      expect(store.syncError).toBeNull()
    })
  })

  describe('setExhausted', () => {
    it('sets isExhausted to true', () => {
      store.setExhausted(true)
      expect(store.isExhausted).toBe(true)
    })
    it('sets isExhausted to false', () => {
      store.setExhausted(true)
      store.setExhausted(false)
      expect(store.isExhausted).toBe(false)
    })
  })

  describe('setLastSyncAt', () => {
    it('sets lastSyncAt', () => {
      const now = new Date().toISOString()
      store.setLastSyncAt(now)
      expect(store.lastSyncAt).toBe(now)
    })
  })

  describe('resetSyncState', () => {
    it('resets all sync state fields', () => {
      store.setOnline(false)
      store.setSyncing(true)
      store.setDraftCount(5)
      store.setFailedCount(3)
      store.setSyncError('token_expired')
      store.setExhausted(true)
      store.setLastSyncAt('2024-01-01T00:00:00Z')
      store.resetSyncState()
      expect(store.isOnline).toBe(navigator.onLine)
      expect(store.isSyncing).toBe(false)
      expect(store.draftCount).toBe(0)
      expect(store.failedCount).toBe(0)
      expect(store.syncError).toBeNull()
      expect(store.isExhausted).toBe(false)
      expect(store.lastSyncAt).toBeNull()
    })
  })

  describe('resetAll', () => {
    it('resets all state', () => {
      store.setDraftCount(99)
      store.setSyncError('sync_failed')
      store.resetAll()
      expect(store.draftCount).toBe(0)
      expect(store.syncError).toBeNull()
    })
  })
})
