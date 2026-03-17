import { defineStore } from 'pinia'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const useInspectionsStore = defineStore('inspections', {
  state: () => ({
    currentInspection: null,
    loading: false
  }),

  actions: {
    async createInspection(data) {
      this.loading = true
      try {
        const response = await axios.post(`${API_URL}/inspections`, data)
        this.currentInspection = response.data.data
        return this.currentInspection
      } finally {
        this.loading = false
      }
    },

    async getInspectionForPlan(planId) {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/plans/${planId}/inspection`)
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
        const response = await axios.get(`${API_URL}/inspections/${inspectionId}/details`)
        return response.data.data
      } catch (e) {
        return []
      }
    }
  }
})
