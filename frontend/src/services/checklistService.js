import api from './api.js'

export const checklistService = {
  async getChecklists() {
    const response = await api.get('/checklists')
    return response.data
  },

  async getChecklist(id) {
    const response = await api.get(`/checklists/${id}`)
    return response.data.data || response.data
  },

  async createChecklist(data) {
    const response = await api.post('/checklists', data)
    return response.data
  },

  async updateChecklist(id, data) {
    const response = await api.put(`/checklists/${id}`, data)
    return response.data
  },

  async deleteChecklist(id) {
    const response = await api.delete(`/checklists/${id}`)
    return response.data
  },

  async cloneChecklist(id) {
    const response = await api.post(`/checklists/${id}/clone`)
    return response.data
  },

  // Items
  async addItem(checklistId, data) {
    const response = await api.post(`/checklists/${checklistId}/items`, data)
    return response.data
  },

  async updateItem(checklistId, itemId, data) {
    const response = await api.put(`/checklists/${checklistId}/items/${itemId}`, data)
    return response.data
  },

  async deleteItem(checklistId, itemId) {
    const response = await api.delete(`/checklists/${checklistId}/items/${itemId}`)
    return response.data
  },
}

export default checklistService
