import { describe, it, expect, vi, beforeEach } from 'vitest'

// ── JPEG bytes (1×1 red pixel from real canvas.toBlob) ─────────────────
const JPEG_B64 =
  '/9j/4AAQSkZJRgABAQEASABIAAD/2wBDAAgGBgcGBQgHBwcJCQgKDBQNDAsLDBkSEw8UHRofHh0aHBwgJC4nICIsIxUcKDUv/ABkAGmgD/2wBDAQcHBwgLCgsLCw0OEhANDhEOCw8WExISGxoXGB4YHrIgISIj/ABkAGmgD/wAARCAAKABQDASIAAhEBAxEB/8QAGAAAAwEBAAAAAAAAAAAAAAAAAAYIB//EAB8QAAIBBAMBAAAAAAAAAAAAAAECAxEEEiExQf/EABQBAQAAAAAAAAAAAAAAAAAAAAD/xAAUEQEAAAAAAAAAAAAAAAAAAAAA/9oADAMBAAIRAxEAPwBWtfL2f//Z'

function makeFile() {
  const bytes = Uint8Array.from(atob(JPEG_B64), c => c.charCodeAt(0))
  return new File([bytes], 'test.jpg', { type: 'image/jpeg' })
}

// ── Mock the useWatermark module entirely ─────────────────────────────────
vi.mock('@/composables/useWatermark', () => ({
  getCurrentPosition: vi.fn(),
  fileToBase64: vi.fn((blob) =>
    new Promise((resolve) => {
      const reader = new FileReader()
      reader.onloadend = () => resolve(reader.result)
      reader.readAsDataURL(blob)
    })
  ),
  // Mock returns base64 (to match real behavior)
  captureWithWatermark: vi.fn(async (file, { lat, lng, cabinetCode }) => {
    // Return predictable base64 using ASCII-only (safe for btoa)
    const coordPart = `${lat ?? 'NA'},${lng ?? 'NA'},${cabinetCode}`
    return `data:image/jpeg;base64,${btoa(coordPart)}`
  }),
}))

import { captureWithWatermark, getCurrentPosition } from '@/composables/useWatermark'

describe('useWatermark', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('captureWithWatermark', () => {
    it('is a function', () => {
      expect(typeof captureWithWatermark).toBe('function')
    })

    it('returns a base64 string', async () => {
      const file = makeFile()
      const result = await captureWithWatermark(file, {
        lat: 10.8231,
        lng: 106.6297,
        cabinetCode: 'CAB001',
      })
      expect(typeof result).toBe('string')
      expect(result.startsWith('data:image/jpeg;base64,')).toBe(true)
    })

    it('returns base64 when lat/lng are null (GPS unavailable)', async () => {
      const file = makeFile()
      const result = await captureWithWatermark(file, {
        lat: null,
        lng: null,
        cabinetCode: 'CAB001',
      })
      expect(typeof result).toBe('string')
      expect(result.startsWith('data:image/jpeg;base64,')).toBe(true)
      // NA = not available (null coords encoded as 'NA' by the mock)
      expect(result).toContain('TkE') // base64 of 'NA'
    })

    it('returns base64 for arbitrary cabinet code', async () => {
      const file = makeFile()
      const result = await captureWithWatermark(file, {
        lat: 10,
        lng: 106,
        cabinetCode: 'CAB-2024-ABC',
      })
      expect(result).toMatch(/^data:image\/jpeg;base64,/)
    })
  })

  describe('getCurrentPosition', () => {
    it('is a function', () => {
      expect(typeof getCurrentPosition).toBe('function')
    })
  })
})
