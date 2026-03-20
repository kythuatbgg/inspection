import api from './api.js'

export const cabinetService = {
  async getCabinets(params = {}) {
    const response = await api.get('/cabinets', { params })
    return response.data
  },

  async getCabinetById(id) {
    const response = await api.get(`/cabinets/${id}`)
    return response.data.data || response.data
  },

  async createCabinet(data) {
    const response = await api.post('/cabinets', data)
    return response.data.data || response.data
  },

  async updateCabinet(id, data) {
    const response = await api.put(`/cabinets/${id}`, data)
    return response.data.data || response.data
  },

  async deleteCabinet(id) {
    const response = await api.delete(`/cabinets/${id}`)
    return response.data
  },

  async getCabinetsMap() {
    const response = await api.get('/cabinets/map')
    return response.data.data || response.data
  },

  async importCabinets(fileData) {
    const response = await api.post('/cabinets/import', fileData, {
      headers: { 'Content-Type': 'multipart/form-data' }
    })
    return response.data
  },

  async exportCabinets() {
    const response = await api.get('/cabinets/export', {
      responseType: 'blob'
    })
    return response.data
  },

  async downloadTemplate() {
    const response = await api.get('/cabinets/template', {
      responseType: 'blob'
    })
    return response.data
  },

  async exportResult(token) {
    const response = await api.get('/cabinets/export-result', {
      params: { token },
      responseType: 'blob'
    })
    return response.data
  }
}

export default cabinetService
