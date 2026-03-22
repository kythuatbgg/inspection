import { defineStore } from 'pinia'
import api from '../services/api'
import { db } from '../db'

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
      } catch {
        // Offline: fallback to Dexie cache
        if (!navigator.onLine) {
          this.checklists = await db.checklists.toArray()
        }
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
      } catch {
        // Offline: fallback to Dexie cache
        if (!navigator.onLine) {
          this.currentChecklist = await db.checklists.get(Number(id))
          return this.currentChecklist
        }
        return null
      } finally {
        this.loading = false
      }
    },

    async fetchChecklistItems(checklistId) {
      try {
        const response = await api.get(`/checklists/${checklistId}/items`)
        return response.data.data
      } catch {
        // Offline: fallback to Dexie cache
        if (!navigator.onLine) {
          return db.checklistItems.where('checklist_id').equals(Number(checklistId)).toArray()
        }
        return []
      }
    }
  }
})
