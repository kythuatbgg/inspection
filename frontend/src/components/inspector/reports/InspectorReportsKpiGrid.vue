<template>
  <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
    <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
          <ClipboardCheck class="w-5 h-5 text-primary-600" />
        </div>
        <div>
          <p class="text-xl font-extrabold text-slate-900 leading-none">{{ stats.total ?? 0 }}</p>
          <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mt-1">{{ $t('inspector.totalInspected') }}</p>
        </div>
      </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
          <TrendingUp class="w-5 h-5 text-emerald-600" />
        </div>
        <div>
          <p class="text-xl font-extrabold leading-none" :class="stats.pass_rate >= 70 ? 'text-emerald-600' : 'text-red-600'">{{ stats.pass_rate ?? 0 }}%</p>
          <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mt-1">{{ $t('inspector.passRate') }}</p>
        </div>
      </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
          <AlertTriangle class="w-5 h-5 text-red-600" />
        </div>
        <div>
          <p class="text-xl font-extrabold text-red-600 leading-none">{{ stats.critical_errors ?? 0 }}</p>
          <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mt-1">{{ $t('inspector.criticalErrors') }}</p>
        </div>
      </div>
    </div>

    <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
      <div class="flex items-center gap-3">
        <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
          <BarChart3 class="w-5 h-5 text-amber-600" />
        </div>
        <div>
          <p class="text-xl font-extrabold text-slate-900 leading-none">{{ stats.avg_score ?? 0 }}</p>
          <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mt-1">{{ $t('inspector.avgScore') }}</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ClipboardCheck, TrendingUp, AlertTriangle, BarChart3 } from 'lucide-vue-next'

defineProps({
  stats: {
    type: Object,
    default: () => ({ total: 0, passed: 0, failed: 0, pass_rate: 0, critical_errors: 0, avg_score: 0 }),
  },
})
</script>
