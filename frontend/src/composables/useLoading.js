import { ref } from 'vue'

export const globalLoadingCount = ref(0)

export const showGlobalLoading = () => {
  globalLoadingCount.value++
}

export const hideGlobalLoading = () => {
  if (globalLoadingCount.value > 0) {
    globalLoadingCount.value--
  }
}
