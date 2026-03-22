import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, h } from 'vue'

// Mock inspectionDraft — all exports needed by useOfflineSync
vi.mock('@/db/inspectionDraft', () => ({
  getPendingInspections: vi.fn().mockResolvedValue([]),
  getFailedInspections: vi.fn().mockResolvedValue([]),
  getPendingRetryInspections: vi.fn().mockResolvedValue([]),
  markAsSynced: vi.fn().mockResolvedValue(),
  markAsPending: vi.fn().mockResolvedValue(),
  markAsFailed: vi.fn().mockResolvedValue(),
  pushDrafts: vi.fn().mockResolvedValue({ synced: 0, pending: 0, failed: 0, reason: null }),
  retryPendingInspections: vi.fn().mockResolvedValue({ synced: 0, pending: 0, failed: 0 }),
}))

vi.mock('@/services/api', () => ({
  default: { post: vi.fn().mockResolvedValue({ data: {} }) },
}))

import { useOfflineSync } from '@/composables/useOfflineSync'
import { useSyncStore } from '@/stores/syncStore'
import {
  getPendingInspections,
  getFailedInspections,
  getPendingRetryInspections,
  retryPendingInspections,
  pushDrafts,
} from '@/db/inspectionDraft'

describe('useOfflineSync', () => {
  let addSpy, removeSpy

  beforeEach(() => {
    vi.clearAllMocks()
    addSpy = vi.spyOn(window, 'addEventListener')
    removeSpy = vi.spyOn(window, 'removeEventListener')
    getPendingInspections.mockResolvedValue([])
    getFailedInspections.mockResolvedValue([])
    navigator.onLine = true
    const store = useSyncStore()
    store.resetAll()
  })

  afterEach(() => {
    addSpy.mockRestore()
    removeSpy.mockRestore()
    navigator.onLine = true
  })

  // ── Event listener lifecycle ───────────────────────────────────────
  it('registers online and offline listeners on mount', () => {
    mount(defineComponent({
      setup() { useOfflineSync(); return {} },
      render: () => h('div'),
    }))
    expect(addSpy).toHaveBeenCalledWith('online', expect.any(Function))
    expect(addSpy).toHaveBeenCalledWith('offline', expect.any(Function))
  })

  it('removes listeners on cleanup', () => {
    const { cleanup } = useOfflineSync()
    cleanup()
    expect(removeSpy).toHaveBeenCalledWith('online', expect.any(Function))
    expect(removeSpy).toHaveBeenCalledWith('offline', expect.any(Function))
  })

  // ── refreshDraftCount ──────────────────────────────────────────────
  it('refreshDraftCount sets draftCount from getPendingInspections', async () => {
    getPendingInspections.mockResolvedValueOnce([{ id: 1 }, { id: 2 }])
    const { refreshDraftCount } = useOfflineSync()
    const store = useSyncStore()
    await refreshDraftCount()
    expect(store.draftCount).toBe(2)
  })

  it('refreshDraftCount sets draftCount to 0 when empty', async () => {
    getPendingInspections.mockResolvedValueOnce([])
    const { refreshDraftCount } = useOfflineSync()
    const store = useSyncStore()
    await refreshDraftCount()
    expect(store.draftCount).toBe(0)
  })

  // ── refreshSyncState ───────────────────────────────────────────────
  it('refreshSyncState updates both draftCount and failedCount', async () => {
    getPendingInspections.mockResolvedValueOnce([{ id: 1 }])
    getFailedInspections.mockResolvedValueOnce([{ id: 2 }])
    const { refreshSyncState } = useOfflineSync()
    const store = useSyncStore()
    await refreshSyncState()
    expect(store.draftCount).toBe(1)
    expect(store.failedCount).toBe(1)
  })

  // ── sync() — pushDrafts success ───────────────────────────────────
  it('sync calls pushDrafts and sets lastSyncAt on success', async () => {
    pushDrafts.mockResolvedValueOnce({ synced: 1, pending: 0, failed: 0, reason: null })
    retryPendingInspections.mockResolvedValueOnce({ synced: 0, pending: 0, failed: 0 })
    getPendingRetryInspections.mockResolvedValueOnce([])
    const { sync } = useOfflineSync()
    const store = useSyncStore()
    store.setOnline(true)
    await sync()
    expect(pushDrafts).toHaveBeenCalled()
    expect(store.lastSyncAt).not.toBeNull()
  })

  it('sync does nothing when offline', async () => {
    const { sync } = useOfflineSync()
    const store = useSyncStore()
    store.setOnline(false)
    await sync()
    expect(pushDrafts).not.toHaveBeenCalled()
  })

  it('sync does nothing when already syncing', async () => {
    const { sync } = useOfflineSync()
    const store = useSyncStore()
    store.setOnline(true)
    store.setSyncing(true)
    await sync()
    expect(pushDrafts).not.toHaveBeenCalled()
    store.setSyncing(false)
  })

  // ── sync() — token_expired ────────────────────────────────────────
  it('sync sets isExhausted and syncError on 401', async () => {
    pushDrafts.mockResolvedValueOnce({ synced: 0, pending: 0, failed: 1, reason: 'token_expired' })
    const { sync } = useOfflineSync()
    const store = useSyncStore()
    store.setOnline(true)
    await sync()
    expect(store.isExhausted).toBe(true)
    expect(store.syncError).toBe('token_expired')
  })

  // ── handleOnlineEvent ──────────────────────────────────────────────
  it('handleOnlineEvent resets syncError and isExhausted', async () => {
    // Mount a component so onMounted registers the event listeners
    mount(defineComponent({
      setup() {
        useOfflineSync()
        return {}
      },
      render: () => h('div'),
    }))

    const store = useSyncStore()
    store.setSyncError('token_expired')
    store.setExhausted(true)

    // Find the registered online handler from addSpy
    const [, onlineHandler] = addSpy.mock.calls.find(([evt]) => evt === 'online') ?? []
    expect(onlineHandler).toBeDefined()

    store.setOnline(false)
    await onlineHandler()
    expect(store.isOnline).toBe(true)
    expect(store.syncError).toBeNull()
    expect(store.isExhausted).toBe(false)
  })

  // ── retryManually ─────────────────────────────────────────────────
  it('retryManually resets state and calls sync when online', async () => {
    const store = useSyncStore()
    store.setSyncError('sync_failed')
    store.setExhausted(true)
    pushDrafts.mockResolvedValueOnce({ synced: 0, pending: 0, failed: 0, reason: null })
    retryPendingInspections.mockResolvedValueOnce({ synced: 0, pending: 0, failed: 0 })
    getPendingRetryInspections.mockResolvedValueOnce([])

    const { retryManually } = useOfflineSync()
    store.setOnline(true)
    await retryManually()
    expect(store.syncError).toBeNull()
    expect(store.isExhausted).toBe(false)
  })

  it('retryManually sets syncError=offline when offline', async () => {
    const { retryManually } = useOfflineSync()
    const store = useSyncStore()
    store.setOnline(false)
    await retryManually()
    expect(store.syncError).toBe('offline')
  })
})
