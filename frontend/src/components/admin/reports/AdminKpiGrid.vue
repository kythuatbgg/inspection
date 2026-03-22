<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 md:gap-4 mb-4">
    <div v-for="kpi in kpis" :key="kpi.key" class="flex flex-col md:flex-row items-start md:items-center p-3 md:p-4.5 gap-2.5 md:gap-3.5 bg-white border border-slate-200 rounded-lg shadow-sm">
      <div class="w-9 h-9 md:w-10 md:h-10 flex items-center justify-center rounded-lg shrink-0" :class="kpi.iconClass">
        <component :is="kpi.icon" :size="20" />
      </div>
      <div class="flex flex-col">
        <span class="text-xl md:text-[22px] font-extrabold text-slate-900 leading-[1.1]" :class="kpi.numClass">{{ kpi.value }}</span>
        <span class="text-[11px] font-medium text-slate-500 uppercase tracking-wide mt-0.5">{{ kpi.labelKey ? $t(kpi.labelKey) : kpi.label }}</span>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { Layers, Box, TrendingUp, AlertCircle } from 'lucide-vue-next'

const props = defineProps({
  stats: { type: Object, required: true },
})

const kpis = computed(() => [
  {
    key: 'total_batches',
    icon: Layers,
    iconClass: 'bg-primary-50 text-primary-600',
    value: props.stats.total_batches ?? 0,
    labelKey: 'reports.totalBatches',
  },
  {
    key: 'total_cabinets_inspected',
    icon: Box,
    iconClass: 'bg-slate-100 text-slate-600',
    value: props.stats.total_cabinets_inspected ?? 0,
    labelKey: 'reports.totalInspected',
  },
  {
    key: 'pass_rate',
    icon: TrendingUp,
    iconClass: 'bg-emerald-50 text-emerald-600',
    numClass: '!text-emerald-600',
    value: (props.stats.pass_rate ?? 0) + '%',
    labelKey: 'reports.passRate',
  },
  {
    key: 'total_critical_errors',
    icon: AlertCircle,
    iconClass: 'bg-red-50 text-red-600',
    numClass: '!text-red-600',
    value: props.stats.total_critical_errors ?? 0,
    labelKey: 'reports.criticalErrorCount',
  },
])
</script>
