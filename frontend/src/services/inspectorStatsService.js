import api from './api'

export default {
  getOverview(params = {}) {
    return api.get('/inspector/stats/overview', { params })
  },

  getTimeline(params = {}) {
    return api.get('/inspector/stats/timeline', { params })
  },

  getTopErrors(params = {}) {
    return api.get('/inspector/stats/top-errors', { params })
  },

  getInspections(params = {}) {
    return api.get('/inspector/stats/inspections', { params })
  },
}
