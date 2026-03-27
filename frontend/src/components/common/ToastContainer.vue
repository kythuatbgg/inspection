<template>
  <Transition
    enter-active-class="transition-all duration-300"
    leave-active-class="transition-all duration-300"
    enter-from-class="opacity-0 translate-y-2.5"
    leave-to-class="opacity-0 translate-y-2.5"
  >
    <div
      v-if="toast.state.visible"
      class="fixed bottom-6 right-6 z-[9999] px-5 py-3 rounded-lg text-[13px] font-medium text-white shadow-lg"
      :class="typeClasses[toast.state.type] || typeClasses.success"
    >
      {{ toast.state.message }}
    </div>
  </Transition>
</template>

<script setup>
import { useToast } from '@/composables/useToast'

const toast = useToast()

const typeClasses = {
  success: 'bg-emerald-600',
  error:   'bg-red-600',
  info:    'bg-blue-600',
}
</script>
