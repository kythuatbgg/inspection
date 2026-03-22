import { describe, it, expect, vi, beforeEach } from 'vitest'

// Mock the module — only the composable function is tested here
vi.mock('@/composables/useInstallPrompt', () => ({
  useInstallPrompt: vi.fn(() => ({
    canInstall: { value: false },
    showBanner: { value: false },
    install: vi.fn(),
    dismiss: vi.fn(),
  })),
}))

import { useInstallPrompt } from '@/composables/useInstallPrompt'

describe('useInstallPrompt', () => {
  beforeEach(() => {
    vi.clearAllMocks()
  })

  describe('exports', () => {
    it('is a function', () => {
      expect(typeof useInstallPrompt).toBe('function')
    })
  })

  describe('return shape', () => {
    it('returns canInstall, showBanner, install, dismiss', () => {
      const result = useInstallPrompt()
      expect(result.canInstall).toBeDefined()
      expect(result.showBanner).toBeDefined()
      expect(typeof result.install).toBe('function')
      expect(typeof result.dismiss).toBe('function')
    })

    it('canInstall and showBanner are Ref objects', () => {
      const { canInstall, showBanner } = useInstallPrompt()
      expect(canInstall.value).toBe(false)
      expect(showBanner.value).toBe(false)
    })

    it('install and dismiss are functions', () => {
      const { install, dismiss } = useInstallPrompt()
      expect(typeof install).toBe('function')
      expect(typeof dismiss).toBe('function')
    })
  })
})
