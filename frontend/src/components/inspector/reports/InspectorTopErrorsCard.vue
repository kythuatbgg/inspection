<template>
  <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
    <h3 class="text-sm font-bold text-slate-900 mb-4 font-heading">{{ $t('inspector.topErrors') }}</h3>

    <div v-if="loading" class="flex items-center justify-center h-24">
      <Loader2 class="w-6 h-6 animate-spin text-slate-400" />
    </div>

    <div v-else-if="data.length" class="space-y-3">
      <div v-for="err in data" :key="err.error_content">
        <div class="flex items-center justify-between mb-1">
          <p class="text-xs text-slate-700 font-medium truncate flex-1 mr-3">{{ err.error_content }}</p>
          <span class="text-[10px] font-bold text-slate-500 shrink-0">{{ err.count }}x</span>
        </div>
        <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full bg-red-400 rounded-full transition-all"
            :style="{ width: getBarWidth(err.count) + '%', minWidth: '4px' }"
          ></div>
        </div>
      </div>
    </div>

    <div v-else class="flex items-center justify-center h-24 text-sm text-slate-400">{{ $t('inspector.noErrors') }}</div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Loader2 } from 'lucide-vue-next'

const props = defineProps({
  data: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const maxCount = computed(() => Math.max(...props.data.map(e => e.count), 1))
const getBarWidth = (count) => Math.max(8, (count / maxCount.value) * 100)
</script>
