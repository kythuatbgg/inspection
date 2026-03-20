import { beforeEach, describe, expect, it, vi } from 'vitest'

vi.mock('@/services/api.js', () => ({
  default: {
    get: vi.fn(),
    post: vi.fn(),
    put: vi.fn(),
    delete: vi.fn()
  }
}))

import api from '@/services/api.js'
import { cabinetService } from '@/services/cabinetService.js'

describe('Cabinet Service (cabinetService.js)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('getCabinets', () => {
    it('calls GET /cabinets with params and returns paginated data', async () => {
      const mockPayload = {
        data: [{ cabinet_code: 'CAB-001' }],
        current_page: 1,
        total: 1
      }
      api.get.mockResolvedValue({ data: mockPayload })

      const result = await cabinetService.getCabinets({ bts_site: 'A' })

      expect(api.get).toHaveBeenCalledWith('/cabinets', { params: { bts_site: 'A' } })
      expect(result).toEqual(mockPayload)
    })
  })

  describe('getCabinetById', () => {
    it('calls GET /cabinets/{id}', async () => {
      const mockData = { cabinet_code: 'CAB-001', bts_site: 'BTS-01' }
      api.get.mockResolvedValue({ data: { data: mockData } })

      const result = await cabinetService.getCabinetById('CAB-001')

      expect(api.get).toHaveBeenCalledWith('/cabinets/CAB-001')
      expect(result).toEqual(mockData)
    })
  })

  describe('createCabinet', () => {
    it('calls POST /cabinets with data', async () => {
      const newCabinet = { cabinet_code: 'CAB-999', bts_site: 'BTS-09', lat: 1, lng: 2 }
      const mockResponse = { ...newCabinet, note: 'New row' }
      api.post.mockResolvedValue({ data: { data: mockResponse } })

      const result = await cabinetService.createCabinet(newCabinet)

      expect(api.post).toHaveBeenCalledWith('/cabinets', newCabinet)
      expect(result).toEqual(mockResponse)
    })
  })

  describe('updateCabinet', () => {
    it('calls PUT /cabinets/{id}', async () => {
      const updateData = { note: 'Updated note' }
      api.put.mockResolvedValue({ data: { data: updateData } })

      await cabinetService.updateCabinet('CAB-001', updateData)

      expect(api.put).toHaveBeenCalledWith('/cabinets/CAB-001', updateData)
    })
  })

  describe('deleteCabinet', () => {
    it('calls DELETE /cabinets/{id}', async () => {
      api.delete.mockResolvedValue({ data: { success: true } })

      await cabinetService.deleteCabinet('CAB-001')

      expect(api.delete).toHaveBeenCalledWith('/cabinets/CAB-001')
    })
  })

  describe('exportCabinets', () => {
    it('requests the streamed export as a blob', async () => {
      const blob = new Blob(['cabinet csv'])
      api.get.mockResolvedValue({ data: blob })

      const result = await cabinetService.exportCabinets()

      expect(api.get).toHaveBeenCalledWith('/cabinets/export', {
        responseType: 'blob'
      })
      expect(result).toBe(blob)
    })
  })

  describe('downloadTemplate', () => {
    it('requests the Excel template as a blob', async () => {
      const blob = new Blob(['template'])
      api.get.mockResolvedValue({ data: blob })

      const result = await cabinetService.downloadTemplate()

      expect(api.get).toHaveBeenCalledWith('/cabinets/template', {
        responseType: 'blob'
      })
      expect(result).toBe(blob)
    })
  })

  describe('exportResult', () => {
    it('requests the import result export with the given token', async () => {
      const blob = new Blob(['result'])
      api.get.mockResolvedValue({ data: blob })

      const result = await cabinetService.exportResult('token-123')

      expect(api.get).toHaveBeenCalledWith('/cabinets/export-result', {
        params: { token: 'token-123' },
        responseType: 'blob'
      })
      expect(result).toBe(blob)
    })
  })
})
