<template>
  <div class="p-4 md:p-6 space-y-6 pb-28 md:pb-6">
    <!-- Header -->
    <div class="flex flex-col gap-4 md:flex-row md:items-center md:justify-between">
      <div>
        <h1 class="text-xl font-bold text-slate-900 font-heading tracking-tight">{{ $t('inspector.myReports') }}</h1>
        <p class="text-xs text-slate-500 mt-1">{{ $t('inspector.myReportsDesc') }}</p>
      </div>
      <div class="flex gap-3">
        <MobileDatePicker v-model="dateFrom" :placeholder="$t('common.from')" trigger-class="flex-1 md:flex-none md:w-40" />
        <MobileDatePicker v-model="dateTo" :placeholder="$t('common.to')" trigger-class="flex-1 md:flex-none md:w-40" />
      </div>
    </div>

    <!-- Loading -->
    <div v-if="loading" class="flex justify-center py-16">
      <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
    </div>

    <template v-else>
      <!-- KPI Cards -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4">
        <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-primary-50 flex items-center justify-center shrink-0">
              <ClipboardCheck class="w-5 h-5 text-primary-600" />
            </div>
            <div>
              <p class="text-xl font-extrabold text-slate-900 leading-none">{{ overview.total }}</p>
              <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mt-1">{{ $t('inspector.totalInspected') }}</p>
            </div>
          </div>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-emerald-50 flex items-center justify-center shrink-0">
              <TrendingUp class="w-5 h-5 text-emerald-600" />
            </div>
            <div>
              <p class="text-xl font-extrabold leading-none" :class="overview.pass_rate >= 70 ? 'text-emerald-600' : 'text-red-600'">{{ overview.pass_rate }}%</p>
              <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mt-1">{{ $t('inspector.passRate') }}</p>
            </div>
          </div>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-red-50 flex items-center justify-center shrink-0">
              <AlertTriangle class="w-5 h-5 text-red-600" />
            </div>
            <div>
              <p class="text-xl font-extrabold text-red-600 leading-none">{{ overview.critical_errors }}</p>
              <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mt-1">{{ $t('inspector.criticalErrors') }}</p>
            </div>
          </div>
        </div>
        <div class="rounded-xl bg-white border border-slate-200 p-4 shadow-sm">
          <div class="flex items-center gap-3">
            <div class="w-9 h-9 rounded-lg bg-amber-50 flex items-center justify-center shrink-0">
              <BarChart3 class="w-5 h-5 text-amber-600" />
            </div>
            <div>
              <p class="text-xl font-extrabold text-slate-900 leading-none">{{ overview.avg_score }}</p>
              <p class="text-[10px] text-slate-500 uppercase tracking-widest font-semibold mt-1">{{ $t('inspector.avgScore') }}</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Charts Row -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <!-- Timeline Chart -->
        <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
          <h3 class="text-sm font-bold text-slate-900 mb-4 font-heading">{{ $t('inspector.timeline') }}</h3>
          <div v-if="timeline.length" class="flex items-end gap-2 h-32">
            <div v-for="month in timeline" :key="month.month" class="flex-1 flex flex-col items-center gap-1">
              <div class="w-full flex flex-col items-stretch rounded-md overflow-hidden" :style="{ height: getBarHeight(month.total) + 'px' }">
                <div class="bg-emerald-400 transition-all" :style="{ flex: month.passed }"></div>
                <div class="bg-red-400 transition-all" :style="{ flex: month.failed || 0.001 }"></div>
              </div>
              <span class="text-[9px] text-slate-400 font-semibold">{{ formatMonth(month.month) }}</span>
              <span class="text-[10px] font-bold text-slate-600">{{ month.total }}</span>
            </div>
          </div>
          <div v-else class="flex items-center justify-center h-32 text-sm text-slate-400">{{ $t('common.noData') }}</div>
          <div class="flex items-center gap-4 mt-3 text-[10px] font-semibold">
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-emerald-400"></span> PASS</span>
            <span class="flex items-center gap-1.5"><span class="w-2.5 h-2.5 rounded-sm bg-red-400"></span> FAIL</span>
          </div>
        </div>

        <!-- Top Errors -->
        <div class="rounded-xl bg-white border border-slate-200 p-5 shadow-sm">
          <h3 class="text-sm font-bold text-slate-900 mb-4 font-heading">{{ $t('inspector.topErrors') }}</h3>
          <div v-if="topErrors.length" class="space-y-3">
            <div v-for="(err, idx) in topErrors" :key="idx">
              <div class="flex items-center justify-between mb-1">
                <p class="text-xs text-slate-700 font-medium truncate flex-1 mr-3">{{ err.error_content }}</p>
                <span class="text-[10px] font-bold text-slate-500 shrink-0">{{ err.count }}x</span>
              </div>
              <div class="w-full h-2 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-red-400 rounded-full transition-all" :style="{ width: getErrorBarWidth(err.count) + '%' }"></div>
              </div>
            </div>
          </div>
          <div v-else class="flex items-center justify-center h-24 text-sm text-slate-400">{{ $t('inspector.noErrors') }}</div>
        </div>
      </div>

      <!-- Inspections List -->
      <div class="rounded-xl bg-white border border-slate-200 shadow-sm">
        <div class="p-5 border-b border-slate-100">
          <h3 class="text-sm font-bold text-slate-900 mb-3 font-heading">{{ $t('inspector.allInspections') }}</h3>
          <div class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
              <Search class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-slate-400" />
              <input
                v-model="searchQuery"
                :placeholder="$t('inspector.searchCabinet')"
                class="w-full pl-10 pr-4 min-h-[48px] md:min-h-[40px] rounded-xl border border-slate-200 bg-slate-50 text-sm focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500"
              />
            </div>
            <MobileBottomSheet
              v-model="filterResult"
              :options="resultOptions"
              :label="$t('inspector.filterResult')"
              :placeholder="$t('inspector.filterResult')"
              container-class="md:w-40"
            />
          </div>
        </div>

        <!-- Desktop Table -->
        <table class="w-full text-sm hidden md:table">
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
            <tr v-for="insp in inspections" :key="insp.id" class="border-t border-slate-100 hover:bg-slate-50 transition-colors">
              <td class="px-5 py-3 font-semibold text-slate-900 font-mono text-xs">{{ insp.cabinet_code }}</td>
              <td class="px-5 py-3 text-slate-600 text-xs">{{ insp.batch_name }}</td>
              <td class="px-5 py-3">
                <span class="text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-widest"
                  :class="insp.final_result === 'PASS' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'">
                  {{ insp.final_result }}
                </span>
              </td>
              <td class="px-5 py-3 font-bold text-slate-700 text-xs">{{ insp.total_score }}</td>
              <td class="px-5 py-3 text-slate-500 text-xs">{{ formatDate(insp.created_at) }}</td>
              <td class="px-5 py-3 text-right">
                <button
                  @click="downloadReport(insp)"
                  :disabled="downloadingId === insp.id"
                  class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold transition-all"
                  :class="downloadingId === insp.id ? 'bg-slate-100 text-slate-400' : 'bg-primary-50 text-primary-700 hover:bg-primary-100'"
                >
                  <Loader2 v-if="downloadingId === insp.id" class="w-3.5 h-3.5 animate-spin" />
                  <Download v-else class="w-3.5 h-3.5" />
                  PDF
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Mobile Cards -->
        <div class="md:hidden divide-y divide-slate-100">
          <div v-for="insp in inspections" :key="'m-' + insp.id" class="p-4">
            <div class="flex items-center justify-between mb-2">
              <span class="font-bold text-slate-900 font-mono text-sm">{{ insp.cabinet_code }}</span>
              <span class="text-[10px] font-bold px-2 py-1 rounded-md uppercase tracking-widest"
                :class="insp.final_result === 'PASS' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'">
                {{ insp.final_result }}
              </span>
            </div>
            <div class="flex items-center gap-3 text-[11px] text-slate-500 mb-3">
              <span>{{ insp.batch_name }}</span>
              <span>·</span>
              <span class="font-semibold text-slate-700">{{ $t('common.score') }} {{ insp.total_score }}</span>
              <span>·</span>
              <span>{{ formatDate(insp.created_at) }}</span>
            </div>
            <button
              @click="downloadReport(insp)"
              :disabled="downloadingId === insp.id"
              class="w-full min-h-[40px] inline-flex items-center justify-center gap-1.5 rounded-lg text-xs font-bold transition-all active:scale-[0.98]"
              :class="downloadingId === insp.id ? 'bg-slate-100 text-slate-400' : 'bg-primary-50 text-primary-700 hover:bg-primary-100'"
            >
              <Loader2 v-if="downloadingId === insp.id" class="w-3.5 h-3.5 animate-spin" />
              <Download v-else class="w-3.5 h-3.5" />
              {{ $t('inspector.exportPdf') }}
            </button>
          </div>
        </div>

        <!-- Empty -->
        <div v-if="inspections.length === 0" class="flex flex-col items-center justify-center py-14 text-center">
          <div class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center mb-4">
            <FileSearch class="w-7 h-7 text-slate-400" />
          </div>
          <p class="font-semibold text-slate-800">{{ $t('inspector.noInspections') }}</p>
        </div>
      </div>
    </template>
  </div>
