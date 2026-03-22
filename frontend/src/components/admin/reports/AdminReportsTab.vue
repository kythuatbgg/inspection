<template>
  <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6 mb-4">
    <!-- Header -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
      <div class="flex flex-col md:flex-row items-start md:items-center gap-2 md:gap-3 w-full md:w-auto">
        <MobileBottomSheet
          v-model="search.selectedBatchId.value"
          :options="search.batches.value"
          :placeholder="$t('reports.selectBatchPlaceholder')"
          :label="$t('reports.title')"
          container-class="w-full md:min-w-[200px] md:max-w-none"
          @update:modelValue="search.searchReports"
        />
        <div class="relative w-full md:flex-1 md:min-w-[200px] md:max-w-[320px]">
          <Search :size="14" class="absolute left-3 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none" />
          <input
            v-model="search.searchCabinet.value"
            type="text"
            class="w-full pl-9 pr-3 py-2 min-h-[48px] md:min-h-[40px] text-sm text-slate-700 bg-slate-50/80 md:bg-white border-transparent md:border-slate-300 rounded-xl md:rounded-lg focus:outline-none focus:border-primary-500 focus:ring-2 focus:ring-primary-500/20 transition-all placeholder:text-slate-400"
            :placeholder="$t('reports.searchCabinetPlaceholder')"
            @input="search.debouncedSearch"
          />
        </div>
        <MobileBottomSheet
          v-model="search.reportLang.value"
          :options="search.langOptions"
          :placeholder="$t('reports.reportLang')"
          :label="$t('reports.reportLang')"
          container-class="w-full md:min-w-[160px] md:max-w-[180px]"
        />
      </div>

      <div v-if="search.selectedBatchId.value" class="flex flex-col md:flex-row gap-2 mt-3 md:mt-0 w-full md:w-auto">
        <button
          class="btn btn-primary"
          :disabled="exportComps.dl.isDownloading(`batch-summary:${search.selectedBatchId.value}`)"
          @click="exportComps.downloadBatchSummary(search.selectedBatchId.value)"
        >
          <FileText :size="14" />
          {{ $t('reports.batchSummary') }}
        </button>
        <button
          class="btn btn-accent"
          :disabled="exportComps.dl.isDownloading(`acceptance:${search.selectedBatchId.value}`)"
          @click="exportComps.downloadAcceptance(search.selectedBatchId.value)"
        >
          <Award :size="14" />
          {{ $t('reports.acceptanceReport') }}
        </button>
        <button
          class="btn btn-warning"
          :disabled="exportComps.dl.isDownloading(`critical-pdf:${search.selectedBatchId.value}`)"
          @click="exportComps.downloadCriticalErrors(search.selectedBatchId.value)"
        >
          <AlertTriangle :size="14" />
          {{ $t('reports.criticalErrors') }}
        </button>
      </div>
    </div>

    <!-- Loading -->
    <div v-if="search.loadingSearch.value" class="flex items-center justify-center gap-2 py-8 text-[13px] text-slate-500">
      <Loader2 :size="24" class="animate-spin" />
      <span>{{ $t('common.loading') }}</span>
    </div>

    <!-- Results -->
    <template v-else-if="search.searchResults.value.length">
      <AdminInspectionResultsTable
        class="d-md-table"
        :items="search.searchResults.value"
        :is-downloading="exportComps.dl.isDownloading"
        @download="(id, cabinet) => exportComps.downloadInspectionReport(id, search.reportLang.value, cabinet)"
      />
      <AdminInspectionResultsMobileList
        class="d-md-none"
        :items="search.searchResults.value"
        :is-downloading="exportComps.dl.isDownloading"
        @download="(id, cabinet) => exportComps.downloadInspectionReport(id, search.reportLang.value, cabinet)"
      />
    </template>

    <!-- Empty State -->
    <div v-else class="flex flex-col items-center justify-center py-12 px-4 text-center text-slate-500">
      <FileSearch :size="48" class="text-slate-300 mb-3" />
      <p>{{ search.searchCabinet.value || search.selectedBatchId.value ? $t('reports.noDataYet') : $t('reports.selectBatchForReport') }}</p>
    </div>
  </div>
</template>

<script setup>
import { FileText, FileSearch, Loader2, AlertTriangle, Award, Search } from 'lucide-vue-next'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'
import AdminInspectionResultsTable from './AdminInspectionResultsTable.vue'
import AdminInspectionResultsMobileList from './AdminInspectionResultsMobileList.vue'

defineProps({
  search: { type: Object, required: true },
  exportComps: { type: Object, required: true },
})
</script>
