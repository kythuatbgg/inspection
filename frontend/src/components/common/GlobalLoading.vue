<template>
  <div v-if="globalLoadingCount > 0" class="fixed inset-0 z-[9999] flex flex-col items-center justify-center bg-white/60 backdrop-blur-sm transition-all duration-300">
    <div class="bg-white p-5 rounded-2xl shadow-xl flex flex-col items-center gap-3 border border-gray-100">
      <div class="w-12 h-12 rounded-full border-4 border-gray-100 border-t-primary-600 animate-spin"></div>
      <span class="text-sm font-semibold text-gray-700">Đang xử lý...</span>
    </div>
  </div>
</template>

<script setup>
import { globalLoadingCount } from '@/composables/useLoading'
</script>
