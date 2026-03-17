import { defineStore } from 'pinia'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

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
        const response = await axios.get(`${API_URL}/checklists`)
        this.checklists = response.data.data
      } finally {
        this.loading = false
      }
    },

    async fetchChecklist(id) {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/checklists/${id}`)
        this.currentChecklist = response.data.data
        return this.currentChecklist
      } finally {
        this.loading = false
      }
    }
  }
})
