import { ref, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import reportService from '@/services/reportService'
import api from '@/services/api'

/**
 * Handles Reports tab search state and API calls.
 * Includes debounce, query deduplication, and request cancellation.
 */
export function useAdminReportsSearch({ onError } = {}) {
  const { t } = useI18n()

  // State
  const batches = ref([])
  const selectedBatchId = ref('')
  const searchCabinet = ref('')
  const reportLang = ref('en')
  const searchResults = ref([])
  const loadingSearch = ref(false)

  // Internal
  let searchTimer = null
  let abortController = null
  let lastQueryKey = ''

  // Constants
  const langOptions = [
    { value: 'en', label: '🇬🇧 English' },
    { value: 'vn', label: '🇻🇳 Tiếng Việt' },
    { value: 'kh', label: '🇰🇭 ភាសាខ្មែរ' },
  ]

  // Deduplication: build a stable query key
  const queryKey = computed(() =>
    `${selectedBatchId.value}::${searchCabinet.value.trim()}`
  )

  // Load batch list
  async function loadBatches() {
    try {
      const { data } = await api.get('/batches')
      batches.value = data.data || data
    } catch {
      onError?.(t('common.errorLoadData'))
    }
  }

  // Core search — cancellable
  async function searchReports() {
    const key = queryKey.value

    // Skip if query unchanged
    if (key === lastQueryKey && searchResults.value.length > 0) return
    lastQueryKey = key

    // Cancel any in-flight request
    if (abortController) {
      abortController.abort()
    }
    abortController = new AbortController()

    loadingSearch.value = true
    try {
      const params = {}
      if (selectedBatchId.value) params.batch_id = selectedBatchId.value
      if (searchCabinet.value.trim()) params.cabinet_code = searchCabinet.value.trim()

      const { data } = await reportService.searchInspections(params, {
        signal: abortController.signal,
      })
      searchResults.value = data.data || []
    } catch (err) {
      if (err.name !== 'CanceledError' && err.code !== 'ERR_CANCELED') {
        onError?.(t('common.errorLoadData'))
      }
    } finally {
      loadingSearch.value = false
    }
  }

  // Debounced wrapper — clears previous timer
  function debouncedSearch() {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(() => searchReports(), 300)
  }

  // Cleanup on caller unmount
  function cleanup() {
    clearTimeout(searchTimer)
    abortController?.abort()
  }

  return {
    batches,
    selectedBatchId,
    searchCabinet,
    reportLang,
    langOptions,
    searchResults,
    loadingSearch,
    loadBatches,
    searchReports,
    debouncedSearch,
    cleanup,
  }
}
