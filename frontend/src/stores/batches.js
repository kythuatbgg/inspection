import { defineStore } from 'pinia'
import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000/api'

export const useBatchesStore = defineStore('batches', {
  state: () => ({
    batches: [],
    currentBatch: null,
    loading: false
  }),

  getters: {
    activeBatches: (state) => state.batches.filter(b => b.status === 'active'),
    pendingPlans: (state) => state.currentBatch?.plan_details?.filter(p => p.status === 'planned') || []
  },

  actions: {
    async fetchBatches() {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/batches`)
        this.batches = response.data.data
      } finally {
        this.loading = false
      }
    },

    async fetchBatch(id) {
      this.loading = true
      try {
        const response = await axios.get(`${API_URL}/batches/${id}`)
        this.currentBatch = response.data.data
      } finally {
        this.loading = false
      }
    },

    async createBatch(data) {
      const response = await axios.post(`${API_URL}/batches`, data)
      this.batches.push(response.data.data)
      return response.data.data
    },

    async updateBatchStatus(id, status) {
      const response = await axios.patch(`${API_URL}/batches/${id}`, { status })
      const index = this.batches.findIndex(b => b.id === id)
      if (index !== -1) this.batches[index] = response.data.data
      return response.data.data
    }
  }
})
