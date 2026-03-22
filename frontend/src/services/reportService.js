import api from './api'

export default {
  // Reports (PDF/Excel download)
  downloadInspectionReport(inspectionId) {
    return api.get(`/reports/inspection/${inspectionId}`, { responseType: 'blob' })
  },

  downloadBatchSummary(batchId) {
    return api.get(`/reports/batch/${batchId}/summary`, { responseType: 'blob' })
  },

  downloadBatchExport(batchId) {
    return api.get(`/reports/batch/${batchId}/export`, { responseType: 'blob' })
  },

  downloadCriticalErrors(params = {}) {
    return api.get('/reports/critical-errors', { params, responseType: 'blob' })
  },

  downloadAcceptance(batchId) {
    return api.get(`/reports/acceptance/${batchId}`, { responseType: 'blob' })
  },

  // Statistics (JSON)
  getOverview(params = {}) {
    return api.get('/statistics/overview', { params })
  },

  getByBts(params = {}) {
    return api.get('/statistics/by-bts', { params })
  },

  getByInspector(params = {}) {
    return api.get('/statistics/by-inspector', { params })
  },

  getByErrorType(params = {}) {
    return api.get('/statistics/by-error-type', { params })
  },

  downloadStatisticsExport(params = {}) {
    return api.get('/statistics/export', { params, responseType: 'blob' })
  },
}

export function triggerDownload(blob, filename) {
  const url = window.URL.createObjectURL(blob)
  const link = document.createElement('a')
  link.href = url
  link.download = filename
  document.body.appendChild(link)
  link.click()
  document.body.removeChild(link)
  window.URL.revokeObjectURL(url)
}
