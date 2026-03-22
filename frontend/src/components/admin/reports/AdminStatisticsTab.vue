<template>
  <div class="space-y-4 md:space-y-6">
    <!-- KPI Cards -->
    <AdminKpiGrid :stats="stats.stats.value" />

    <!-- Filters -->
    <div class="bg-white rounded-lg shadow-sm border border-slate-200 p-4 md:p-6">
      <div class="flex flex-col md:flex-row gap-4">
        <div class="flex-1 min-w-0">
          <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">{{ $t('reports.fromDate') }}</label>
          <MobileDatePicker
            v-model="stats.filterFrom.value"
            :placeholder="$t('reports.fromDate')"
            :label="$t('reports.fromDate')"
            @update:modelValue="stats.loadStats"
          />
        </div>
        <div class="flex-1 min-w-0">
          <label class="block text-[11px] font-semibold text-slate-500 uppercase tracking-wide mb-1">{{ $t('reports.toDate') }}</label>
          <MobileDatePicker
            v-model="stats.filterTo.value"
            :placeholder="$t('reports.toDate')"
            :label="$t('reports.toDate')"
            @update:modelValue="stats.loadStats"
          />
        </div>
      </div>
    </div>

    <!-- Data panels -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 md:gap-6">
      <AdminBtsStatsBlock :data="stats.btsData.value" :loading="stats.loadingBts.value" />
      <AdminInspectorStatsBlock :data="stats.inspectorData.value" :loading="stats.loadingInspector.value" />
    </div>

    <!-- Common Errors -->
    <AdminErrorStatsBlock :data="stats.errorData.value" :loading="stats.loadingErrors.value" />
  </div>
</template>

<script setup>
import MobileDatePicker from '@/components/common/MobileDatePicker.vue'
import AdminKpiGrid from './AdminKpiGrid.vue'
import AdminBtsStatsBlock from './AdminBtsStatsBlock.vue'
import AdminInspectorStatsBlock from './AdminInspectorStatsBlock.vue'
import AdminErrorStatsBlock from './AdminErrorStatsBlock.vue'

defineProps({
  stats: { type: Object, required: true },
})
</script>
