import { ref } from 'vue'
import { useI18n } from 'vue-i18n'
import reportService, { triggerDownload } from '@/services/reportService'

/**
 * Keyed download state for individual inspection reports.
 * Only the button with the matching inspection ID is disabled.
 *
 * Usage:
 *   const dl = useInspectorReportDownload({ onSuccess, onError })
 *   :disabled="dl.isDownloading(insp.id)"
 *   @click="dl.downloadReport(insp.id, insp.cabinet_code)"
 */
export function useInspectorReportDownload({ onSuccess, onError } = {}) {
  const { t } = useI18n()

  const activeDownloads = ref(new Set())

  function startDownload(id) {
    activeDownloads.value = new Set([...activeDownloads.value, id])
  }

  function finishDownload(id) {
    const next = new Set(activeDownloads.value)
    next.delete(id)
    activeDownloads.value = next
  }

  function isDownloading(id) {
    return activeDownloads.value.has(id)
  }

  function showSuccess(msg) {
    if (onSuccess) onSuccess(msg)
  }

  function showError(msg) {
    if (onError) onError(msg)
  }

  async function downloadReport(inspectionId, cabinetCode, lang = 'en') {
    if (!inspectionId) return
    startDownload(inspectionId)
    try {
      const { data } = await reportService.downloadInspectionReport(inspectionId, lang)
      const filename = cabinetCode
        ? `bien-ban-${cabinetCode}.pdf`
        : `inspection-report-${inspectionId}.pdf`
      triggerDownload(data, filename)
      showSuccess(t('reports.downloadSuccess'))
    } catch (e) {
      showError(t('reports.downloadError'))
      console.error('[useInspectorReportDownload] failed:', e)
    } finally {
      finishDownload(inspectionId)
    }
  }

  return {
    activeDownloads,
    isDownloading,
    downloadReport,
  }
}
