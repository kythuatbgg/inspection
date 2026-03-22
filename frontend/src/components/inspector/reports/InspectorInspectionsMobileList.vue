<template>
  <div class="md:hidden divide-y divide-slate-100">
    <div v-for="insp in items" :key="'m-' + insp.id" class="p-4">
      <div class="flex items-center justify-between mb-2">
        <span class="font-bold text-slate-900 font-mono text-sm">{{ insp.cabinet_code }}</span>
        <span
          class="text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-widest"
          :class="insp.final_result === 'PASS' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
        >{{ insp.final_result }}</span>
      </div>
      <div class="flex items-center gap-3 text-[11px] text-slate-500 mb-3">
        <span>{{ insp.batch_name }}</span>
        <span>·</span>
        <span class="font-semibold text-slate-700">{{ $t('common.score') }} {{ insp.total_score }}</span>
        <span>·</span>
        <span>{{ formatDate(insp.created_at) }}</span>
      </div>
      <button
        :disabled="isDownloading(insp.id)"
        class="w-full min-h-[40px] inline-flex items-center justify-center gap-1.5 rounded-lg text-xs font-bold transition-all active:scale-[0.98]"
        :class="isDownloading(insp.id) ? 'bg-slate-100 text-slate-400' : 'bg-primary-50 text-primary-700 hover:bg-primary-100'"
        @click="$emit('download', insp.id, insp.cabinet_code)"
      >
        <Loader2 v-if="isDownloading(insp.id)" class="w-3.5 h-3.5 animate-spin" />
        <Download v-else class="w-3.5 h-3.5" />
        {{ $t('inspector.exportPdf') }}
      </button>
    </div>
  </div>
</template>

<script setup>
import { Loader2, Download } from 'lucide-vue-next'
import { formatDate } from '@/utils/formatters'

defineProps({
  items: { type: Array, default: () => [] },
  isDownloading: { type: Function, required: true },
})

defineEmits(['download'])
</script>
