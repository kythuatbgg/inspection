import { describe, it, expect, vi, beforeEach } from 'vitest'

describe('API Service (api.js)', () => {
  beforeEach(() => {
    vi.clearAllMocks()
    localStorage.getItem.mockReturnValue(null)
  })

  it('should export API_URL constant', async () => {
    const { API_URL } = await import('@/services/api.js')
    expect(API_URL).toBe('http://localhost:8000/api')
  })

  it('should export api instance', async () => {
    const { default: api } = await import('@/services/api.js')
    expect(api).toBeDefined()
    expect(typeof api.get).toBe('function')
    expect(typeof api.post).toBe('function')
    expect(typeof api.interceptors).toBe('object')
  })

  it('should have request and response interceptors', async () => {
    const { default: api } = await import('@/services/api.js')
    expect(api.interceptors.request).toBeDefined()
    expect(api.interceptors.response).toBeDefined()
    expect(api.interceptors.request.handlers.length).toBeGreaterThan(0)
    expect(api.interceptors.response.handlers.length).toBeGreaterThan(0)
  })
})
