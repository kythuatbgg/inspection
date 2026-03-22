<template>
  <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
    <h3 class="text-sm font-bold text-slate-900 mb-4 font-heading">{{ $t('inspector.timeline') }}</h3>

    <div v-if="loading" class="flex items-center justify-center h-32">
      <Loader2 class="w-6 h-6 animate-spin text-slate-400" />
    </div>

    <div v-else-if="data.length" class="flex items-end gap-2 h-32">
      <div
        v-for="month in data"
        :key="month.month"
        class="flex-1 flex flex-col items-center gap-1"
      >
        <div
          class="w-full flex flex-col items-stretch rounded-md overflow-hidden"
          :style="{ height: getBarHeight(month.total) + 'px', minHeight: '4px' }"
        >
          <div class="bg-emerald-400 transition-all" :style="{ flex: month.passed }"></div>
          <div class="bg-red-400 transition-all" :style="{ flex: month.failed || 0.001 }"></div>
        </div>
        <span class="text-[9px] text-slate-400 font-semibold">{{ formatTimelineLabel(month.month, locale) }}</span>
        <span class="text-[10px] font-bold text-slate-600">{{ month.total }}</span>
      </div>
    </div>

    <div v-else class="flex items-center justify-center h-32 text-sm text-slate-400">{{ $t('common.noData') }}</div>

    <div v-if="data.length" class="flex items-center gap-4 mt-3 text-[10px] font-semibold">
      <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-400"></span> PASS</span>
      <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-red-400"></span> FAIL</span>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { Loader2 } from 'lucide-vue-next'
import { formatTimelineLabel } from '@/utils/formatters'

const { locale } = useI18n()
const props = defineProps({
  data: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})

const maxTotal = computed(() => Math.max(...props.data.map(m => m.total), 1))

function getBarHeight(total) {
  if (total === 0) return 4
  return Math.max(8, (total / maxTotal.value) * 100)
}
</script>
