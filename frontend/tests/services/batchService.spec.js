import { describe, it, expect, vi, beforeEach } from 'vitest'

// Mock api module
vi.mock('@/services/api.js', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

import api from '@/services/api.js'
import { batchService } from '@/services/batchService.js'

describe('Batch Service (batchService.js)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getBatches', () => {
    it('should call GET /batches with params', async () => {
      // axios wraps response in { data: ... }; service returns response.data (unwrapped)
      const axiosResponse = { data: [{ id: 1, name: 'Batch 1' }] }
      api.get.mockResolvedValue(axiosResponse)

      const result = await batchService.getBatches({ status: 'pending' })

      expect(api.get).toHaveBeenCalledWith('/batches', { params: { status: 'pending' } })
      // service returns response.data (unwrapped), so we get the array directly
      expect(result).toEqual([{ id: 1, name: 'Batch 1' }])
    })

    it('should handle empty params', async () => {
      api.get.mockResolvedValue({ data: [] })

      await batchService.getBatches()

      expect(api.get).toHaveBeenCalledWith('/batches', { params: {} })
    })
  })

  describe('getBatchById', () => {
    it('should call GET /batches/{id}', async () => {
      const mockData = { id: 1, name: 'Batch 1' }
      api.get.mockResolvedValue({ data: mockData })

      const result = await batchService.getBatchById(1)

      expect(api.get).toHaveBeenCalledWith('/batches/1')
      expect(result).toEqual(mockData)
    })
  })

  describe('createBatch', () => {
    it('should call POST /batches with data', async () => {
      const newBatch = { name: 'New Batch', description: 'Test' }
      const mockResponse = { id: 1, ...newBatch }
      api.post.mockResolvedValue({ data: { data: mockResponse } })

      const result = await batchService.createBatch(newBatch)

      expect(api.post).toHaveBeenCalledWith('/batches', newBatch)
      expect(result).toEqual(mockResponse)
    })
  })

  describe('updateBatch', () => {
    it('should call PUT /batches/{id}', async () => {
      const updateData = { name: 'Updated Batch' }
      api.put.mockResolvedValue({ data: { data: updateData } })

      await batchService.updateBatch(1, updateData)

      expect(api.put).toHaveBeenCalledWith('/batches/1', updateData)
    })
  })

  describe('deleteBatch', () => {
    it('should call DELETE /batches/{id}', async () => {
      api.delete.mockResolvedValue({ data: { success: true } })

      await batchService.deleteBatch(1)

      // Always called with 2 args: url + optional params object
      expect(api.delete).toHaveBeenCalledWith('/batches/1', expect.any(Object))
    })
  })

  describe('getBatchPlans', () => {
    it('should call GET /batches/{id}/plans', async () => {
      const mockPlans = [{ id: 1, name: 'Plan 1' }]
      api.get.mockResolvedValue({ data: mockPlans })

      const result = await batchService.getBatchPlans(1)

      expect(api.get).toHaveBeenCalledWith('/batches/1/plans')
      expect(result).toEqual(mockPlans)
    })
  })
})
