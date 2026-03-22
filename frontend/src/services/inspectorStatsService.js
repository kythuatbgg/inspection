import api from './api'

export default {
  getOverview(params = {}, config = {}) {
    return api.get('/inspector/stats/overview', { params, ...config })
  },

  getTimeline(params = {}, config = {}) {
    return api.get('/inspector/stats/timeline', { params, ...config })
  },

  getTopErrors(params = {}, config = {}) {
    return api.get('/inspector/stats/top-errors', { params, ...config })
  },

  // Supports pagination via { page, per_page } in params
  // Supports AbortController signal via config.signal
  getInspections(params = {}, config = {}) {
    return api.get('/inspector/stats/inspections', { params, ...config })
  },
}
