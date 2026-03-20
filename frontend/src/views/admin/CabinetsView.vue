<template>
  <div class="space-y-4 md:space-y-6 pb-24 md:pb-0">
    <!-- DESKTOP HEADER -->
    <div class="hidden md:flex items-center justify-between">
      <h2 class="text-xl font-bold text-gray-100">Danh sách tủ cáp</h2>
      <div class="flex gap-3">
        <div class="flex rounded-xl overflow-hidden bg-dark-elevated p-1">
          <button
            @click="viewMode = 'list'"
            class="px-4 py-2 min-h-[44px] rounded-lg items-center text-sm font-semibold transition-all"
            :class="viewMode === 'list' ? 'bg-dark-surface text-primary-400 shadow-lg shadow-black/10' : 'text-gray-500 hover:text-gray-300'"
          >
            <div class="flex items-center">
              <Menu class="w-4 h-4 mr-2" />
              List
            </div>
          </button>
          <button
            @click="viewMode = 'map'"
            class="px-4 py-2 min-h-[44px] rounded-lg items-center text-sm font-semibold transition-all"
            :class="viewMode === 'map' ? 'bg-dark-surface text-primary-400 shadow-lg shadow-black/10' : 'text-gray-500 hover:text-gray-300'"
          >
            <div class="flex items-center">
              <MapPin class="w-4 h-4 mr-2" />
              Map
            </div>
          </button>
        </div>

        <button @click="exportCabinets" class="bg-dark-surface border border-gray-700/50 hover:bg-dark-bg text-gray-300 rounded-xl min-h-[52px] px-5 flex items-center gap-2 text-sm font-semibold transition-colors">
          <ArrowUpToLine class="w-5 h-5 text-gray-500" />
          Export
        </button>

        <button @click="openImportModal" class="bg-dark-surface border border-gray-700/50 hover:bg-dark-bg text-gray-300 rounded-xl min-h-[52px] px-5 flex items-center gap-2 text-sm font-semibold transition-colors">
          <ArrowDownToLine class="w-5 h-5 text-gray-500" />
          Import
        </button>

        <button @click="openAddModal" class="bg-primary-500 hover:bg-primary-400 text-white rounded-xl min-h-[52px] px-6 flex items-center gap-2 text-sm font-semibold transition-colors shadow-lg shadow-black/10 shadow-primary-500/20">
          <Plus class="w-5 h-5" />
          Thêm tủ
        </button>
      </div>
    </div>

    <!-- MOBILE HEADER -->
    <div class="flex flex-col md:hidden gap-4">
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-bold text-gray-100 tracking-tight">Tủ cáp</h2>
        
        <!-- Toggle View Mode Pill -->
        <div class="flex bg-dark-elevated/80 backdrop-blur rounded-[16px] p-1 shadow-inner">
          <button
            @click="viewMode = 'list'"
            class="w-12 h-10 flex items-center justify-center rounded-[12px] transition-all duration-200"
            :class="viewMode === 'list' ? 'bg-dark-surface shadow-lg shadow-black/10 text-primary-400' : 'text-gray-500 active:bg-gray-700/50'"
          >
            <Menu class="w-5 h-5" />
          </button>
          <button
            @click="viewMode = 'map'"
            class="w-12 h-10 flex items-center justify-center rounded-[12px] transition-all duration-200"
            :class="viewMode === 'map' ? 'bg-dark-surface shadow-lg shadow-black/10 text-primary-400' : 'text-gray-500 active:bg-gray-700/50'"
          >
            <MapPin class="w-5 h-5" />
          </button>
        </div>
      </div>

      <!-- Action Buttons -->
      <div class="flex gap-3">
        <button @click="openImportModal" class="flex-1 min-h-[48px] bg-dark-surface border border-gray-700/50 active:bg-dark-bg rounded-[16px] text-gray-300 font-semibold text-sm flex items-center justify-center gap-2 shadow-lg shadow-black/10">
          <ArrowDownToLine class="w-5 h-5 text-blue-500" />
          Nhập Excel
        </button>
        <button @click="exportCabinets" class="flex-1 min-h-[48px] bg-dark-surface border border-gray-700/50 active:bg-dark-bg rounded-[16px] text-gray-300 font-semibold text-sm flex items-center justify-center gap-2 shadow-lg shadow-black/10">
          <ArrowUpToLine class="w-5 h-5 text-green-500" />
          Xuất dữ liệu
        </button>
      </div>
    </div>

    <!-- Search Bar -->
    <div class="bg-dark-surface rounded-[20px] md:rounded-xl shadow-lg shadow-black/10 border border-gray-700/30 md:border-gray-700/50 p-3 md:p-4 flex flex-col md:flex-row gap-4 items-center justify-between">
      <div class="relative w-full md:max-w-md">
        <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
          <Search class="w-5 h-5 text-gray-500" />
        </div>
        <input
          v-model="filters.search"
          type="text"
          placeholder="Tìm mã tủ hoặc BTS site..."
          class="w-full pl-11 pr-4 py-3 min-h-[52px] bg-dark-elevated/80 md:bg-dark-surface border-transparent md:border-gray-600 focus:bg-dark-surface focus:border-primary-500 rounded-[16px] md:rounded-xl text-gray-100 border focus:ring-2 focus:ring-primary-500/20 outline-none transition-all placeholder:text-gray-500 font-medium"
          @input="handleSearch"
        />
      </div>

      <!-- Desktop Per Page Selector -->
      <div class="hidden md:flex shrink-0 whitespace-nowrap items-center gap-2 text-sm text-gray-500 font-medium bg-gray-50/50 p-1.5 rounded-xl border border-gray-700/50/60">
        <label class="pl-2">Hiển thị:</label>
        <MobileBottomSheet
          :model-value="pagination.per_page"
          :options="perPageOptions"
          label="Số mục mỗi trang"
          placeholder="10"
          container-class="w-20"
          trigger-class="!min-h-[36px] border-none shadow-lg shadow-black/10 !py-1 !px-3 !rounded-lg text-sm bg-dark-surface"
          @update:model-value="(val) => { pagination.per_page = Number(val); changePerPage() }"
        />
        <span class="pr-2 text-gray-500">/ trang</span>
      </div>
    </div>

    <div v-if="loading" class="text-center text-gray-500 py-8">
      Đang tải...
    </div>

    <div v-else-if="error" class="text-center text-red-500 py-8">
      {{ error }}
    </div>

    <div v-else class="space-y-4">
      <!-- Desktop Table Card -->
      <div v-show="viewMode === 'list'" class="space-y-4">
        <div class="hidden lg:block card overflow-hidden">
        <table class="w-full">
          <thead class="bg-dark-bg border-b border-gray-700/50">
            <tr>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Mã tủ</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">BTS Site</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Tọa độ</th>
              <th class="px-4 py-3 text-left text-sm font-semibold text-gray-300">Ghi chú</th>
              <th class="px-4 py-3 text-right text-sm font-semibold text-gray-300">Thao tác</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-gray-100">
            <tr v-for="cabinet in cabinets" :key="cabinet.cabinet_code" class="hover:bg-dark-bg">
              <td class="px-4 py-3">
                <router-link :to="'/cabinets/' + cabinet.cabinet_code" class="font-medium text-gray-100 hover:text-primary-400">
                  {{ cabinet.cabinet_code }}
                </router-link>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500">{{ cabinet.bts_site }}</td>
              <td class="px-4 py-3 text-sm text-gray-500">
                <div class="flex items-center gap-2">
                  <span>{{ cabinet.lat }}, {{ cabinet.lng }}</span>
                  <a
                    v-if="cabinet.lat && cabinet.lng"
                    :href="`https://www.google.com/maps/dir/?api=1&destination=${cabinet.lat},${cabinet.lng}`"
                    target="_blank"
                    rel="noopener"
                    title="Mở Google Maps"
                    class="inline-flex items-center justify-center p-1.5 bg-blue-500/10 text-blue-400 hover:bg-blue-500/15 rounded-lg transition-colors"
                  >
                    <Navigation class="w-4 h-4" />
                  </a>
                </div>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500 truncate max-w-[200px]">{{ cabinet.note || '-' }}</td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-2">
                  <button @click.stop="openEditModal(cabinet)" class="p-2 hover:bg-dark-elevated rounded-lg min-h-[44px] min-w-[44px] flex items-center justify-center" title="Sửa">
                    <FileEdit class="w-5 h-5 text-gray-500" />
                  </button>
                  <button @click.stop="deleteCabinet(cabinet)" class="p-2 hover:bg-red-500/10 rounded-lg min-h-[44px] min-w-[44px] flex items-center justify-center" title="Xóa">
                    <Trash2 class="w-5 h-5 text-red-600" />
                  </button>
                </div>
              </td>
            </tr>
          </tbody>
        </table>

        <div v-if="cabinets.length === 0" class="text-center text-gray-500 py-8">
          Không có tủ cáp nào
        </div>
        </div>

      <div class="lg:hidden flex flex-col gap-4">
        <div
          v-for="cabinet in cabinets"
          :key="cabinet.cabinet_code"
          class="bg-dark-surface rounded-[24px] p-5 shadow-lg shadow-black/10 border border-gray-700/30 relative overflow-hidden active:scale-[0.98] transition-all duration-200"
        >
          <router-link :to="'/cabinets/' + cabinet.cabinet_code" class="block">
            <!-- Card Header -->
            <div class="flex items-start gap-4 mb-4">
              <div class="flex-shrink-0 w-12 h-12 rounded-[16px] bg-primary-500/10 flex items-center justify-center">
                <Server class="w-6 h-6 text-primary-400" />
              </div>
              <div class="flex-1 pt-1">
                <h3 class="text-lg font-bold text-gray-100 tracking-tight">{{ cabinet.cabinet_code }}</h3>
                <p class="text-sm font-medium text-gray-500 flex items-center gap-1 mt-0.5">
                  <MapPin class="w-4 h-4" />
                  {{ cabinet.bts_site }}
                </p>
              </div>
            </div>
            
            <!-- Details -->
            <div class="bg-dark-elevated/80 rounded-[16px] p-3.5 mb-4 space-y-2.5">
              <div class="flex items-center justify-between text-sm">
                <div class="flex items-center">
                  <span class="text-gray-500 font-medium w-20">Tọa độ:</span>
                  <span class="text-gray-100 font-semibold">{{ cabinet.lat }}, {{ cabinet.lng }}</span>
                </div>
                <a
                  v-if="cabinet.lat && cabinet.lng"
                  :href="`https://www.google.com/maps/dir/?api=1&destination=${cabinet.lat},${cabinet.lng}`"
                  target="_blank"
                  rel="noopener"
                  class="flex items-center justify-center p-2 rounded-xl bg-blue-500/10 text-blue-400 active:bg-blue-500/15 transition-colors"
                  @click.stop
                >
                  <Navigation class="w-4 h-4" />
                </a>
              </div>
              <div class="flex items-start text-sm" v-if="cabinet.note">
                <span class="text-gray-500 font-medium w-20 pt-0.5">Ghi chú:</span>
                <span class="text-gray-300 leading-relaxed max-w-[calc(100%-5rem)] line-clamp-2">{{ cabinet.note }}</span>
              </div>
            </div>
          </router-link>

          <!-- Card Actions -->
          <div class="flex items-center gap-3 pt-1">
            <button @click.stop="openEditModal(cabinet)" class="flex-1 min-h-[48px] bg-dark-surface border border-gray-700/50 rounded-[14px] text-gray-300 font-semibold text-sm flex items-center justify-center gap-2 active:bg-dark-bg transition-colors">
              <FileEdit class="w-5 h-5 text-gray-500" />
              Sửa
            </button>
            <button @click.stop="deleteCabinet(cabinet)" class="flex-1 min-h-[48px] bg-red-500/10/50 hover:bg-red-500/10 border border-red-500/20 rounded-[14px] text-red-600 font-semibold text-sm flex items-center justify-center gap-2 active:bg-red-500/15 transition-colors">
              <Trash2 class="w-5 h-5" />
              Xóa
            </button>
          </div>
        </div>

        <div v-if="cabinets.length === 0" class="flex flex-col items-center justify-center py-12 px-4 bg-dark-surface rounded-[24px] border border-gray-700/30 shadow-lg shadow-black/10 mt-4">
          <FileStack class="w-16 h-16 text-gray-300 mb-4" />
          <p class="text-gray-500 font-medium text-center">Không có tủ cáp nào<br/><span class="text-sm text-gray-500 font-normal">Thêm tủ mới hoặc thay đổi bộ lọc</span></p>
        </div>
      </div>
      </div>
      
      <!-- MAP VIEW -->
      <div v-if="mapEverOpened" v-show="viewMode === 'map'" class="card p-1 md:p-4">
        <CabinetMap ref="cabinetMapRef" :cabinets="cabinets" class="h-[calc(100vh-280px)] min-h-[400px] w-full rounded-lg" />
      </div>

      <!-- WEB VIEW PAGINATION -->
      <div v-if="pagination.last_page > 1" class="hidden md:flex bg-dark-surface rounded-xl shadow-lg shadow-black/10 border border-gray-700/50 p-6 mt-6 items-center justify-between">
        <!-- Info -->
        <p class="text-sm text-gray-500 font-medium">
          Hiển thị <span class="font-semibold text-gray-100">{{ pagination.from }}</span> - 
          <span class="font-semibold text-gray-100">{{ pagination.to }}</span> trong 
          <span class="font-semibold text-gray-100">{{ pagination.total }}</span> tủ cáp
        </p>

        <!-- Pagination Controls -->
        <div class="flex items-center gap-1.5">
          <!-- First/Prev -->
          <button
            @click="goToPage(1)"
            :disabled="pagination.current_page === 1"
            class="flex p-2 min-h-[40px] min-w-[40px] rounded-lg border border-gray-700/50 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-dark-bg text-gray-500 transition-colors items-center justify-center"
            title="Trang đầu"
          >
            <ChevronsLeft class="w-4 h-4" />
          </button>
          <button
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="p-2 min-h-[40px] min-w-[40px] rounded-lg border border-gray-700/50 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-dark-bg text-gray-500 transition-colors flex items-center justify-center"
            title="Trang trước"
          >
            <ChevronLeft class="w-4 h-4" />
          </button>

          <!-- Page Numbers -->
          <div class="flex items-center gap-1.5 mx-2">
            <template v-for="page in visiblePages" :key="page">
              <button
                @click="goToPage(page)"
                class="min-h-[40px] min-w-[40px] px-3 rounded-lg text-sm font-semibold transition-all duration-200"
                :class="page === pagination.current_page
                  ? 'bg-primary-500 text-white shadow-md transform'
                  : 'border border-gray-700/50 hover:bg-dark-bg text-gray-300 hover:text-primary-400 hover:border-primary-200'"
              >
                {{ page }}
              </button>
            </template>
          </div>

          <!-- Next/Last -->
          <button
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="p-2 min-h-[40px] min-w-[40px] rounded-lg border border-gray-700/50 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-dark-bg text-gray-500 transition-colors flex items-center justify-center"
            title="Trang sau"
          >
            <ChevronRight class="w-4 h-4" />
          </button>
          <button
            @click="goToPage(pagination.last_page)"
            :disabled="pagination.current_page === pagination.last_page"
            class="flex p-2 min-h-[40px] min-w-[40px] rounded-lg border border-gray-700/50 disabled:opacity-40 disabled:cursor-not-allowed hover:bg-dark-bg text-gray-500 transition-colors items-center justify-center"
            title="Trang cuối"
          >
            <ChevronsRight class="w-4 h-4" />
          </button>
        </div>
      </div>

      <!-- MOBILE VIEW PAGINATION (PRO MAX) -->
      <div v-if="pagination.last_page > 1" class="block md:hidden mt-8 mb-24">
        <!-- Status text -->
        <div class="text-center mb-5">
          <div class="text-sm text-gray-500 font-medium">
            Tủ cáp <span class="text-gray-100 font-bold">{{ pagination.from }}</span> - <span class="text-gray-100 font-bold">{{ pagination.to }}</span><br/>
            <span class="text-xs text-gray-500 mt-1 inline-block">trong tổng số {{ pagination.total }} tủ</span>
          </div>
        </div>
        
        <!-- Modern Pill Pagination -->
        <div class="flex items-center justify-between bg-dark-surface p-2 rounded-[24px] shadow-lg shadow-black/10 border border-gray-700/30 mx-auto max-w-[320px]">
          <button 
            @click="goToPage(pagination.current_page - 1)"
            :disabled="pagination.current_page === 1"
            class="group flex items-center justify-center w-14 h-14 rounded-full bg-dark-elevated/80 text-gray-500 active:bg-dark-elevated active:scale-95 transition-all duration-200 disabled:opacity-40 disabled:active:scale-100 disabled:bg-transparent"
          >
            <ChevronLeft class="w-6 h-6 group-active:-translate-x-1 transition-transform" />
          </button>

          <div class="flex-1 flex flex-col items-center justify-center">
            <div class="flex items-baseline gap-1.5">
              <span class="text-xl font-bold tracking-tight text-gray-100">{{ pagination.current_page }}</span>
              <span class="text-sm font-semibold text-gray-500">/ {{ pagination.last_page }}</span>
            </div>
          </div>

          <button 
            @click="goToPage(pagination.current_page + 1)"
            :disabled="pagination.current_page === pagination.last_page"
            class="group flex items-center justify-center w-14 h-14 rounded-full text-white bg-primary-500 shadow-lg shadow-primary-500/30 active:bg-primary-700 active:scale-95 transition-all duration-200 disabled:opacity-50 disabled:active:scale-100 disabled:shadow-none"
          >
            <ChevronRight class="w-6 h-6 group-active:translate-x-1 transition-transform" />
          </button>
        </div>
      </div>
    </div>

    <!-- Floating Action Button for Add (Mobile) -->
    <button 
      @click="openAddModal" 
      class="md:hidden fixed bottom-6 right-6 w-14 h-14 bg-primary-500 shadow-lg shadow-primary-500/40 text-white rounded-[20px] flex items-center justify-center z-40 active:scale-90 active:bg-primary-700 transition-all duration-200"
    >
      <Plus class="w-7 h-7" />
    </button>

    <div v-if="showImportModal" class="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div class="absolute inset-0 bg-black/50" @click="closeImportModal"></div>
      <div class="relative bg-dark-surface rounded-xl shadow-xl w-full max-w-lg p-6">
        <h3 class="text-lg font-semibold text-gray-100 mb-4">Import tủ cáp</h3>

        <div class="mb-4">
          <button @click="downloadTemplate" class="text-blue-400 hover:text-blue-300 text-sm flex items-center gap-2">
            <FileDown class="w-4 h-4" />
            Tải file mẫu Excel
          </button>
        </div>

        <div class="mb-4">
          <label class="block text-sm font-medium text-gray-300 mb-2">Chọn file (CSV/Excel)</label>
          <input
            ref="fileInput"
            type="file"
            accept=".csv,.xlsx,.xls"
            @change="handleFileSelect"
            class="input-field min-h-[56px]"
          />
        </div>

        <div v-if="importing" class="mb-4">
          <div class="flex justify-between text-sm text-gray-500 mb-1">
            <span>Đang import...</span>
            <span>{{ importProgress }}%</span>
          </div>
          <div class="w-full bg-gray-700 rounded-full h-2">
            <div class="bg-blue-600 h-2 rounded-full transition-all" :style="{ width: `${importProgress}%` }"></div>
          </div>
        </div>

        <div v-if="importResult" class="mb-4 p-4 rounded-lg" :class="importResult.failed > 0 ? 'bg-yellow-50 border border-yellow-200' : 'bg-green-500/10 border border-green-500/20'">
          <div class="flex items-center gap-2 mb-2">
            <AlertTriangle class="w-5 h-5 text-yellow-600" />
            <ShieldCheck class="w-5 h-5 text-green-600" />
            <span class="font-medium" :class="importResult.failed > 0 ? 'text-yellow-800' : 'text-green-300'">
              Import hoàn thành
            </span>
          </div>
          <div class="text-sm space-y-1" :class="importResult.failed > 0 ? 'text-yellow-700' : 'text-green-400'">
            <p>{{ importResult.message }}</p>
            <p>Thành công: {{ importResult.imported }}</p>
            <p v-if="importResult.failed > 0">Thất bại: {{ importResult.failed }}</p>
          </div>

          <button
            v-if="importResult.exportToken"
            @click="downloadImportResult"
            class="mt-3 text-blue-400 hover:text-blue-300 text-sm flex items-center gap-2"
          >
            <FileDown class="w-4 h-4" />
            Tải file kết quả chi tiết
          </button>
        </div>

        <div class="flex gap-3">
          <button @click="closeImportModal" class="btn-secondary flex-1 min-h-[56px]">Đóng</button>
          <button @click="importCabinets" :disabled="!selectedFile || importing" class="btn-primary flex-1 min-h-[56px]">
            {{ importing ? 'Đang import...' : 'Import' }}
          </button>
        </div>
      </div>
    </div>

    <CabinetFormModal
      :visible="showFormModal"
      :cabinet="selectedCabinet"
      @update:visible="showFormModal = $event"
      @saved="handleFormSaved"
    />
  </div>
