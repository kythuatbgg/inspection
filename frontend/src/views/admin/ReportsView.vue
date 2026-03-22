<template>
  <div class="reports-page">
    <!-- Header -->
    <div class="reports-header">
      <h1 class="reports-title">{{ $t('reports.title') }}</h1>
    </div>

    <!-- Tabs -->
    <div class="tabs-container">
      <button
        v-for="tab in tabs"
        :key="tab.key"
        class="tab-btn"
        :class="{ active: activeTab === tab.key }"
        @click="activeTab = tab.key"
      >
        <component :is="tab.icon" :size="16" />
        <span>{{ $t(tab.label) }}</span>
      </button>
    </div>

    <!-- Tab: Biên bản (Reports) -->
    <div v-if="activeTab === 'reports'" class="tab-content">
      <div class="card">
        <div class="card-header">
          <div class="filters-row">
            <MobileBottomSheet
              v-model="selectedBatchId"
              :options="batches"
              :placeholder="$t('reports.selectBatchPlaceholder')"
              :label="$t('reports.title')"
              container-class="batch-select-container"
              @update:modelValue="searchReports"
            />
            <div class="search-box">
              <Search :size="14" class="search-icon" />
              <input
                v-model="searchCabinet"
                type="text"
                class="search-input"
                :placeholder="$t('reports.searchCabinetPlaceholder')"
                @input="debouncedSearch"
              />
            </div>
            <MobileBottomSheet
              v-model="reportLang"
              :options="langOptions"
              :placeholder="$t('reports.reportLang')"
              :label="$t('reports.reportLang')"
              container-class="lang-select-container"
            />
          </div>
          <div v-if="selectedBatchId" class="batch-actions">
            <button class="btn btn-primary" :disabled="downloading" @click="downloadBatchSummary">
              <FileText :size="14" />
              {{ $t('reports.batchSummary') }}
            </button>
            <button class="btn btn-accent" :disabled="downloading" @click="downloadAcceptance">
              <Award :size="14" />
              {{ $t('reports.acceptanceReport') }}
            </button>
            <button class="btn btn-warning" :disabled="downloading" @click="downloadCriticalErrors">
              <AlertTriangle :size="14" />
              {{ $t('reports.criticalErrors') }}
            </button>
          </div>
        </div>

        <div v-if="loadingSearch" class="loading-state">
          <Loader2 :size="24" class="spinner" />
          <span>{{ $t('common.loading') }}</span>
        </div>

        <!-- Desktop Table -->
        <table v-else-if="searchResults.length" class="data-table d-md-table">
          <thead>
            <tr>
              <th>#</th>
              <th>{{ $t('reports.cabinetCode') }}</th>
              <th>{{ $t('reports.btsSite') }}</th>
              <th>{{ $t('reports.result') }}</th>
              <th>{{ $t('reports.score') }}</th>
              <th>{{ $t('reports.inspectorName') }}</th>
              <th>{{ $t('reports.selectBatch') }}</th>
              <th>{{ $t('reports.actions') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(row, i) in searchResults" :key="row.id">
              <td>{{ i + 1 }}</td>
              <td class="font-mono">{{ row.cabinet_code }}</td>
              <td class="font-mono text-muted">{{ row.bts_site }}</td>
              <td>
                <span
                  class="badge"
                  :class="row.final_result === 'PASS' ? 'badge-pass' : 'badge-fail'"
                >
                  {{ row.final_result === 'PASS' ? $t('common.resultPass') : $t('common.resultFail') }}
                </span>
              </td>
              <td><strong>{{ row.total_score }}</strong></td>
              <td>{{ row.inspector_name }}</td>
              <td class="text-muted text-sm">{{ row.batch_name }}</td>
              <td>
                <button
                  class="btn btn-sm btn-outline"
                  :disabled="downloading"
                  @click="downloadInspectionReport(row.id, row.cabinet_code)"
                >
                  <Download :size="12" />
                  {{ $t('reports.exportPdf') }}
                </button>
              </td>
            </tr>
          </tbody>
        </table>

        <!-- Mobile Cards -->
        <div v-if="searchResults.length && !loadingSearch" class="mobile-list d-md-none">
          <div v-for="(row, i) in searchResults" :key="row.id" class="mobile-card">
            <div class="mc-header">
              <div class="mc-title-group">
                <div class="mc-title font-mono">{{ row.cabinet_code }}</div>
                <div class="mc-subtitle font-mono">{{ row.bts_site }}</div>
              </div>
              <span
                class="badge"
                :class="row.final_result === 'PASS' ? 'badge-pass' : 'badge-fail'"
              >
                {{ row.final_result === 'PASS' ? $t('common.resultPass') : $t('common.resultFail') }}
              </span>
            </div>
            <div class="mc-body">
              <div class="mc-row">
                <span class="mc-label">{{ $t('reports.score') }}</span>
                <span class="mc-value"><strong>{{ row.total_score }}</strong></span>
              </div>
              <div class="mc-row">
                <span class="mc-label">{{ $t('reports.inspectorName') }}</span>
                <span class="mc-value">{{ row.inspector_name }}</span>
              </div>
              <div class="mc-row">
                <span class="mc-label">{{ $t('reports.selectBatch') }}</span>
                <span class="mc-value text-muted">{{ row.batch_name }}</span>
              </div>
            </div>
            <div class="mc-actions">
              <button
                class="btn btn-sm btn-outline w-full"
                :disabled="downloading"
                @click="downloadInspectionReport(row.id, row.cabinet_code)"
              >
                <Download :size="14" />
                {{ $t('reports.exportPdf') }}
              </button>
            </div>
          </div>
        </div>

        <div v-else class="empty-state">
          <FileSearch :size="48" class="empty-icon" />
          <p>{{ searchCabinet || selectedBatchId ? $t('reports.noDataYet') : $t('reports.selectBatchForReport') }}</p>
        </div>
      </div>
    </div>

    <!-- Tab: Thống kê (Statistics) -->
    <div v-if="activeTab === 'statistics'" class="tab-content">
      <!-- KPI Cards -->
      <div class="kpi-grid">
        <div class="kpi-card">
          <div class="kpi-icon kpi-blue"><Layers :size="20" /></div>
          <div class="kpi-info">
            <span class="kpi-num">{{ stats.total_batches ?? 0 }}</span>
            <span class="kpi-label">{{ $t('reports.totalBatches') }}</span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon kpi-slate"><Box :size="20" /></div>
          <div class="kpi-info">
            <span class="kpi-num">{{ stats.total_cabinets_inspected ?? 0 }}</span>
            <span class="kpi-label">{{ $t('reports.totalInspected') }}</span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon kpi-green"><TrendingUp :size="20" /></div>
          <div class="kpi-info">
            <span class="kpi-num kpi-pass">{{ stats.pass_rate ?? 0 }}%</span>
            <span class="kpi-label">{{ $t('reports.passRate') }}</span>
          </div>
        </div>
        <div class="kpi-card">
          <div class="kpi-icon kpi-red"><AlertCircle :size="20" /></div>
          <div class="kpi-info">
            <span class="kpi-num kpi-fail">{{ stats.total_critical_errors ?? 0 }}</span>
            <span class="kpi-label">{{ $t('reports.criticalErrorCount') }}</span>
          </div>
        </div>
      </div>

      <!-- Filters -->
      <div class="card filter-card">
        <div class="filter-row">
          <div class="filter-group flex-1">
            <label>{{ $t('reports.fromDate') }}</label>
            <MobileDatePicker
              v-model="filterFrom"
              :placeholder="$t('reports.fromDate')"
              :label="$t('reports.fromDate')"
              @update:modelValue="loadStats"
            />
          </div>
          <div class="filter-group flex-1">
            <label>{{ $t('reports.toDate') }}</label>
            <MobileDatePicker
              v-model="filterTo"
              :placeholder="$t('reports.toDate')"
              :label="$t('reports.toDate')"
              @update:modelValue="loadStats"
            />
          </div>
        </div>
      </div>

      <!-- Data panels -->
      <div class="stats-grid">
        <!-- By BTS -->
        <div class="card">
          <h3 class="card-title">{{ $t('reports.byBts') }}</h3>
          <div v-if="loadingBts" class="loading-state"><Loader2 :size="18" class="spinner" /></div>
          <table v-else-if="btsData.length" class="data-table compact d-md-table">
            <thead>
              <tr>
                <th>{{ $t('reports.btsSite') }}</th>
                <th>{{ $t('reports.total') }}</th>
                <th>{{ $t('reports.passed') }}</th>
                <th>{{ $t('reports.failed') }}</th>
                <th>{{ $t('reports.passRate') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in btsData" :key="row.bts_site">
                <td class="font-mono">{{ row.bts_site }}</td>
                <td>{{ row.total }}</td>
                <td class="text-pass">{{ row.passed }}</td>
                <td class="text-fail">{{ row.failed }}</td>
                <td>
                  <div class="bar-container">
                    <div class="bar-fill" :style="{ width: row.pass_rate + '%' }"></div>
                    <span class="bar-label">{{ row.pass_rate }}%</span>
                  </div>
                </td>
              </tr>
            </tbody>
          </table>
          <div v-if="btsData.length && !loadingBts" class="mobile-list d-md-none">
            <div v-for="row in btsData" :key="row.bts_site" class="mobile-card">
              <div class="mc-header" style="margin-bottom: 8px; padding-bottom: 8px;">
                <div class="mc-title-group">
                  <div class="mc-title font-mono">{{ row.bts_site }}</div>
                </div>
                <span class="badge" :class="row.pass_rate >= 80 ? 'badge-pass' : (row.pass_rate >= 50 ? 'badge-warning' : 'badge-fail')">{{ row.pass_rate }}% pass</span>
              </div>
              <div class="mc-body" style="margin-bottom: 0;">
                <div class="mc-row">
                  <span class="mc-label">{{ $t('reports.total') }}</span>
                  <span class="mc-value">{{ row.total }}</span>
                </div>
                <div class="mc-row">
                  <span class="mc-label">{{ $t('reports.passed') }}/{{ $t('reports.failed') }}</span>
                  <span class="mc-value"><span class="text-pass">{{ row.passed }}</span> / <span class="text-fail">{{ row.failed }}</span></span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-state small"><p>{{ $t('reports.noDataYet') }}</p></div>
        </div>

        <!-- By Inspector -->
        <div class="card">
          <h3 class="card-title">{{ $t('reports.byInspector') }}</h3>
          <div v-if="loadingInspector" class="loading-state"><Loader2 :size="18" class="spinner" /></div>
          <table v-else-if="inspectorData.length" class="data-table compact d-md-table">
            <thead>
              <tr>
                <th>{{ $t('reports.inspectorName') }}</th>
                <th>{{ $t('reports.totalInspections') }}</th>
                <th>{{ $t('reports.passRate') }}</th>
                <th>{{ $t('reports.avgScore') }}</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="row in inspectorData" :key="row.inspector_id">
                <td>{{ row.inspector_name }}</td>
                <td>{{ row.total_inspections }}</td>
                <td>
                  <div class="bar-container">
                    <div class="bar-fill" :style="{ width: row.pass_rate + '%' }"></div>
                    <span class="bar-label">{{ row.pass_rate }}%</span>
                  </div>
                </td>
                <td>{{ row.avg_score }}</td>
              </tr>
            </tbody>
          </table>
          <div v-if="inspectorData.length && !loadingInspector" class="mobile-list d-md-none">
            <div v-for="row in inspectorData" :key="row.inspector_id" class="mobile-card">
              <div class="mc-header" style="margin-bottom: 8px; padding-bottom: 8px;">
                <div class="mc-title-group">
                  <div class="mc-title">{{ row.inspector_name }}</div>
                </div>
                <span class="badge badge-primary">{{ row.total_inspections }} tủ</span>
              </div>
              <div class="mc-body" style="margin-bottom: 0;">
                <div class="mc-row">
                  <span class="mc-label">{{ $t('reports.passRate') }}</span>
                  <span class="mc-value">{{ row.pass_rate }}%</span>
                </div>
                <div class="mc-row">
                  <span class="mc-label">{{ $t('reports.avgScore') }}</span>
                  <span class="mc-value"><strong>{{ row.avg_score }}</strong></span>
                </div>
              </div>
            </div>
          </div>
          <div v-else class="empty-state small"><p>{{ $t('reports.noDataYet') }}</p></div>
        </div>
      </div>

      <!-- Common Errors -->
      <div class="card" style="margin-top: 16px">
        <h3 class="card-title">{{ $t('reports.byErrorType') }}</h3>
        <div v-if="loadingErrors" class="loading-state"><Loader2 :size="18" class="spinner" /></div>
        <table v-else-if="errorData.length" class="data-table compact d-md-table">
          <thead>
            <tr>
              <th>{{ $t('reports.errorContent') }}</th>
              <th>{{ $t('reports.category') }}</th>
              <th>{{ $t('reports.count') }}</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="row in errorData" :key="row.error_content">
              <td>{{ row.error_content }}</td>
              <td><span class="badge badge-category">{{ row.category }}</span></td>
              <td class="text-fail font-bold">{{ row.count }}</td>
            </tr>
          </tbody>
        </table>
        <div v-if="errorData.length && !loadingErrors" class="mobile-list d-md-none">
          <div v-for="row in errorData" :key="row.error_content" class="mobile-card">
            <div class="mc-body" style="margin-bottom: 0; gap: 4px;">
              <div class="mc-row" style="align-items: flex-start;">
                <span class="mc-title" style="flex: 1; text-align: left; font-weight: 500;">{{ row.error_content }}</span>
                <span class="mc-value text-fail font-bold" style="font-size: 16px; min-width: 30px; text-align: right;">{{ row.count }}</span>
              </div>
              <div class="mc-row" style="margin-top: 4px;">
                <span class="badge badge-category" style="font-size: 10px;">{{ row.category }}</span>
              </div>
            </div>
          </div>
        </div>
        <div v-else class="empty-state small"><p>{{ $t('reports.noDataYet') }}</p></div>
      </div>
    </div>

    <!-- Tab: Export Excel -->
    <div v-if="activeTab === 'export'" class="tab-content">
      <div class="card export-card">
        <div class="export-grid mobile-export-grid">
          <!-- Batch Export -->
          <div class="export-item">
            <div class="export-icon"><FileSpreadsheet :size="32" /></div>
            <div class="export-info" style="width: 100%;">
              <h4>{{ $t('reports.batchExport') }}</h4>
              <MobileBottomSheet
                v-model="exportBatchId"
                :options="batches"
                :placeholder="$t('reports.selectBatchPlaceholder')"
                :label="$t('reports.batchExport')"
                container-class="w-full mt-2"
              />
            </div>
            <button
              class="btn btn-primary"
              :disabled="!exportBatchId || downloading"
              @click="downloadBatchExcel"
            >
              <Download :size="14" />
              {{ downloading ? $t('reports.downloading') : $t('reports.exportExcel') }}
            </button>
          </div>

          <!-- Statistics Export -->
          <div class="export-item">
            <div class="export-icon"><BarChart3 :size="32" /></div>
            <div class="export-info">
              <h4>{{ $t('reports.exportAll') }}</h4>
              <p class="export-desc">Overview + BTS + Inspector</p>
            </div>
            <button class="btn btn-primary" :disabled="downloading" @click="downloadStatsExcel">
              <Download :size="14" />
              {{ downloading ? $t('reports.downloading') : $t('reports.exportExcel') }}
            </button>
          </div>

          <!-- Critical Errors Export -->
          <div class="export-item">
            <div class="export-icon export-icon-red"><AlertTriangle :size="32" /></div>
            <div class="export-info" style="width: 100%;">
              <h4>{{ $t('reports.criticalErrors') }}</h4>
              <MobileBottomSheet
                v-model="exportErrorBatchId"
                :options="[{value: '', label: $t('common.all')}, ...batches.map(b => ({value: b.id, label: b.name}))]"
                :placeholder="$t('common.all')"
                :label="$t('reports.criticalErrors')"
                container-class="w-full mt-2"
              />
            </div>
            <button class="btn btn-warning" :disabled="downloading" @click="downloadCriticalErrorsExcel">
              <Download :size="14" />
              {{ downloading ? $t('reports.downloading') : $t('reports.exportExcel') }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Toast -->
    <Transition name="toast">
      <div v-if="toast.show" class="toast" :class="'toast-' + toast.type">
        {{ toast.message }}
      </div>
    </Transition>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { useI18n } from 'vue-i18n'
import {
  FileText, FileSearch, FileSpreadsheet, Download, Loader2,
  AlertTriangle, AlertCircle, Award, Layers, Box,
  TrendingUp, BarChart3, Search
} from 'lucide-vue-next'
import reportService, { triggerDownload } from '@/services/reportService'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'
import MobileDatePicker from '@/components/common/MobileDatePicker.vue'
import api from '@/services/api'

const { t } = useI18n()

const tabs = [
  { key: 'reports', label: 'reports.tabReports', icon: FileText },
  { key: 'statistics', label: 'reports.tabStatistics', icon: BarChart3 },
  { key: 'export', label: 'reports.tabExport', icon: FileSpreadsheet },
]

// State
const activeTab = ref('reports')
const batches = ref([])
const selectedBatchId = ref('')
const searchCabinet = ref('')
const reportLang = ref('en')
const downloading = ref(false) // Moved here from original position
const searchResults = ref([]) // Moved here from original position

// Computed
const langOptions = computed(() => [
  { value: 'en', label: '🇬🇧 English' },
  { value: 'vn', label: '🇻🇳 Tiếng Việt' },
  { value: 'kh', label: '🇰🇭 ភាសាខ្មែរ' }
])
const loadingSearch = ref(false)
let searchTimer = null

// Statistics tab
const stats = ref({})
const btsData = ref([])
const inspectorData = ref([])
const errorData = ref([])
const filterFrom = ref('')
const filterTo = ref('')
const loadingBts = ref(false)
const loadingInspector = ref(false)
const loadingErrors = ref(false)

// Export tab
const exportBatchId = ref('')
const exportErrorBatchId = ref('')

// Toast
const toast = ref({ show: false, message: '', type: 'success' })

function showToast(message, type = 'success') {
  toast.value = { show: true, message, type }
  setTimeout(() => { toast.value.show = false }, 3000)
}

async function loadBatches() {
  try {
    const { data } = await api.get('/batches')
    batches.value = data.data || data
  } catch {
    showToast(t('common.errorLoadData'), 'error')
  }
}

async function searchReports() {
  loadingSearch.value = true
  try {
    const params = {}
    if (selectedBatchId.value) params.batch_id = selectedBatchId.value
    if (searchCabinet.value.trim()) params.cabinet_code = searchCabinet.value.trim()
    const { data } = await reportService.searchInspections(params)
    searchResults.value = data.data || []
  } catch {
    showToast(t('common.errorLoadData'), 'error')
  } finally {
    loadingSearch.value = false
  }
}

function debouncedSearch() {
  clearTimeout(searchTimer)
  searchTimer = setTimeout(() => searchReports(), 300)
}

async function loadStats() {
  const params = {}
  if (filterFrom.value) params.from = filterFrom.value
  if (filterTo.value) params.to = filterTo.value

  try {
    const [overview, bts, inspector, errors] = await Promise.all([
      reportService.getOverview(params),
      (loadingBts.value = true, reportService.getByBts(params)),
      (loadingInspector.value = true, reportService.getByInspector(params)),
      (loadingErrors.value = true, reportService.getByErrorType(params)),
    ])
    stats.value = overview.data.data || {}
    btsData.value = bts.data.data || []
    inspectorData.value = inspector.data.data || []
    errorData.value = errors.data.data || []
  } catch {
    showToast(t('common.errorLoadData'), 'error')
  } finally {
    loadingBts.value = false
    loadingInspector.value = false
    loadingErrors.value = false
  }
}

// Downloads
async function downloadInspectionReport(inspectionId, cabinetCode = '') {
  downloading.value = true
  try {
    const { data } = await reportService.downloadInspectionReport(inspectionId, reportLang.value)
    const filename = cabinetCode ? `inspection-${cabinetCode}.pdf` : `inspection-report-${inspectionId}.pdf`
    triggerDownload(data, filename)
    showToast(t('reports.downloadSuccess'))
  } catch {
    showToast(t('reports.downloadError'), 'error')
  } finally {
    downloading.value = false
  }
}

async function downloadBatchSummary() {
  downloading.value = true
  try {
    const { data } = await reportService.downloadBatchSummary(selectedBatchId.value)
    triggerDownload(data, `tong-ket-dot-${selectedBatchId.value}.pdf`)
    showToast(t('reports.downloadSuccess'))
  } catch {
    showToast(t('reports.downloadError'), 'error')
  } finally {
    downloading.value = false
  }
}

async function downloadAcceptance() {
  downloading.value = true
  try {
    const { data } = await reportService.downloadAcceptance(selectedBatchId.value)
    triggerDownload(data, `bien-ban-nghiem-thu-${selectedBatchId.value}.pdf`)
    showToast(t('reports.downloadSuccess'))
  } catch {
    showToast(t('reports.downloadError'), 'error')
  } finally {
    downloading.value = false
  }
}

async function downloadCriticalErrors() {
  downloading.value = true
  try {
    const { data } = await reportService.downloadCriticalErrors({ batch_id: selectedBatchId.value })
    triggerDownload(data, 'bao-cao-loi-critical.pdf')
    showToast(t('reports.downloadSuccess'))
  } catch {
    showToast(t('reports.downloadError'), 'error')
  } finally {
    downloading.value = false
  }
}

async function downloadBatchExcel() {
  downloading.value = true
  try {
    const { data } = await reportService.downloadBatchExport(exportBatchId.value)
    triggerDownload(data, `thong-ke-dot-${exportBatchId.value}.xlsx`)
    showToast(t('reports.downloadSuccess'))
  } catch {
    showToast(t('reports.downloadError'), 'error')
  } finally {
    downloading.value = false
  }
}

async function downloadStatsExcel() {
  downloading.value = true
  try {
    const params = {}
    if (filterFrom.value) params.from = filterFrom.value
    if (filterTo.value) params.to = filterTo.value
    const { data } = await reportService.downloadStatisticsExport(params)
    triggerDownload(data, 'thong-ke-tong-hop.xlsx')
    showToast(t('reports.downloadSuccess'))
  } catch {
    showToast(t('reports.downloadError'), 'error')
  } finally {
    downloading.value = false
  }
}

async function downloadCriticalErrorsExcel() {
  downloading.value = true
  try {
    const params = { format: 'xlsx' }
    if (exportErrorBatchId.value) params.batch_id = exportErrorBatchId.value
    const { data } = await reportService.downloadCriticalErrors(params)
    triggerDownload(data, 'loi-critical.xlsx')
    showToast(t('reports.downloadSuccess'))
  } catch {
    showToast(t('reports.downloadError'), 'error')
  } finally {
    downloading.value = false
  }
}

onMounted(() => {
  loadBatches()
  loadStats()
})
</script>

<style scoped>
.reports-page {
  padding: 24px;
  max-width: 1200px;
  margin: 0 auto;
}

.reports-header {
  margin-bottom: 20px;
}

.reports-title {
  font-size: 22px;
  font-weight: 700;
  color: var(--color-text, #1e293b);
}

/* Tabs */
.tabs-container {
  display: flex;
  gap: 4px;
  border-bottom: 2px solid #e2e8f0;
  margin-bottom: 20px;
}

.tab-btn {
  display: flex;
  align-items: center;
  gap: 6px;
  padding: 10px 18px;
  border: none;
  background: none;
  color: #64748b;
  font-size: 13px;
  font-weight: 500;
  cursor: pointer;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  transition: all 0.2s;
}

.tab-btn:hover { color: #334155; }
.tab-btn.active {
  color: #2563eb;
  border-bottom-color: #2563eb;
}

/* Card */
.card {
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 20px;
}

.card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 12px;
  margin-bottom: 16px;
}

.card-title {
  font-size: 14px;
  font-weight: 600;
  color: #334155;
  margin-bottom: 14px;
}

/* Filters */
.filters-row { display: flex; gap: 10px; flex-wrap: wrap; align-items: center; }

.search-box {
  position: relative;
  flex: 1;
  min-width: 200px;
  max-width: 320px;
}
.search-icon {
  position: absolute;
  left: 10px;
  top: 50%;
  transform: translateY(-50%);
  color: #94a3b8;
  pointer-events: none;
}
.search-input {
  width: 100%;
  padding: 8px 12px 8px 32px;
  border: 1px solid #d1d5db;
  border-radius: 8px;
  font-size: 14px;
  color: #334155;
  background: #fff;
  transition: border-color 0.15s;
}
@media (max-width: 768px) {
  .search-input {
    min-height: 52px;
    border-radius: 16px;
    background: rgba(248, 250, 252, 0.8);
    border-color: transparent;
  }
}
.search-input:focus {
  outline: none;
  border-color: #2563eb;
  box-shadow: 0 0 0 2px rgba(37, 99, 235, 0.1);
}
.search-input::placeholder { color: #94a3b8; }

.batch-select-container { min-width: 200px; }
.lang-select-container { min-width: 160px; max-width: 180px; }

/* Filter & Desktop/Mobile Visibility */
.d-md-none { display: none !important; }
.d-md-table { display: table; }

@media (max-width: 768px) {
  .d-md-none { display: block !important; }
  .d-md-table { display: none !important; }
  
  .filters-row { flex-direction: column; gap: 8px; }
  .batch-select-container, .search-box, .lang-select-container { width: 100%; max-width: 100%; }
  
  .batch-actions { flex-direction: column; gap: 8px; }
  .batch-actions .btn { width: 100%; justify-content: center; }
  
  .w-full { width: 100%; justify-content: center; }
}

/* Mobile Cards */
.mobile-list {
  display: flex;
  flex-direction: column;
  gap: 12px;
  padding: 8px 0;
}

.mobile-card {
  background: white;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 12px;
  box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
}

.mc-header {
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
  margin-bottom: 12px;
  padding-bottom: 12px;
  border-bottom: 1px dashed #e2e8f0;
}

.mc-title-group {
  display: flex;
  flex-direction: column;
  gap: 4px;
}

.mc-title {
  font-weight: 600;
  color: #0f172a;
  font-size: 14px;
}

.mc-subtitle {
  font-size: 12px;
  color: #64748b;
}

.mc-body {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-bottom: 12px;
  font-size: 13px;
}

.mc-row {
  display: flex;
  justify-content: space-between;
}

.mc-label {
  color: #64748b;
}

.mc-value {
  color: #1e293b;
  text-align: right;
}

.mc-actions {
  display: flex;
  gap: 8px;
}

.filter-card { margin-bottom: 16px; }
.filter-row { display: flex; gap: 16px; flex-wrap: wrap; }
.filter-group label {
  display: block;
  font-size: 11px;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 4px;
  text-transform: uppercase;
  letter-spacing: 0.5px;
}
.input-date {
  padding: 7px 10px;
  border: 1px solid #d1d5db;
  border-radius: 6px;
  font-size: 13px;
}

/* Batch actions */
.batch-actions { display: flex; gap: 8px; flex-wrap: wrap; }

/* Buttons */
.btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 8px 14px;
  border: none;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  cursor: pointer;
  transition: all 0.15s;
  white-space: nowrap;
}
.btn:disabled { opacity: 0.5; cursor: not-allowed; }
.btn-primary { background: #2563eb; color: #fff; }
.btn-primary:hover:not(:disabled) { background: #1d4ed8; }
.btn-accent { background: #0891b2; color: #fff; }
.btn-accent:hover:not(:disabled) { background: #0e7490; }
.btn-warning { background: #d97706; color: #fff; }
.btn-warning:hover:not(:disabled) { background: #b45309; }
.btn-outline {
  background: transparent;
  border: 1px solid #d1d5db;
  color: #475569;
}
.btn-outline:hover:not(:disabled) { background: #f1f5f9; }
.btn-sm { padding: 4px 10px; font-size: 11px; }

/* Table */
.data-table {
  width: 100%;
  border-collapse: collapse;
  font-size: 13px;
}
.data-table th {
  background: #f8fafc;
  font-weight: 600;
  text-align: left;
  padding: 10px 12px;
  border-bottom: 2px solid #e2e8f0;
  color: #475569;
  font-size: 11px;
  text-transform: uppercase;
  letter-spacing: 0.3px;
}
.data-table td {
  padding: 10px 12px;
  border-bottom: 1px solid #f1f5f9;
  color: #334155;
}
.data-table tr:hover { background: #f8fafc; }
.data-table.compact th { padding: 8px 10px; }
.data-table.compact td { padding: 7px 10px; font-size: 12px; }

/* Badges */
.badge {
  display: inline-block;
  padding: 2px 8px;
  border-radius: 4px;
  font-size: 11px;
  font-weight: 700;
  letter-spacing: 0.3px;
}
.badge-pass { background: #dcfce7; color: #16a34a; }
.badge-fail { background: #fee2e2; color: #dc2626; }
.badge-pending { background: #f1f5f9; color: #94a3b8; }
.badge-category { background: #eff6ff; color: #2563eb; font-weight: 500; }

/* KPI Grid */
.kpi-grid {
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 16px;
  margin-bottom: 16px;
}
.kpi-card {
  display: flex;
  align-items: center;
  gap: 14px;
  background: #fff;
  border: 1px solid #e2e8f0;
  border-radius: 8px;
  padding: 18px;
}
.kpi-icon {
  width: 42px;
  height: 42px;
  display: flex;
  align-items: center;
  justify-content: center;
  border-radius: 8px;
  flex-shrink: 0;
}
.kpi-blue { background: #eff6ff; color: #2563eb; }
.kpi-slate { background: #f1f5f9; color: #475569; }
.kpi-green { background: #f0fdf4; color: #16a34a; }
.kpi-red { background: #fef2f2; color: #dc2626; }
.kpi-info { display: flex; flex-direction: column; }
.kpi-num {
  font-size: 22px;
  font-weight: 800;
  color: #1e293b;
  line-height: 1.1;
}
.kpi-num.kpi-pass { color: #16a34a; }
.kpi-num.kpi-fail { color: #dc2626; }
.kpi-label {
  font-size: 11px;
  color: #64748b;
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 500;
}

/* Stats grid */
.stats-grid {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 16px;
}

/* Bar chart */
.bar-container {
  position: relative;
  height: 20px;
  background: #f1f5f9;
  border-radius: 4px;
  min-width: 80px;
  overflow: hidden;
}
.bar-fill {
  height: 100%;
  background: linear-gradient(90deg, #22c55e, #16a34a);
  border-radius: 4px;
  transition: width 0.4s ease;
}
.bar-label {
  position: absolute;
  right: 6px;
  top: 50%;
  transform: translateY(-50%);
  font-size: 10px;
  font-weight: 700;
  color: #334155;
}

/* Empty & Loading */
.empty-state {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 48px 16px;
  color: #94a3b8;
}
.empty-state.small { padding: 24px; }
.empty-icon { color: #cbd5e1; margin-bottom: 12px; }
.loading-state {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  padding: 24px;
  color: #64748b;
  font-size: 13px;
}

/* Export card */
.export-grid {
  display: flex;
  flex-direction: column;
  gap: 16px;
}
.export-item {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px;
  background: #f8fafc;
  border-radius: 8px;
  border: 1px solid #e2e8f0;
}
.export-icon {
  width: 56px;
  height: 56px;
  display: flex;
  align-items: center;
  justify-content: center;
  background: #eff6ff;
  color: #2563eb;
  border-radius: 10px;
  flex-shrink: 0;
}
.export-icon-red { background: #fef2f2; color: #dc2626; }
.export-info { flex: 1; }
.export-info h4 {
  font-size: 14px;
  font-weight: 600;
  color: #1e293b;
  margin-bottom: 6px;
}
.export-desc { font-size: 12px; color: #64748b; }

/* Utilities */
.font-mono { font-family: 'SF Mono', 'Monaco', 'Consolas', monospace; font-size: 12px; }
.font-bold { font-weight: 700; }
.text-muted { color: #94a3b8; }
.text-sm { font-size: 12px; }
.text-pass { color: #16a34a; font-weight: 600; }
.text-fail { color: #dc2626; font-weight: 600; }

@keyframes spin { to { transform: rotate(360deg); } }
.spinner { animation: spin 0.8s linear infinite; }

/* Toast */
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
}
.toast-success { background: #16a34a; color: #fff; }
.toast-error { background: #dc2626; color: #fff; }
.toast-enter-active, .toast-leave-active { transition: all 0.3s; }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(10px); }

/* Responsive */
@media (max-width: 768px) {
  .reports-page { padding: 16px; }
  .kpi-grid { grid-template-columns: repeat(2, 1fr); gap: 10px; }
  .stats-grid { grid-template-columns: 1fr; }
  .card-header { flex-direction: column; align-items: flex-start; }
  .batch-actions { width: 100%; }
  .batch-actions .btn { flex: 1; justify-content: center; }
  .export-item { flex-direction: column; text-align: center; }
  .export-item .btn { width: 100%; justify-content: center; }
  .tabs-container { overflow-x: auto; }
}
</style>
