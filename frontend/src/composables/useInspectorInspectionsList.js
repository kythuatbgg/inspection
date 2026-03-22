import { ref, computed, watch } from 'vue'
import { useI18n } from 'vue-i18n'
import inspectorStatsService from '@/services/inspectorStatsService'

/**
 * Handles inspections list: search, filter, pagination.
 * Safe against race conditions — only the latest request commits results.
 *
 * Usage:
 *   const list = useInspectorInspectionsList({ dateParams, onError })
 *   list.fetchInspections()     // manual trigger
 *   list.resetFilters()          // clear search/filter/page
 *   list.goToPage(2)             // pagination
 */
export function useInspectorInspectionsList({ dateParams = null, onError = null } = {}) {
  const { t } = useI18n()

  // ── Filter state ───────────────────────────────────────────────
  const searchQuery = ref('')
  const filterResult = ref('') // '' | 'PASS' | 'FAIL'
  const filterBatch = ref('')   // batch_id — empty if backend doesn't support it

  // ── Pagination state ─────────────────────────────────────────
  const page = ref(1)
  const perPage = ref(20)
  const total = ref(0)
  const lastPage = ref(1)
  const hasPagination = ref(false) // true once backend confirms pagination support

  // ── Data / status state ───────────────────────────────────────
  const inspections = ref([])
  const isLoadingInspections = ref(false)
  const inspectionsError = ref(null)

  // ── Request guard: prevents stale responses from overwriting fresh ones ──
  let abortController = null
  let searchTimer = null
  let requestCounter = 0 // increment on each trigger; discard if not latest

  // Build query params from current state
  // Always include pagination params — backend will ignore if it doesn't support them
  const queryParams = computed(() => {
    const p = {}

    // Date filters from parent
    if (dateParams?.value) {
      if (dateParams.value.from) p.from = dateParams.value.from
      if (dateParams.value.to)   p.to   = dateParams.value.to
    }

    // Local filters
    if (searchQuery.value.trim()) p.search = searchQuery.value.trim()
    if (filterResult.value)       p.result = filterResult.value
    if (filterBatch.value)        p.batch_id = filterBatch.value

    // Pagination — always send, backend ignores if unsupported
    p.page = page.value
    p.per_page = perPage.value

    return p
  })

  // Stable key for deduplication (shallow comparison)
  const lastQueryKey = ref('')

  // ── Core fetch ─────────────────────────────────────────────────
  async function fetchInspections() {
    const params = queryParams.value
    const key = JSON.stringify(params)

    // Skip if query unchanged
    if (key === lastQueryKey.value && inspections.value.length > 0) return
    lastQueryKey.value = key

    // Increment counter so only this call can commit
    const reqId = ++requestCounter

    // Cancel any in-flight request
    if (abortController) {
      abortController.abort()
    }
    abortController = new AbortController()

    isLoadingInspections.value = true
    inspectionsError.value = null

    try {
      const res = await inspectorStatsService.getInspections(params, {
        signal: abortController.signal,
      })

      // Discard if a newer request has already been triggered
      if (reqId !== requestCounter) return

      const payload = res.data

      // Shape 1: { data: [...], meta: { total, last_page, ... } }
      if (payload?.meta && Array.isArray(payload.data)) {
        hasPagination.value = true
        inspections.value = payload.data
        total.value = payload.meta.total ?? payload.data.length
        lastPage.value = payload.meta.last_page ?? 1
      }
      // Shape 2: { data: { data: [...], meta: {...} } }
      else if (payload?.data?.meta && Array.isArray(payload?.data?.data)) {
        hasPagination.value = true
        inspections.value = payload.data.data
        total.value = payload.data.meta.total ?? payload.data.data.length
        lastPage.value = payload.data.meta.last_page ?? 1
      }
      // Shape 3: flat array { data: [...] }
      else if (Array.isArray(payload?.data)) {
        inspections.value = payload.data
        total.value = payload.data.length
        lastPage.value = 1
        hasPagination.value = false
      }
      // Shape 4: flat array at root [...]
      else if (Array.isArray(payload)) {
        inspections.value = payload
        total.value = payload.length
        lastPage.value = 1
        hasPagination.value = false
      }
      // Fallback: empty
      else {
        inspections.value = []
        total.value = 0
        lastPage.value = 1
        hasPagination.value = false
      }
    } catch (e) {
      if (e.name !== 'CanceledError' && e.code !== 'ERR_CANCELED') {
        inspectionsError.value = t('common.errorLoadData')
        onError?.(t('common.errorLoadData'))
        console.error('[useInspectorInspectionsList] fetch failed:', e)
      }
    } finally {
      if (reqId === requestCounter) {
        isLoadingInspections.value = false
      }
    }
  }

  // ── Debounced search ──────────────────────────────────────────
  function debouncedFetch() {
    clearTimeout(searchTimer)
    // Reset to page 1 whenever search changes
    page.value = 1
    searchTimer = setTimeout(fetchInspections, 400)
  }

  // Watch filterResult changes
  watch(filterResult, () => {
    page.value = 1
    fetchInspections()
  })

  // Watch filterBatch changes
  watch(filterBatch, () => {
    page.value = 1
    fetchInspections()
  })

  // ── Pagination ────────────────────────────────────────────────
  function goToPage(p) {
    if (p < 1 || p > lastPage.value) return
    page.value = p
    fetchInspections()
    // Scroll to top of list
    window.scrollTo({ top: 0, behavior: 'smooth' })
  }

  // ── Reset ─────────────────────────────────────────────────────
  function resetFilters() {
    searchQuery.value = ''
    filterResult.value = ''
    filterBatch.value = ''
    page.value = 1
    lastQueryKey.value = ''
    fetchInspections()
  }

  // ── Cleanup ───────────────────────────────────────────────────
  function cleanup() {
    clearTimeout(searchTimer)
    abortController?.abort()
  }

  // ── Computed helpers ──────────────────────────────────────────
  const emptyStateReason = computed(() => {
    if (inspectionsError.value) return 'error'
    if (searchQuery.value || filterResult.value || filterBatch.value) return 'filtered'
    return 'empty'
  })

  return {
    // State
    inspections,
    searchQuery,
    filterResult,
    filterBatch,
    page,
    perPage,
    total,
    lastPage,
    hasPagination,
    isLoadingInspections,
    inspectionsError,
    emptyStateReason,
    // Actions
    fetchInspections,
    debouncedFetch,
    goToPage,
    resetFilters,
    cleanup,
  }
}
