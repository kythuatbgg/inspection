<template>
  <div class="p-4 md:p-6 space-y-6 pb-28 md:pb-6">

    <!-- ── Header ─────────────────────────────────────────── -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900 font-heading tracking-tight">{{ $t('inspector.myReports') }}</h1>
        <p class="text-xs text-slate-500 mt-1">{{ $t('inspector.myReportsDesc') }}</p>
      </div>
      <div class="flex gap-3">
        <MobileDatePicker
          v-model="dateFrom"
          :placeholder="$t('common.from')"
          trigger-class="flex-1 md:flex-none md:w-40"
        />
        <MobileDatePicker
          v-model="dateTo"
          :placeholder="$t('common.to')"
          trigger-class="flex-1 md:flex-none md:w-40"
        />
      </div>
    </div>

    <!-- ── Full-page initial loading ────────────────────────── -->
    <div v-if="dash.isInitialLoading.value" class="flex justify-center py-16">
      <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
    </div>

    <template v-else>

      <!-- ── Dashboard error banner ─────────────────────────── -->
      <div
        v-if="dash.dashboardError.value"
        class="flex items-center gap-3 p-4 bg-red-50 border border-red-200 rounded-xl text-sm text-red-700"
      >
        <AlertTriangle class="w-4 h-4 shrink-0" />
        {{ dash.dashboardError.value }}
        <button class="ml-auto font-semibold hover:underline" @click="refreshAll">Retry</button>
      </div>

      <!-- ── KPI Grid ─────────────────────────────────────── -->
      <InspectorReportsKpiGrid :stats="dash.overview.value" />

      <!-- ── Charts row ───────────────────────────────────── -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <InspectorTimelineChart
          :data="dash.timeline.value"
          :loading="dash.isRefreshingDashboard.value"
        />
        <InspectorTopErrorsCard
          :data="dash.topErrors.value"
          :loading="dash.isRefreshingDashboard.value"
        />
      </div>

      <!-- ── Inspections section ──────────────────────────── -->
      <InspectorInspectionsSection
        :items="list.inspections.value"
        :is-loading="list.isLoadingInspections.value"
        :error="list.inspectionsError.value"
        :is-downloading="dl.isDownloading"
        :page="list.page.value"
        :per-page="list.perPage.value"
        :total="list.total.value"
        :last-page="list.lastPage.value"
        :has-pagination="list.hasPagination.value"
        :empty-reason="list.emptyStateReason.value"
        @download="(id, cabinet) => dl.downloadReport(id, cabinet, reportLang)"
        @retry="list.fetchInspections"
        @go-to-page="list.goToPage"
        @clear-filters="list.resetFilters"
      >
        <!-- Filters slot -->
        <template #filters>
          <InspectorReportsFilters
            :search-query="list.searchQuery.value"
            :filter-result="list.filterResult.value"
            :report-lang="reportLang"
            @update:searchQuery="onSearchInput"
            @update:filterResult="v => { list.filterResult.value = v }"
            @update:reportLang="v => { reportLang = v }"
          />
        </template>
      </InspectorInspectionsSection>

    </template>

    <!-- ── Toast ─────────────────────────────────────────────── -->
    <Transition name="toast">
      <div
        v-if="toast.show"
        class="toast"
        :class="toast.type === 'error' ? 'toast-error' : 'toast-success'"
      >
        {{ toast.message }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { useI18n } from 'vue-i18n'
import { Loader2, AlertTriangle } from 'lucide-vue-next'
import MobileDatePicker from '@/components/common/MobileDatePicker.vue'
import InspectorReportsKpiGrid from '@/components/inspector/reports/InspectorReportsKpiGrid.vue'
import InspectorTimelineChart from '@/components/inspector/reports/InspectorTimelineChart.vue'
import InspectorTopErrorsCard from '@/components/inspector/reports/InspectorTopErrorsCard.vue'
import InspectorReportsFilters from '@/components/inspector/reports/InspectorReportsFilters.vue'
import InspectorInspectionsSection from '@/components/inspector/reports/InspectorInspectionsSection.vue'
import { useInspectorReportsDashboard } from '@/composables/useInspectorReportsDashboard'
import { useInspectorInspectionsList } from '@/composables/useInspectorInspectionsList'
import { useInspectorReportDownload } from '@/composables/useInspectorReportDownload'

// ── Toast ───────────────────────────────────────────────────
const toast = ref({ show: false, message: '', type: 'success' })
function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

// ── Filter state ────────────────────────────────────────────
const dateFrom = ref('')
const dateTo = ref('')
const reportLang = ref('en')

const dateParams = computed(() => {
  const p = {}
  if (dateFrom.value) p.from = dateFrom.value
  if (dateTo.value)   p.to   = dateTo.value
  return p
})

// ── Composables ─────────────────────────────────────────────
const dash = useInspectorReportsDashboard({ onError: showToast })
const list = useInspectorInspectionsList({ dateParams, onError: showToast })
const dl   = useInspectorReportDownload({ onSuccess: showToast, onError: showToast })

// ── Refresh all — parallel load: dashboard + inspections together ──
async function refreshAll() {
  await Promise.all([
    dash.refreshDashboard(dateParams.value),
    list.fetchInspections(),
  ])
}

// ── Search ─────────────────────────────────────────────────
function onSearchInput(value) {
  list.searchQuery.value = value
  list.debouncedFetch()
}

// ── Date watchers ───────────────────────────────────────────
watch(dateParams, () => {
  dash.refreshDashboard(dateParams.value)
  list.fetchInspections()
}, { deep: true })

// ── Lifecycle ────────────────────────────────────────────────
onMounted(() => {
  refreshAll()
})

onUnmounted(() => {
  list.cleanup()
})
</script>

<style scoped>
.toast {
  position: fixed;
  bottom: 24px;
  right: 24px;
  padding: 12px 20px;
  border-radius: 8px;
  font-size: 13px;
  font-weight: 500;
  z-index: 9999;
  box-shadow: 0 4px 12px rgba(0,0,0,0.15);
  color: #fff;
}
.toast-success { background: #16a34a; }
.toast-error   { background: #dc2626; }
.toast-enter-active, .toast-leave-active { transition: all 0.3s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }
</style>