</template>

<script setup>
import { FileDown, ShieldCheck, AlertTriangle, Plus, ChevronRight, ChevronLeft, ChevronsRight, ChevronsLeft, FileStack, Trash2, FileEdit, MapPin, Server, Search, ArrowUpToLine, ArrowDownToLine, Menu, Navigation } from 'lucide-vue-next'

import { computed, nextTick, onMounted, ref, watch } from 'vue'
import cabinetService from '@/services/cabinetService.js'
import CabinetFormModal from '@/components/admin/CabinetFormModal.vue'
import CabinetMap from '@/components/admin/CabinetMap.vue'
import MobileBottomSheet from '@/components/common/MobileBottomSheet.vue'

const perPageOptions = [
  { value: 10, label: '10' },
  { value: 20, label: '20' },
  { value: 50, label: '50' },
  { value: 100, label: '100' }
]

const viewMode = ref('list')
const mapEverOpened = ref(false)
const cabinetMapRef = ref(null)
const loading = ref(true)
const error = ref(null)
const cabinets = ref([])
const fileInput = ref(null)

// Lazy-init map on first switch, invalidateSize on subsequent switches
watch(viewMode, async (mode) => {
  if (mode === 'map') {
    if (!mapEverOpened.value) {
      mapEverOpened.value = true
    }
    await nextTick()
    cabinetMapRef.value?.refresh()
  }
})

