import { reactive } from 'vue'

// Singleton reactive state — shared across all useToast() calls
const state = reactive({
  show: false,
  message: '',
  type: 'success', // 'success' | 'error' | 'info'
})

let timer = null

/**
 * Single source of truth for toast notifications.
 * Import and call anywhere in the app — no props/callbacks needed.
 *
 * @example
 * const toast = useToast()
 * toast.success('Saved!')
 * toast.error('Something went wrong')
 * toast.info('Processing...')
 */
export function useToast() {
  function showToast(message, type = 'success') {
    // Reset any existing timer to prevent premature hide
    if (timer) {
      clearTimeout(timer)
      timer = null
    }
    state.show = false
    // Tick so Vue re-triggers Transition on re-open
    setTimeout(() => {
      state.show = true
      state.message = message
      state.type = type
    }, 10)

    timer = setTimeout(() => {
      state.show = false
    }, 3000)
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
