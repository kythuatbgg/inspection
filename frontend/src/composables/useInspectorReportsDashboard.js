import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import inspectorStatsService from '@/services/inspectorStatsService'

const DEFAULT_OVERVIEW = {
  total: 0,
  passed: 0,
  failed: 0,
  pass_rate: 0,
  critical_errors: 0,
  avg_score: 0,
}

/**
 * Handles dashboard data: overview, timeline, top errors.
 * Each block can be loaded independently without re-fetching others.
 *
 * Usage:
 *   const dash = useInspectorReportsDashboard({ onError })
 *   await dash.refreshDashboard(dateParams)
 */
export function useInspectorReportsDashboard({ onError } = {}) {
  const { t } = useI18n()

  // State — always start with stable default shape
  const overview = ref({ ...DEFAULT_OVERVIEW })
  const timeline = ref([])
  const topErrors = ref([])
  const isInitialLoading = ref(true)
  const isRefreshingDashboard = ref(false)
  const dashboardError = ref(null)

  // ── Individual fetchers — reusable, handle their own error state ──

  async function fetchOverview(params = {}, config = {}) {
    try {
      const res = await inspectorStatsService.getOverview(params, config)
      overview.value = { ...DEFAULT_OVERVIEW, ...(res.data?.data || res.data || {}) }
    } catch (e) {
      if (e.name !== 'CanceledError' && e.code !== 'ERR_CANCELED') {
        overview.value = { ...DEFAULT_OVERVIEW }
        throw e
      }
    }
  }

  async function fetchTimeline(params = {}, config = {}) {
    try {
      const res = await inspectorStatsService.getTimeline(params, config)
      timeline.value = res.data?.data || res.data || []
    } catch (e) {
      if (e.name !== 'CanceledError' && e.code !== 'ERR_CANCELED') {
        timeline.value = []
        throw e
      }
    }
  }

  async function fetchTopErrors(params = {}, config = {}) {
    try {
      const res = await inspectorStatsService.getTopErrors(params, config)
      topErrors.value = res.data?.data || res.data || []
    } catch (e) {
      if (e.name !== 'CanceledError' && e.code !== 'ERR_CANCELED') {
        topErrors.value = []
        throw e
      }
    }
  }

  // ── Combined refresh — reuse individual fetchers ──

  /**
   * Full dashboard refresh — used on mount and date filter change.
   * Calls individual fetchers so error handling and mapping stay DRY.
   */
  async function refreshDashboard(params = {}) {
    isInitialLoading.value = true
    isRefreshingDashboard.value = true
    dashboardError.value = null

    try {
      // Run all three in parallel; each resets its own state on error
      await Promise.all([
        fetchOverview(params),
        fetchTimeline(params),
        fetchTopErrors(params),
      ])
    } catch {
      dashboardError.value = t('common.errorLoadData')
      onError?.(t('common.errorLoadData'))
    } finally {
      isInitialLoading.value = false
      isRefreshingDashboard.value = false
    }
  }

  return {
    overview,
    timeline,
    topErrors,
    isInitialLoading,
    isRefreshingDashboard,
    dashboardError,
    fetchOverview,
    fetchTimeline,
    fetchTopErrors,
    refreshDashboard,
  }
}