const pagination = ref({
  current_page: 1,
  per_page: 10,
  total: 0,
  last_page: 1,
  from: 0,
  to: 0
})

const filters = ref({ search: '', area: '' })
let searchTimeout = null

const showFormModal = ref(false)
const selectedCabinet = ref(null)
const showImportModal = ref(false)
const selectedFile = ref(null)
const importing = ref(false)
const importResult = ref(null)
const importProgress = ref(0)

const openAddModal = () => {
  selectedCabinet.value = null
  showFormModal.value = true
}

const openEditModal = (cabinet) => {
  selectedCabinet.value = cabinet
  showFormModal.value = true
}

const handleFormSaved = () => {
  fetchCabinets()
}

const openImportModal = () => {
  selectedFile.value = null
  importResult.value = null
  importProgress.value = 0
  showImportModal.value = true

  if (fileInput.value) {
    fileInput.value.value = ''
  }
}

const closeImportModal = () => {
  showImportModal.value = false
}

const downloadBlob = (blob, fileName) => {
  const url = window.URL.createObjectURL(blob)
  const anchor = document.createElement('a')
  anchor.href = url
  anchor.download = fileName
  anchor.click()
  window.URL.revokeObjectURL(url)
}

const downloadTemplate = async () => {
  try {
    const blob = await cabinetService.downloadTemplate()
    downloadBlob(blob, 'cabinet_template.xlsx')
  } catch (requestError) {
    console.error('Download template failed:', requestError)
    alert('Không thể tải file mẫu')
  }
}

