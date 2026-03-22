import { defineStore } from 'pinia'
import api from '../services/api'

export const useChecklistsStore = defineStore('checklists', {
  state: () => ({
    checklists: [],
    currentChecklist: null,
    loading: false
  }),

  getters: {
    getChecklistById: (state) => (id) => state.checklists.find(c => c.id === id)
  },

  actions: {
    async fetchChecklists() {
      this.loading = true
      try {
        const response = await api.get('/checklists')
        this.checklists = response.data.data
      } finally {
        this.loading = false
      }
    },

    async fetchChecklist(id) {
      this.loading = true
      try {
        const response = await api.get(`/checklists/${id}`)
        this.currentChecklist = response.data.data
        return this.currentChecklist
      } finally {
        this.loading = false
      }
    },

    async fetchChecklistItems(checklistId) {
      try {
        const response = await api.get(`/checklists/${checklistId}/items`)
        return response.data.data
      } catch (error) {
        console.error('Failed to fetch checklist items:', error)
        return []
      }
    }
  }
})
