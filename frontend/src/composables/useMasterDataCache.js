import { ref } from 'vue'
import { syncCabinets, syncChecklists, syncBatches } from '@/db/inspectionDraft'

/**
 * Composable cache master data (cabinets, checklists, batches)
 * khi user đăng nhập thành công — để offline vẫn truy cập được.
 */
export function useMasterDataCache() {
  const isCaching = ref(false)
  const cacheError = ref(null)

  /**
   * Fetch + cache all master data in parallel.
   * Fails gracefully — each table handles its own errors.
   *
   * @param {number|null} userId — filter batches by user_id (optional)
   */
  async function cacheMasterData(userId = null) {
    if (isCaching.value) return
    isCaching.value = true
    cacheError.value = null

    const results = await Promise.allSettled([
      syncCabinets(),
      syncChecklists(),
      syncBatches(userId),
    ])

    const failed = results.filter(r => r.status === 'rejected')
    if (failed.length) {
      cacheError.value = failed[0].reason?.message ?? 'Some caches failed'
    }

    isCaching.value = false
  }

  return { isCaching, cacheError, cacheMasterData }
}
