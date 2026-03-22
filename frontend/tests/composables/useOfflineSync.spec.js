import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest'
import { mount } from '@vue/test-utils'
import { defineComponent, h } from 'vue'

// Mock inspectionDraft
vi.mock('@/db/inspectionDraft', () => ({
  getPendingInspections: vi.fn().mockResolvedValue([]),
  markAsSynced: vi.fn().mockResolvedValue(),
  pushDrafts: vi.fn().mockResolvedValue({ synced: 0 }),
  saveDraft: vi.fn(),
  getDrafts: vi.fn(),
  syncService: {},
}))

// Mock api
vi.mock('@/services/api', () => ({
  default: { post: vi.fn().mockResolvedValue({ data: {} }) },
}))

import { useOfflineSync } from '@/composables/useOfflineSync'
import { useSyncStore } from '@/stores/syncStore'
import { getPendingInspections } from '@/db/inspectionDraft'

describe('useOfflineSync', () => {
  let addSpy, removeSpy

  beforeEach(() => {
    vi.clearAllMocks()
    addSpy = vi.spyOn(window, 'addEventListener')
    removeSpy = vi.spyOn(window, 'removeEventListener')
    getPendingInspections.mockResolvedValue([])
    navigator.onLine = true
    const store = useSyncStore()
    store.resetAll()
  })

  afterEach(() => {
    addSpy.mockRestore()
    removeSpy.mockRestore()
    navigator.onLine = true
  })

  // ── State functions — testable without component context ──────────────
  it('setOnlineStatus updates isOnline to true', () => {
    const { isOnline, setOnlineStatus } = useOfflineSync()
    setOnlineStatus(true)
    expect(isOnline.value).toBe(true)
  })

  it('setOnlineStatus updates isOnline to false', () => {
    const { isOnline, setOnlineStatus } = useOfflineSync()
    setOnlineStatus(false)
    expect(isOnline.value).toBe(false)
  })

  it('draftCount is 0 when no pending inspections', async () => {
    getPendingInspections.mockResolvedValueOnce([])
    const { draftCount, refreshDraftCount } = useOfflineSync()
    await refreshDraftCount()
    expect(draftCount.value).toBe(0)
  })

  it('draftCount reflects pending inspections count', async () => {
    getPendingInspections.mockResolvedValueOnce([{ id: 1 }, { id: 2 }])
    const { draftCount, refreshDraftCount } = useOfflineSync()
    await refreshDraftCount()
    expect(draftCount.value).toBe(2)
  })

  // ── Event listener lifecycle — requires component mount ──────────────
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
})
