<template>
  <div class="flex flex-col gap-3 py-2">
    <div v-for="row in items" :key="row.id" class="bg-white rounded-lg border border-slate-200 p-4 shadow-sm">
      <div class="flex items-start justify-between mb-3 pb-3 border-b border-dashed border-slate-200">
        <div class="flex flex-col gap-1">
          <div class="font-semibold text-slate-900 text-sm font-mono">{{ row.cabinet_code }}</div>
          <div class="text-xs text-slate-500 font-mono">{{ row.bts_site }}</div>
        </div>
        <span class="badge" :class="row.final_result === 'PASS' ? 'badge-pass' : 'badge-fail'">
          {{ row.final_result === 'PASS' ? $t('common.resultPass') : $t('common.resultFail') }}
        </span>
      </div>
      <div class="flex flex-col gap-2.5 text-[13px] mb-4">
        <div class="flex justify-between">
          <span class="text-slate-500">{{ $t('reports.score') }}</span>
          <span class="text-slate-900 text-right"><strong>{{ row.total_score }}</strong></span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500">{{ $t('reports.inspectorName') }}</span>
          <span class="text-slate-900 text-right">{{ row.inspector_name }}</span>
        </div>
        <div class="flex justify-between">
          <span class="text-slate-500">{{ $t('reports.selectBatch') }}</span>
          <span class="text-slate-400 text-right">{{ row.batch_name }}</span>
        </div>
      </div>
      <div class="flex gap-2 w-full">
        <button
          class="btn btn-secondary w-full min-h-[44px]"
          :disabled="isDownloading(`inspection:${row.id}`)"
          @click="$emit('download', row.id, row.cabinet_code)"
        >
          <Download :size="14" />
          {{ $t('reports.exportPdf') }}
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Download } from 'lucide-vue-next'

defineProps({
  items: { type: Array, default: () => [] },
  isDownloading: { type: Function, required: true },
})
defineEmits(['download'])
</script>