const downloadImportResult = async () => {
  if (!importResult.value?.exportToken) {
    alert('Không tìm thấy mã tải kết quả import')
    return
  }

  try {
    const blob = await cabinetService.exportResult(importResult.value.exportToken)
    downloadBlob(blob, 'import_results.csv')
  } catch (requestError) {
    console.error('Download result failed:', requestError)
    alert('Không thể tải file kết quả import')
  }
}

const fetchCabinets = async () => {
  loading.value = true
  error.value = null

  try {
    const params = {
      page: pagination.value.current_page,
      per_page: pagination.value.per_page
    }

    if (filters.value.search) params.search = filters.value.search
    if (filters.value.area) params.bts_site = filters.value.area

    const response = await cabinetService.getCabinets(params)
    cabinets.value = response.data || []
    pagination.value = {
      current_page: response.current_page,
      per_page: response.per_page,
      total: response.total,
      last_page: response.last_page,
      from: response.from,
      to: response.to
    }
  } catch (requestError) {
    error.value = 'Không thể tải dữ liệu'
    console.error(requestError)
  } finally {
    loading.value = false
  }
}

const deleteCabinet = async (cabinet) => {
  if (!confirm(`Bạn có chắc muốn xóa tủ cáp "${cabinet.cabinet_code}"?`)) {
    return
  }

  try {
    await cabinetService.deleteCabinet(cabinet.cabinet_code)
    await fetchCabinets()
  } catch (requestError) {
    console.error(requestError)
    alert('Có lỗi khi xóa tủ cáp')
  }
}

