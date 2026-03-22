import { defineStore } from 'pinia'
import { ref } from 'vue'

export const useSyncStore = defineStore('sync', () => {
  const isOnline = ref(navigator.onLine)
  const isSyncing = ref(false)
  const draftCount = ref(0)
  const lastSyncAt = ref(null)

  function setOnline(val) { isOnline.value = val }
  function setSyncing(val) { isSyncing.value = val }
  function setDraftCount(val) { draftCount.value = val }
  function setLastSyncAt(val) { lastSyncAt.value = val }
  function resetAll() {
    isOnline.value = navigator.onLine
    isSyncing.value = false
    draftCount.value = 0
    lastSyncAt.value = null
  }

  return {
    isOnline,
    isSyncing,
    draftCount,
    lastSyncAt,
    setOnline,
    setSyncing,
    setDraftCount,
    setLastSyncAt,
    resetAll,
  }
})
