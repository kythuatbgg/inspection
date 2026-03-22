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

    <!-- Toast -->
    <Transition 
      enter-active-class="transition-all duration-300" leave-active-class="transition-all duration-300" 
      enter-from-class="opacity-0 translate-y-2.5" leave-to-class="opacity-0 translate-y-2.5"
    >
      <div v-if="toast.show" class="fixed bottom-6 right-6 px-5 py-3 rounded-lg text-[13px] font-medium z-[9999] shadow-lg text-white" :class="toast.type === 'success' ? 'bg-emerald-600' : 'bg-red-600'">
        {{ toast.message }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, watch, onMounted, onUnmounted, computed } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  FileText, FileSpreadsheet, BarChart3
} from 'lucide-vue-next'
import { useAdminReportsSearch } from '@/composables/useAdminReportsSearch'
import { useAdminReportsStats } from '@/composables/useAdminReportsStats'
import { useAdminReportsExport } from '@/composables/useAdminReportsExport'
import { useDownloadState } from '@/composables/useDownloadState'
import AdminReportsTab from '@/components/admin/reports/AdminReportsTab.vue'
import AdminStatisticsTab from '@/components/admin/reports/AdminStatisticsTab.vue'
import AdminExportTab from '@/components/admin/reports/AdminExportTab.vue'

const { t } = useI18n()

// ── Tabs config ──────────────────────────────────────────────
const tabs = [
  { key: 'reports',    label: 'reports.tabReports',    icon: FileText },
  { key: 'statistics',  label: 'reports.tabStatistics', icon: BarChart3 },
  { key: 'export',     label: 'reports.tabExport',     icon: FileSpreadsheet },
]

const activeTab = ref('reports')

// ── Toast ───────────────────────────────────────────────────
const toast = ref({ show: false, message: '', type: 'success' })
function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

// Register global toast handler for composables
if (typeof window !== 'undefined') {
  window.__adminReportsToast = showToast
}

// ── Download state (shared key Set so only the active button is disabled) ──
const dl = useDownloadState()

// ── Composables ─────────────────────────────────────────────
const search = useAdminReportsSearch({ onError: showToast })
const stats  = useAdminReportsStats({ onError: showToast })
const exportComps = useAdminReportsExport({ dl })

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
  if (typeof window !== 'undefined') {
    delete window.__adminReportsToast
  }
})
</script>
