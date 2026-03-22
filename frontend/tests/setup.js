import { vi } from 'vitest'
import { createPinia, setActivePinia } from 'pinia'

// ── IndexedDB polyfill (required for Dexie tests) ─────────────
import 'fake-indexeddb/auto'

// ── localStorage mock ─────────────────────────────────────────
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

// ── navigator.onLine mock ──────────────────────────────────
Object.defineProperty(globalThis.navigator, 'onLine', {
  value: true,
  writable: true,
  configurable: true,
})

// ── window event listeners mock ────────────────────────────────
window.addEventListener = vi.fn()
window.removeEventListener = vi.fn()

// ── Pinia global setup ──────────────────────────────────────
const pinia = createPinia()
setActivePinia(pinia)
