import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import reportService from '@/services/reportService'

/**
 * Handles Statistics tab state and API calls.
 * Supports lazy loading via loadStatsIfNeeded().
 */
export function useAdminReportsStats({ onError } = {}) {
  const { t } = useI18n()

  // State
  const stats = ref({})
  const btsData = ref([])
  const inspectorData = ref([])
  const errorData = ref([])
  const filterFrom = ref('')
  const filterTo = ref('')
  const loadingOverview = ref(false)
  const loadingBts = ref(false)
  const loadingInspector = ref(false)
  const loadingErrors = ref(false)

  // Tracks whether stats have ever been loaded — for lazy loading
  let hasLoadedStats = false

  // Build request params from filters
  function buildParams() {
    const params = {}
    if (filterFrom.value) params.from = filterFrom.value
    if (filterTo.value) params.to = filterTo.value
    return params
  }

  // Load all stats — call this directly or via loadStatsIfNeeded()
  async function loadStats() {
    const params = buildParams()

    loadingOverview.value = true
    loadingBts.value = true
    loadingInspector.value = true
    loadingErrors.value = true

    try {
      const [overview, bts, inspector, errors] = await Promise.all([
        reportService.getOverview(params),
        reportService.getByBts(params),
        reportService.getByInspector(params),
        reportService.getByErrorType(params),
      ])
      stats.value = overview.data.data || {}
      btsData.value = bts.data.data || []
      inspectorData.value = inspector.data.data || []
      errorData.value = errors.data.data || []
    } catch {
      onError?.(t('common.errorLoadData'))
    } finally {
      loadingOverview.value = false
      loadingBts.value = false
      loadingInspector.value = false
      loadingErrors.value = false
    }
  }

  // Load stats only the first time — for lazy tab activation
  async function loadStatsIfNeeded() {
    if (!hasLoadedStats) {
      hasLoadedStats = true
      await loadStats()
    }
  }

  return {
    stats,
    btsData,
    inspectorData,
    errorData,
    filterFrom,
    filterTo,
    loadingOverview,
    loadingBts,
    loadingInspector,
    loadingErrors,
    loadStats,
    loadStatsIfNeeded,
  }
}
