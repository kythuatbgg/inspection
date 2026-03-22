import { onMounted, onUnmounted } from 'vue'
import { storeToRefs } from 'pinia'
import { useSyncStore } from '@/stores/syncStore'
import {
  pushDrafts,
  retryPendingInspections,
  getPendingInspections,
  getFailedInspections,
  getPendingRetryInspections,
} from '@/db/inspectionDraft'

const MAX_RETRIES = 5
const BACKOFF_BASE_MS = 5000 // 5s base

/** Compute delay for retry attempt n (exponential backoff capped at 80s) */
function backoffDelay(retryCount) {
  const raw = BACKOFF_BASE_MS * Math.pow(2, retryCount - 1)
  return Math.min(raw, 80_000)
}

/**
 * Composable kết nối online/offline detection, sync queue,
 * và auto-push khi có mạng trở lại.
 *
 * Retry schedule:
 *   draft fail → pending → retry after 5s → fail → retry after 10s → ... → fail x5 → failed (exhausted)
 *
 * Dùng syncStore để shared state giữa các components.
 */
export function useOfflineSync() {
  const syncStore = useSyncStore()
  const { isOnline, isSyncing } = storeToRefs(syncStore)

  // ── Pure state update functions (testable without component context) ──
  async function refreshDraftCount() {
    const pending = await getPendingInspections()
    syncStore.setDraftCount(pending.length)
  }

  async function refreshFailedCount() {
    const failed = await getFailedInspections()
    syncStore.setFailedCount(failed.length)
  }

  async function refreshSyncState() {
    await Promise.all([refreshDraftCount(), refreshFailedCount()])
  }

  /** Main sync — pushes drafts, retries pending with backoff */
  async function sync() {
    if (!isOnline.value || isSyncing.value) return

    syncStore.setSyncing(true)
    syncStore.setSyncError(null)
    syncStore.setExhausted(false)

    try {
      // 1. Try new drafts first
      const draftResult = await pushDrafts()

      if (draftResult.failed > 0) {
        if (draftResult.reason === 'token_expired') {
          syncStore.setSyncError('token_expired')
          syncStore.setExhausted(true)
          await refreshSyncState()
          return
        }
      }

      // 2. Retry any pending inspections with backoff
      await retryWithBackoff()

      syncStore.setLastSyncAt(new Date().toISOString())
    } catch {
      // Unexpected error — nothing to do, let the online event re-trigger
    } finally {
      syncStore.setSyncing(false)
      await refreshSyncState()
    }
  }

  /**
   * Retry pending inspections with exponential backoff.
   * Stops when all are synced, failed, or retry count >= MAX_RETRIES.
   */
  async function retryWithBackoff() {
    // eslint-disable-next-line no-constant-condition
    while (true) {
      const result = await retryPendingInspections(MAX_RETRIES)

      if (result.failed > 0) {
        // At least one inspection exhausted — stop, show error
        syncStore.setSyncError('sync_failed')
        syncStore.setExhausted(true)
        break
      }

      if (result.pending === 0 && result.synced > 0) {
        // All done — nothing more to retry
        break
      }

      if (result.pending === 0 && result.synced === 0) {
        // Nothing left to process
        break
      }

      // Still have pending items — wait for backoff delay then retry
      const pendingWithCount = await getPendingRetryInspections()
      if (!pendingWithCount.length) break

      const retryCount = pendingWithCount[0].sync_retry_count ?? 0
      const delay = backoffDelay(retryCount + 1)

      // Check if still online before waiting
      if (!isOnline.value) break

      await sleep(delay)
    }
  }

  function sleep(ms) {
    return new Promise(resolve => setTimeout(resolve, ms))
  }

  // ── Event handlers ──
  async function handleOnlineEvent() {
    syncStore.setOnline(true)
    syncStore.setSyncError(null) // reset on reconnect — user may have re-logged in
    await refreshSyncState()
    sync()
  }

  function handleOfflineEvent() {
    syncStore.setOnline(false)
  }

  // ── Manual retry — resets exhausted state and retries ──
  async function retryManually() {
    if (!isOnline.value) {
      syncStore.setSyncError('offline')
      return
    }
    syncStore.setSyncError(null)
    syncStore.setExhausted(false)
    await sync()
  }

  // ── Lifecycle ──
  onMounted(() => {
    window.addEventListener('online', handleOnlineEvent)
    window.addEventListener('offline', handleOfflineEvent)
    syncStore.setOnline(navigator.onLine)
    refreshSyncState()
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
    sync,
    retryManually,
    refreshSyncState,
    refreshDraftCount,
    cleanup,
  }
}
