<template>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6 mt-4 md:mt-6">
    <h3 class="text-sm font-semibold text-slate-900 mb-3.5">{{ $t('reports.byErrorType') }}</h3>
    <div v-if="loading" class="flex items-center justify-center gap-2 py-6 text-[13px] text-slate-500"><Loader2 :size="18" class="animate-spin" /></div>
    <table v-else-if="data.length" class="data-table compact d-md-table">
      <thead>
        <tr>
          <th>{{ $t('reports.errorContent') }}</th>
          <th>{{ $t('reports.category') }}</th>
          <th>{{ $t('reports.count') }}</th>
        </tr>
      </thead>
      <tbody>
        <tr v-for="row in data" :key="row.error_content">
          <td>{{ row.error_content }}</td>
          <td><span class="badge badge-category">{{ row.category }}</span></td>
          <td class="text-fail font-bold">{{ row.count }}</td>
        </tr>
      </tbody>
    </table>
    <div v-if="data.length && !loading" class="flex flex-col gap-3 py-2 d-md-none">
      <div v-for="row in data" :key="row.error_content" class="bg-white rounded-lg border border-slate-200 p-3 shadow-sm">
        <div class="flex flex-col gap-1 text-[13px]">
          <div class="flex items-start justify-between">
            <span class="flex-1 text-left font-medium text-slate-900 text-[13px]">{{ row.error_content }}</span>
            <span class="text-fail font-bold text-base min-w-[30px] text-right">{{ row.count }}</span>
          </div>
          <div class="flex justify-between mt-1">
            <span class="badge badge-category text-[10px]">{{ row.category }}</span>
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