</template>

<script setup>
import {
  Loader2, ClipboardCheck, TrendingUp, AlertTriangle, BarChart3,
  Search, Download, FileSearch
} from 'lucide-vue-next'
import { ref, computed, watch, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import inspectorStatsService from '@/services/inspectorStatsService'
import reportService, { triggerDownload } from '@/services/reportService'
import MobileDatePicker from '@/components/common/MobileDatePicker.vue'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'

const { t } = useI18n()

const loading = ref(true)
const dateFrom = ref('')
const dateTo = ref('')
const searchQuery = ref('')
const filterResult = ref('')
const downloadingId = ref(null)

const overview = ref({ total: 0, passed: 0, failed: 0, pass_rate: 0, critical_errors: 0, avg_score: 0 })
const timeline = ref([])
const topErrors = ref([])
const inspections = ref([])

const resultOptions = computed(() => [
  { value: '', label: t('common.all') },
  { value: 'PASS', label: '✅ PASS' },
  { value: 'FAIL', label: '❌ FAIL' },
])

const dateParams = computed(() => {
  const p = {}
  if (dateFrom.value) p.from = dateFrom.value
  if (dateTo.value) p.to = dateTo.value
  return p
})

const fetchAll = async () => {
  loading.value = true
  try {
    const [ovRes, tlRes, errRes, inspRes] = await Promise.all([
      inspectorStatsService.getOverview(dateParams.value),
      inspectorStatsService.getTimeline(dateParams.value),
      inspectorStatsService.getTopErrors(dateParams.value),
      inspectorStatsService.getInspections({
        ...dateParams.value,
        search: searchQuery.value || undefined,
        result: filterResult.value || undefined,
      }),
    ])

    overview.value = ovRes.data?.data || ovRes.data || {}
    timeline.value = tlRes.data?.data || tlRes.data || []
    topErrors.value = errRes.data?.data || errRes.data || []
    inspections.value = inspRes.data?.data || inspRes.data || []
  } catch (e) {
    console.error('Failed to load inspector stats:', e)
  } finally {
    loading.value = false
  }
}

const fetchInspections = async () => {
  try {
    const res = await inspectorStatsService.getInspections({
      ...dateParams.value,
      search: searchQuery.value || undefined,
      result: filterResult.value || undefined,
    })
    inspections.value = res.data?.data || res.data || []
  } catch (e) {
    console.error('Failed to load inspections:', e)
  }
}

let searchTimer = null
watch(searchQuery, () => {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(fetchInspections, 400)
})

watch(filterResult, fetchInspections)
watch([dateFrom, dateTo], fetchAll)

const maxTimelineTotal = computed(() => Math.max(...timeline.value.map(m => m.total), 1))

const getBarHeight = (total) => {
  if (total === 0) return 4
  return Math.max(8, (total / maxTimelineTotal.value) * 100)
}

const maxErrorCount = computed(() => Math.max(...topErrors.value.map(e => e.count), 1))
const getErrorBarWidth = (count) => Math.max(8, (count / maxErrorCount.value) * 100)

const formatMonth = (monthStr) => {
  if (!monthStr) return ''
  const [, m] = monthStr.split('-')
  return `T${parseInt(m)}`
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}/${d.getFullYear()}`
}

const downloadReport = async (insp) => {
  if (!insp.id || downloadingId.value) return
  downloadingId.value = insp.id
  try {
    const { data } = await reportService.downloadInspectionReport(insp.id, 'en')
    triggerDownload(data, `bien-ban-${insp.cabinet_code}.pdf`)
  } catch (e) {
    console.error('Download failed:', e)
  } finally {
    downloadingId.value = null
  }
}

onMounted(fetchAll)
</script>
