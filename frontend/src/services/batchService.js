import api from './api.js'

export const batchService = {
  /**
   * Get all batches
   * @param {object} params - Query parameters
   * @returns {Promise}
   */
  async getBatches(params = {}) {
    const response = await api.get('/batches', { params })
    // Return full response.data to preserve pagination meta
    return response.data
  },

  /**
   * Get batch by ID
   * @param {number} id - Batch ID
   * @returns {Promise}
   */
  async getBatchById(id) {
    const response = await api.get(`/batches/${id}`)
    return response.data.data || response.data
  },

  /**
   * Create new batch
   * @param {object} data - Batch data
   * @returns {Promise}
   */
  async createBatch(data) {
    const response = await api.post('/batches', data)
    return response.data.data || response.data
  },

  /**
   * Update batch
   * @param {number} id - Batch ID
   * @param {object} data - Batch data
   * @returns {Promise}
   */
  async updateBatch(id, data) {
    const response = await api.put(`/batches/${id}`, data)
    return response.data.data || response.data
  },

  /**
   * Delete batch
   * @param {number} id - Batch ID
   * @returns {Promise}
   */
  async deleteBatch(id) {
    const response = await api.delete(`/batches/${id}`)
    return response.data
  },

  /**
   * Get plans in a batch
   * @param {number} batchId - Batch ID
   * @returns {Promise}
   */
  async getBatchPlans(batchId) {
    const response = await api.get(`/batches/${batchId}/plans`)
    return response.data.data || response.data
  },

  async getBatchResults(batchId) {
    const response = await api.get(`/batches/${batchId}/results`)
    return response.data
  },

  async reviewPlan(planId, data) {
    const response = await api.patch(`/plans/${planId}/review`, data)
    return response.data
  },

  async closeBatch(batchId) {
    const response = await api.patch(`/batches/${batchId}/close`)
    return response.data
  },

  async deleteBatch(id, force = false) {
    const response = await api.delete(`/batches/${id}`, { params: force ? { force: true } : {} })
    return response.data
  },

  async reopenBatch(batchId) {
    const response = await api.patch(`/batches/${batchId}/reopen`)
    return response.data
  },

  async addCabinetsToBatch(batchId, cabinetCodes) {
    const response = await api.post(`/batches/${batchId}/cabinets`, { cabinet_codes: cabinetCodes })
    return response.data
  },

  async removeCabinetFromBatch(batchId, planId, force = false) {
    const response = await api.delete(`/batches/${batchId}/plans/${planId}`, { params: force ? { force: true } : {} })
    return response.data
  },

  async swapCabinet(batchId, planId, newCabinetCode, force = false) {
    const response = await api.patch(`/batches/${batchId}/plans/${planId}/swap`, {
      new_cabinet_code: newCabinetCode,
      force: force || undefined,
    })
    return response.data
  }
}

export default batchService