const goToPage = (page) => {
  if (!Number.isInteger(page) || page < 1 || page > pagination.value.last_page) {
    return
  }

  pagination.value.current_page = page
  fetchCabinets()
  window.scrollTo({ top: 0, behavior: 'smooth' })
}

const changePerPage = () => {
  pagination.value.current_page = 1
  fetchCabinets()
}

const visiblePages = computed(() => {
  const pages = []
  const current = pagination.value.current_page
  const last = pagination.value.last_page
  const delta = 2

  for (let page = Math.max(1, current - delta); page <= Math.min(last, current + delta); page += 1) {
    pages.push(page)
  }

  return pages
})



const handleSearch = () => {
  clearTimeout(searchTimeout)
  searchTimeout = setTimeout(() => {
    pagination.value.current_page = 1
    fetchCabinets()
  }, 300)
}

const handleFileSelect = (event) => {
  selectedFile.value = event.target.files?.[0] || null
  importResult.value = null
}

const importCabinets = async () => {
  if (!selectedFile.value) {
    return
  }

  importing.value = true
  importResult.value = null
  importProgress.value = 0

  let progressInterval = null

  try {
    progressInterval = setInterval(() => {
      if (importProgress.value < 90) {
        importProgress.value += 10
      }
    }, 200)

    const formData = new FormData()
    formData.append('file', selectedFile.value)

    const result = await cabinetService.importCabinets(formData)

    importProgress.value = 100
    importResult.value = {
      imported: result.imported,
      failed: result.failed,
      message: result.message,
      exportToken: result.export_token || null
    }

    await fetchCabinets()
  } catch (requestError) {
    importResult.value = {
      imported: 0,
      failed: 0,
      message: requestError.response?.data?.message || 'Import thất bại',
      exportToken: null
    }
  } finally {
    if (progressInterval) {
      clearInterval(progressInterval)
    }

    importing.value = false
    importProgress.value = 0
  }
}

const exportCabinets = async () => {
  try {
    const blob = await cabinetService.exportCabinets()
    downloadBlob(blob, 'cabinets.csv')
  } catch (requestError) {
    console.error(requestError)
    alert('Export thất bại')
  }
}

onMounted(() => {
  fetchCabinets()
})
</script>
