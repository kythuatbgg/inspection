<template>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6">
    <h3 class="text-sm font-semibold text-slate-900 mb-3.5">{{ $t('reports.byBts') }}</h3>
    <div v-if="loading" class="flex items-center justify-center gap-2 py-6 text-[13px] text-slate-500"><Loader2 :size="18" class="animate-spin" /></div>
    <table v-else-if="data.length" class="data-table compact d-md-table">
      <thead>
        <tr>
          <th>{{ $t('reports.btsSite') }}</th>
          <th>{{ $t('reports.total') }}</th>
          <th>{{ $t('reports.passed') }}</th>
          <th>{{ $t('reports.failed') }}</th>
          <th>{{ $t('reports.passRate') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in data" :key="row.bts_site">
          <td class="font-mono">{{ row.bts_site }}</td>
          <td>{{ row.total }}</td>
          <td class="text-pass">{{ row.passed }}</td>
          <td class="text-fail">{{ row.failed }}</td>
          <td>
            <div class="bar-container">
              <div class="bar-fill" :style="{ width: row.pass_rate + '%' }"></div>
              <span class="bar-label">{{ row.pass_rate }}%</span>
            </div>
          </td>
        </tr>
      </tbody>
    </table>
    <div v-if="data.length && !loading" class="flex flex-col gap-3 py-2 d-md-none">
      <div v-for="row in data" :key="row.bts_site" class="bg-white rounded-lg border border-slate-200 p-3 shadow-sm">
        <div class="flex items-start justify-between mb-2 pb-2 border-b border-dashed border-slate-200">
          <div class="flex flex-col gap-1">
            <div class="font-semibold text-slate-900 text-sm font-mono">{{ row.bts_site }}</div>
          </div>
          <span class="badge" :class="row.pass_rate >= 80 ? 'badge-pass' : (row.pass_rate >= 50 ? 'badge-warning' : 'badge-fail')">{{ row.pass_rate }}% pass</span>
        </div>
        <div class="flex flex-col gap-2 text-[13px]">
          <div class="flex justify-between">
            <span class="text-slate-500">{{ $t('reports.total') }}</span>
            <span class="text-slate-900 text-right">{{ row.total }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-slate-500">{{ $t('reports.passed') }}/{{ $t('reports.failed') }}</span>
            <span class="text-slate-900 text-right"><span class="text-pass">{{ row.passed }}</span> / <span class="text-fail">{{ row.failed }}</span></span>
          </div>
        </div>
      </div>
    </div>
    <div v-else-if="!loading" class="flex flex-col items-center justify-center py-6 px-4 text-center text-slate-500"><p>{{ $t('reports.noDataYet') }}</p></div>
  </div>
</template>

<script setup>
import { Loader2 } from 'lucide-vue-next'

defineProps({
  data: { type: Array, default: () => [] },
  loading: { type: Boolean, default: false },
})
</script>
