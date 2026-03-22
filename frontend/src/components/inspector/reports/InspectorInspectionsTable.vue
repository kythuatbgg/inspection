<template>
  <table class="w-full text-sm">
    <thead>
      <tr class="bg-slate-50">
        <th class="text-left px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('common.cabinetCode') }}</th>
        <th class="text-left px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('common.batch') }}</th>
        <th class="text-left px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('common.result') }}</th>
        <th class="text-left px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('common.score') }}</th>
        <th class="text-left px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider">{{ $t('common.date') }}</th>
        <th class="text-right px-5 py-3 text-[11px] font-bold text-slate-500 uppercase tracking-wider"></th>
      </tr>
    </thead>
    <tbody>
      <tr
        v-for="insp in items"
        :key="insp.id"
        class="border-t border-slate-100 hover:bg-slate-50 transition-colors"
      >
        <td class="px-5 py-3 font-semibold text-slate-900 font-mono text-xs">{{ insp.cabinet_code }}</td>
        <td class="px-5 py-3 text-slate-600 text-xs">{{ insp.batch_name }}</td>
        <td class="px-5 py-3">
          <span
            class="text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-widest"
            :class="insp.final_result === 'PASS' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
          >{{ insp.final_result }}</span>
        </td>
        <td class="px-5 py-3 font-bold text-slate-700 text-xs">{{ insp.total_score }}</td>
        <td class="px-5 py-3 text-slate-500 text-xs">{{ formatDate(insp.created_at) }}</td>
        <td class="px-5 py-3 text-right">
          <button
            :disabled="isDownloading(insp.id)"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
            :class="isDownloading(insp.id) ? 'bg-slate-100 text-slate-400' : 'bg-primary-50 text-primary-700 hover:bg-primary-100'"
            @click="$emit('download', insp.id, insp.cabinet_code)"
          >
            <Loader2 v-if="isDownloading(insp.id)" class="w-3.5 h-3.5 animate-spin" />
            <Download v-else class="w-3.5 h-3.5" />
            PDF
          </button>
        </td>
      </tr>
    </tbody>
  </table>
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
