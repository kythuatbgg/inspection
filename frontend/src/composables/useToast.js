import { reactive } from 'vue'

// Singleton reactive state — shared across all useToast() calls
const state = reactive({
  message: '',
  type: 'success', // 'success' | 'error' | 'info'
  visible: false,
})

let timer = null
let hideTimer = null

/**
 * Single source of truth for toast notifications.
 * Import and call anywhere in the app — no props/callbacks needed.
 *
 * @example
 * const { success, error, info } = useToast()
 * success('Saved!')
 * error('Something went wrong')
 * info('Processing...')
 */
export function useToast() {
  function showToast(message, type = 'success') {
    // Cancel any pending hide timer so the new toast is never cut short
    if (hideTimer) {
      clearTimeout(hideTimer)
      hideTimer = null
    }
    // Cancel any pending re-show (prevents flicker when called rapidly)
    if (timer) {
      clearTimeout(timer)
      timer = null
    }

    // Set state immediately — Transition re-triggers when visible goes false→true
    state.visible = false
    state.message = message
    state.type = type

    timer = setTimeout(() => {
      state.visible = true
      hideTimer = setTimeout(() => {
        state.visible = false
      }, 3000)
    }, 20)
  }

  function success(message) {
    showToast(message, 'success')
  }

  function error(message) {
    showToast(message, 'error')
  }

  function info(message) {
    showToast(message, 'info')
  }

  return { state, success, error, info }
}
