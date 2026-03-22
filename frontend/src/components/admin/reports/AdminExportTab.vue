<template>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6 mb-4">
    <div class="flex flex-col gap-4 text-center md:text-left">

      <!-- Batch Export -->
      <div class="flex flex-col md:flex-row items-center md:items-start gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
        <div class="w-14 h-14 flex items-center justify-center bg-primary-50 text-primary-600 rounded-[10px] shrink-0"><FileSpreadsheet :size="32" /></div>
        <div class="flex-1 w-full">
          <h4 class="text-sm font-semibold text-slate-900 mb-1.5">{{ $t('reports.batchExport') }}</h4>
          <MobileBottomSheet
            v-model="exportComps.exportBatchId.value"
            :options="searchComps.batches.value"
            :placeholder="$t('reports.selectBatchPlaceholder')"
            :label="$t('reports.batchExport')"
            container-class="w-full mt-2"
          />
        </div>
        <button
          class="btn btn-primary w-full md:w-auto min-h-[48px] md:min-h-[auto]"
          :disabled="!exportComps.exportBatchId.value || exportComps.dl.isDownloading(`batch-excel:${exportComps.exportBatchId.value}`)"
          @click="exportComps.downloadBatchExcel(exportComps.exportBatchId.value)"
        >
          <Download :size="14" />
          {{ exportComps.dl.isDownloading(`batch-excel:${exportComps.exportBatchId.value}`) ? $t('reports.downloading') : $t('reports.exportExcel') }}
        </button>
      </div>

      <!-- Statistics Export -->
      <div class="flex flex-col md:flex-row items-center md:items-start gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
        <div class="w-14 h-14 flex items-center justify-center bg-primary-50 text-primary-600 rounded-[10px] shrink-0"><BarChart3 :size="32" /></div>
        <div class="flex-1 w-full">
          <h4 class="text-sm font-semibold text-slate-900 mb-1.5">{{ $t('reports.exportAll') }}</h4>
          <p class="text-xs text-slate-500">Overview + BTS + Inspector</p>
        </div>
        <button
          class="btn btn-primary w-full md:w-auto min-h-[48px] md:min-h-[auto]"
          :disabled="exportComps.dl.isDownloading('stats-excel')"
          @click="exportComps.downloadStatsExcel(filterParams)"
        >
          <Download :size="14" />
          {{ exportComps.dl.isDownloading('stats-excel') ? $t('reports.downloading') : $t('reports.exportExcel') }}
        </button>
      </div>

      <!-- Critical Errors Export -->
      <div class="flex flex-col md:flex-row items-center md:items-start gap-4 p-4 bg-slate-50 rounded-lg border border-slate-200">
        <div class="w-14 h-14 flex items-center justify-center bg-red-50 text-red-600 rounded-[10px] shrink-0"><AlertTriangle :size="32" /></div>
        <div class="flex-1 w-full">
          <h4 class="text-sm font-semibold text-slate-900 mb-1.5">{{ $t('reports.criticalErrors') }}</h4>
          <MobileBottomSheet
            v-model="exportComps.exportErrorBatchId.value"
            :options="criticalErrorOptions"
            :placeholder="$t('common.all')"
            :label="$t('reports.criticalErrors')"
            container-class="w-full mt-2"
          />
        </div>
        <button
          class="btn btn-warning w-full md:w-auto min-h-[48px] md:min-h-[auto]"
          :disabled="exportComps.dl.isDownloading(`critical-excel:${exportComps.exportErrorBatchId.value}`)"
          @click="exportComps.downloadCriticalErrorsExcel(exportComps.exportErrorBatchId.value)"
        >
          <Download :size="14" />
          {{ exportComps.dl.isDownloading(`critical-excel:${exportComps.exportErrorBatchId.value}`) ? $t('reports.downloading') : $t('reports.exportExcel') }}
        </button>
      </div>

    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue'
import { useI18n } from 'vue-i18n'
import { FileSpreadsheet, BarChart3, Download, AlertTriangle } from 'lucide-vue-next'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'

const { t } = useI18n()

const props = defineProps({
  searchComps: {
    type: Object,
    required: true,
    // { batches }
  },
  exportComps: {
    type: Object,
    required: true,
    // { dl, exportBatchId, exportErrorBatchId, downloadBatchExcel, downloadStatsExcel, downloadCriticalErrorsExcel }
  },
  filterParams: {
    type: Object,
    default: () => ({}),
  },
})

const criticalErrorOptions = computed(() => [
  { value: '', label: t('common.all') },
  ...props.searchComps.batches.value.map(b => ({ value: b.id, label: b.name })),
])
</script>
