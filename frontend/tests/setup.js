import { vi } from 'vitest'

// Mock localStorage
const localStorageMock = {
  getItem: vi.fn(() => null),
  setItem: vi.fn(),
  removeItem: vi.fn(),
  clear: vi.fn(),
}

Object.defineProperty(globalThis, 'localStorage', {
  value: localStorageMock,
  writable: true,
})

// Mock navigator.onLine
Object.defineProperty(globalThis.navigator, 'onLine', {
  value: true,
  writable: true,
})

// Mock window events
window.addEventListener = vi.fn()
window.removeEventListener = vi.fn()
