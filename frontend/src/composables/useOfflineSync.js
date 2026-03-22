import { onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useSyncStore } from '@/stores/syncStore'
import { pushDrafts, getPendingInspections } from '@/db/inspectionDraft'

/**
 * Composable kết nối online/offline detection, sync queue,
 * và auto-push khi có mạng trở lại.
 *
 * Dùng syncStore để shared state giữa các components.
 */
export function useOfflineSync() {
  const syncStore = useSyncStore()
  const { isOnline, isSyncing, draftCount } = storeToRefs(syncStore)

  // Refresh draft count từ Dexie
  async function refreshDraftCount() {
    const pending = await getPendingInspections()
    syncStore.setDraftCount(pending.length)
  }

  // Push all pending drafts lên backend
  async function sync() {
    if (!isOnline.value || isSyncing.value) return

    syncStore.setSyncing(true)
    try {
      await pushDrafts()
      syncStore.setLastSyncAt(new Date().toISOString())
      await refreshDraftCount()
    } finally {
      syncStore.setSyncing(false)
    }
  }

  // ── Pure state update functions (testable without component context) ──
  function setOnlineStatus(val) {
    syncStore.setOnline(val)
  }

  function handleOnlineEvent() {
    syncStore.setOnline(true)
    sync()
  }

  function handleOfflineEvent() {
    syncStore.setOnline(false)
  }

  // ── Lifecycle — only runs inside a component ──
  onMounted(() => {
    window.addEventListener('online', handleOnlineEvent)
    window.addEventListener('offline', handleOfflineEvent)
    syncStore.setOnline(navigator.onLine)
    refreshDraftCount()
    if (navigator.onLine) sync()
  })

  function cleanup() {
    window.removeEventListener('online', handleOnlineEvent)
    window.removeEventListener('offline', handleOfflineEvent)
  }

  onUnmounted(cleanup)

  return {
    isOnline,
    isSyncing,
    draftCount,
    sync,
    refreshDraftCount,
    setOnlineStatus,
    cleanup,
  }
}
