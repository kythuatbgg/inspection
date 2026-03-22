import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import reportService, { triggerDownload } from '@/services/reportService'

/**
 * Handles all export/download actions for the Reports admin page.
 * Uses keyed download state so only the active button is disabled.
 *
 * @param {Object} options
 * @param {Object} options.dl - Shared useDownloadState instance from parent shell.
 *                             If not provided, creates its own (for isolated use).
 * @param {Function} options.onError - Error callback (receives message string).
 */
export function useAdminReportsExport({ dl = null, onError = null } = {}) {
  const { t } = useI18n()

  // Accept dl from parent shell so all tabs share the same keyed state.
  // Falls back to creating its own if not supplied.
  const _dl = dl

  function showToast(msg, type = 'success') {
    if (typeof window !== 'undefined' && window.__adminReportsToast) {
      window.__adminReportsToast(msg, type)
    } else if (onError && type === 'error') {
      onError(msg)
    }
  }

  // Export tab state
  const exportBatchId = ref('')
  const exportErrorBatchId = ref('')

  // --- Batch PDF actions (used in Reports tab) ---

  async function downloadInspectionReport(inspectionId, lang = 'en', cabinetCode = '') {
    const key = `inspection:${inspectionId}`
    _dl.startDownload(key)
    try {
      const { data } = await reportService.downloadInspectionReport(inspectionId, lang)
      const filename = cabinetCode ? `inspection-${cabinetCode}.pdf` : `inspection-report-${inspectionId}.pdf`
      triggerDownload(data, filename)
      showToast(t('reports.downloadSuccess'))
    } catch {
      showToast(t('reports.downloadError'), 'error')
    } finally {
      _dl.finishDownload(key)
    }
  }

  async function downloadBatchSummary(batchId) {
    const key = `batch-summary:${batchId}`
    _dl.startDownload(key)
    try {
      const { data } = await reportService.downloadBatchSummary(batchId)
      triggerDownload(data, `tong-ket-dot-${batchId}.pdf`)
      showToast(t('reports.downloadSuccess'))
    } catch {
      showToast(t('reports.downloadError'), 'error')
    } finally {
      _dl.finishDownload(key)
    }
  }

  async function downloadAcceptance(batchId) {
    const key = `acceptance:${batchId}`
    _dl.startDownload(key)
    try {
      const { data } = await reportService.downloadAcceptance(batchId)
      triggerDownload(data, `bien-ban-nghiem-thu-${batchId}.pdf`)
      showToast(t('reports.downloadSuccess'))
    } catch {
      showToast(t('reports.downloadError'), 'error')
    } finally {
      _dl.finishDownload(key)
    }
  }

  async function downloadCriticalErrors(batchId) {
    const key = `critical-pdf:${batchId}`
    _dl.startDownload(key)
    try {
      const { data } = await reportService.downloadCriticalErrors({ batch_id: batchId })
      triggerDownload(data, 'bao-cao-loi-critical.pdf')
      showToast(t('reports.downloadSuccess'))
    } catch {
      showToast(t('reports.downloadError'), 'error')
    } finally {
      _dl.finishDownload(key)
    }
  }

  // --- Excel export actions (used in Export tab) ---

  async function downloadBatchExcel(batchId) {
    const key = `batch-excel:${batchId}`
    _dl.startDownload(key)
    try {
      const { data } = await reportService.downloadBatchExport(batchId)
      triggerDownload(data, `thong-ke-dot-${batchId}.xlsx`)
      showToast(t('reports.downloadSuccess'))
    } catch {
      showToast(t('reports.downloadError'), 'error')
    } finally {
      _dl.finishDownload(key)
    }
  }

  async function downloadStatsExcel(params = {}) {
    const key = 'stats-excel'
    _dl.startDownload(key)
    try {
      const { data } = await reportService.downloadStatisticsExport(params)
      triggerDownload(data, 'thong-ke-tong-hop.xlsx')
      showToast(t('reports.downloadSuccess'))
    } catch {
      showToast(t('reports.downloadError'), 'error')
    } finally {
      _dl.finishDownload(key)
    }
  }

  async function downloadCriticalErrorsExcel(batchId = '') {
    const key = `critical-excel:${batchId || 'all'}`
    _dl.startDownload(key)
    try {
      const params = { format: 'xlsx' }
      if (batchId) params.batch_id = batchId
      const { data } = await reportService.downloadCriticalErrors(params)
      triggerDownload(data, 'loi-critical.xlsx')
      showToast(t('reports.downloadSuccess'))
    } catch {
      showToast(t('reports.downloadError'), 'error')
    } finally {
      _dl.finishDownload(key)
    }
  }

  return {
    dl: _dl,
    exportBatchId,
    exportErrorBatchId,
    downloadInspectionReport,
    downloadBatchSummary,
    downloadAcceptance,
    downloadCriticalErrors,
    downloadBatchExcel,
    downloadStatsExcel,
    downloadCriticalErrorsExcel,
  }
}
