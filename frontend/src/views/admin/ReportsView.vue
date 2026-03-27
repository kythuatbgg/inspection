<template>
  <div class="p-4 lg:p-8 max-w-[1200px] mx-auto w-full">
    <!-- Header -->
    <div class="mb-4 lg:mb-6">
      <h1 class="text-xl lg:text-[22px] font-bold font-heading text-slate-900">{{ $t('reports.title') }}</h1>
    </div>

    <!-- Tabs -->
    <div class="flex gap-1 border-b-2 border-slate-200 mb-5 overflow-x-auto scrollbar-hide pb-[1px]">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="flex items-center gap-1.5 px-4 py-2.5 text-[13px] font-medium transition-colors whitespace-nowrap shrink-0 border-b-2 -mb-[2.5px]"
        :class="activeTab === tab.key ? '!text-primary-600 !border-primary-600' : 'text-slate-500 border-transparent hover:text-slate-700'"
        @click="onTabChange(tab.key)"
      >
        <component :is="tab.icon" :size="16" />
        <span>{{ $t(tab.label) }}</span>
      </button>
    </div>

    <!-- Tab content -->
    <div class="tab-content">
      <AdminReportsTab
        v-if="activeTab === 'reports'"
        :search="search"
        :export-comps="exportComps"
      />
      <AdminStatisticsTab
        v-if="activeTab === 'statistics'"
        :stats="stats"
      />
      <AdminExportTab
        v-if="activeTab === 'export'"
        :search-comps="search"
        :export-comps="exportComps"
        :filter-params="filterParams"
      />
    </div>

  </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import {
  FileText, FileSpreadsheet, BarChart3
} from 'lucide-vue-next'
import { useAdminReportsSearch } from '@/composables/useAdminReportsSearch'
import { useAdminReportsStats } from '@/composables/useAdminReportsStats'
import { useAdminReportsExport } from '@/composables/useAdminReportsExport'
import { useDownloadState } from '@/composables/useDownloadState'
import { useToast } from '@/composables/useToast'
import AdminReportsTab from '@/components/admin/reports/AdminReportsTab.vue'
import AdminStatisticsTab from '@/components/admin/reports/AdminStatisticsTab.vue'
import AdminExportTab from '@/components/admin/reports/AdminExportTab.vue'

const { success, error } = useToast()

// ── Tabs config ──────────────────────────────────────────────
const tabs = [
  { key: 'reports',    label: 'reports.tabReports',    icon: FileText },
  { key: 'statistics',  label: 'reports.tabStatistics', icon: BarChart3 },
  { key: 'export',     label: 'reports.tabExport',     icon: FileSpreadsheet },
]

const activeTab = ref('reports')

// ── Download state ─────────────────────────────────────────────
const dl = useDownloadState()

// ── Composables ─────────────────────────────────────────────
const search = useAdminReportsSearch({ onError: error })
const stats  = useAdminReportsStats({ onError: error })
const exportComps = useAdminReportsExport({ dl, onSuccess: success, onError: error })

// Filter params for statistics export
const filterParams = computed(() => {
  const p = {}
  if (stats.filterFrom.value) p.from = stats.filterFrom.value
  if (stats.filterTo.value)   p.to   = stats.filterTo.value
  return p
})

// ── Tab change ──────────────────────────────────────────────
async function onTabChange(key) {
  activeTab.value = key
  // Lazy load Statistics the first time it's opened
  if (key === 'statistics') {
    await stats.loadStatsIfNeeded()
  }
}

// ── Lifecycle ───────────────────────────────────────────────
onMounted(() => {
  search.loadBatches()
})

onUnmounted(() => {
  search.cleanup()
})
</script>
