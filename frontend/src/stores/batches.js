import { defineStore } from 'pinia'
import api from '../services/api'

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
        const response = await api.get('/batches')
        this.batches = response.data.data
      } finally {
        this.loading = false
      }
    },

    async fetchBatch(id) {
      this.loading = true
      try {
        const response = await api.get(`/batches/${id}`)
        this.currentBatch = response.data.data
      } finally {
        this.loading = false
      }
    },

    async createBatch(data) {
      const response = await api.post('/batches', data)
      this.batches.push(response.data.data)
      return response.data.data
    },

    async updateBatchStatus(id, status) {
      const response = await api.patch(`/batches/${id}`, { status })
      const index = this.batches.findIndex(b => b.id === id)
      if (index !== -1) this.batches[index] = response.data.data
      return response.data.data
    },

    async fetchPlan(planId) {
      this.loading = true
      try {
        // Get batch that contains this plan
        const batchesResponse = await api.get('/batches')
        for (const batch of batchesResponse.data.data) {
          if (batch.plan_details) {
            const plan = batch.plan_details.find(p => p.id === parseInt(planId))
            if (plan) {
              this.currentBatch = batch
              return plan
            }
          }
        }
        return null
      } finally {
        this.loading = false
      }
    }
  }
})
