import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSyncStore = defineStore('sync', () => {
  const isOnline = ref(navigator.onLine)
  const isSyncing = ref(false)
  const draftCount = ref(0)       // inspections in 'draft' status
  const failedCount = ref(0)      // inspections in 'failed' status (exhausted)
  const lastSyncAt = ref(null)
  const syncError = ref(null)     // null | 'token_expired' | 'sync_failed' | 'offline'
  const isExhausted = ref(false)  // true when retry attempts are exhausted

  function setOnline(val) { isOnline.value = val }
  function setSyncing(val) { isSyncing.value = val }
  function setDraftCount(val) { draftCount.value = val }
  function setFailedCount(val) { failedCount.value = val }
  function setLastSyncAt(val) { lastSyncAt.value = val }
  function setSyncError(msg) { syncError.value = msg }
  function setExhausted(val) { isExhausted.value = val }

  function resetSyncState() {
    isOnline.value = navigator.onLine
    isSyncing.value = false
    draftCount.value = 0
    failedCount.value = 0
    lastSyncAt.value = null
    syncError.value = null
    isExhausted.value = false
  }

  function resetAll() {
    resetSyncState()
  }

  return {
    isOnline,
    isSyncing,
    draftCount,
    failedCount,
    lastSyncAt,
    syncError,
    isExhausted,
    setOnline,
    setSyncing,
    setDraftCount,
    setFailedCount,
    setLastSyncAt,
    setSyncError,
    setExhausted,
    resetSyncState,
    resetAll,
  }
})
