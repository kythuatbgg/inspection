import { defineStore } from 'pinia'
import api from '../services/api'

export const useInspectionsStore = defineStore('inspections', {
  state: () => ({
    currentInspection: null,
    loading: false
  }),

  actions: {
    async createInspection(data) {
      this.loading = true
      try {
        const response = await api.post('/inspections', data)
        this.currentInspection = response.data.data
        return this.currentInspection
      } finally {
        this.loading = false
      }
    },

    async getInspectionForPlan(planId) {
      this.loading = true
      try {
        const response = await api.get(`/plans/${planId}/inspection`)
        this.currentInspection = response.data.data
        return this.currentInspection
      } catch (e) {
        return null
      } finally {
        this.loading = false
      }
    },

    async fetchInspectionDetails(inspectionId) {
      try {
        const response = await api.get(`/inspections/${inspectionId}/details`)
        return response.data.data
      } catch (e) {
        return []
      }
    }
  }
})
