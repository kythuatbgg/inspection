<template>
  <div class="flex flex-col md:flex-row w-full h-full md:absolute md:inset-0">
    <!-- MASTER LIST -->
    <div class="w-full md:w-[350px] lg:w-[400px] shrink-0 md:h-full md:overflow-y-auto md:bg-white md:border-r border-slate-200"
         :class="{ 'hidden md:block': isDetailOpen }">
      <div class="p-4 md:p-5 space-y-4">

        <!-- Header -->
        <div>
          <h2 class="text-lg md:text-xl font-bold text-slate-900 tracking-tight font-heading">Lô của tôi</h2>
          <p class="text-xs text-slate-500 mt-1">Danh sách lô đã được giao</p>
        </div>

        <!-- Segmented Filter -->
        <div class="bg-slate-100/80 p-1 flex gap-1 border border-slate-200 rounded-lg">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            class="flex-1 flex items-center justify-center gap-1.5 py-2 rounded-lg text-xs md:text-[13px] font-semibold whitespace-nowrap transition-all"
            :class="activeTab === tab.value
              ? 'bg-white text-slate-900 shadow-sm'
              : 'text-slate-500 hover:text-slate-700'"
          >
            {{ tab.label }}
            <span
              v-if="!loading"
              class="min-w-[20px] h-5 px-1.5 rounded-full text-[10px] font-bold flex items-center justify-center leading-none tracking-tight"
              :class="activeTab === tab.value
                ? 'bg-primary-600 text-white'
                : 'bg-slate-200/80 text-slate-500'"
            >{{ getCount(tab.value) }}</span>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
        </div>

        <!-- Error -->
        <div v-else-if="errorMessage" class="rounded-lg border border-red-200 bg-red-50 p-4 text-center">
          <p class="font-semibold text-red-700">Không thể tải danh sách lô</p>
          <p class="mt-1 text-sm text-red-600">{{ errorMessage }}</p>
          <button
            @click="fetchData()"
            class="mt-4 inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-red-700"
          >
            Thử lại
          </button>
        </div>

        <!-- Batch List -->
        <div v-else class="space-y-3">
          <button
            v-for="batch in batches"
            :key="batch.id"
            @click="goToBatch(batch)"
            class="w-full text-left rounded-lg bg-white border border-slate-200 p-4 transition-all cursor-pointer hover:bg-slate-50"
            :class="{ 'border-primary-500 ring-1 ring-primary-500 bg-primary-50/10 hover:bg-primary-50/20': isActiveBatch(batch.id) }"
          >
            <div class="flex items-start justify-between gap-3">
              <div class="flex-1 min-w-0 pr-3">
                <div class="flex items-center gap-2">
                  <h4 class="font-bold text-slate-900 font-heading tracking-tight">{{ batch.name }}</h4>
                </div>
                <p class="text-xs text-slate-500 mt-1 truncate">{{ batch.checklist?.name || 'Checklist tiêu chuẩn' }}</p>
              </div>
              <div class="shrink-0 text-right">
                <span
                  class="text-[10px] font-bold px-2 py-0.5 rounded-md uppercase tracking-widest"
                  :class="batch.status === 'completed'
                    ? 'bg-success/10 text-success'
                    : 'bg-warning/10 text-warning'"
                >
                  {{ statusLabel(batch.status) }}
                </span>
              </div>
            </div>

            <!-- Progress -->
            <div class="mt-3">
              <div class="flex items-center justify-between text-[11px] mb-1.5">
                <span class="text-slate-500 uppercase tracking-widest font-medium">Tiến độ</span>
                <span class="font-bold text-slate-700">{{ batchProgress(batch) }}%</span>
              </div>
              <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all"
                  :class="batchProgress(batch) === 100 ? 'bg-success' : 'bg-primary-500'"
                  :style="{ width: batchProgress(batch) + '%' }"
                ></div>
              </div>
            </div>

            <div class="flex items-center gap-3 mt-3 text-[11px] text-slate-500 uppercase tracking-widest font-medium">
              <span class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 text-slate-400" />
                {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
              </span>
              <span>{{ batch.completed_count || 0 }} / {{ batch.plans_count || 0 }} tủ</span>
            </div>
          </button>

          <!-- Empty State -->
          <div v-if="batches.length === 0" class="flex flex-col items-center justify-center py-14 text-center">
            <div class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center mb-4">
              <FileStack class="w-7 h-7 text-slate-400" />
            </div>
            <p class="font-semibold text-slate-800">Không có lô nào</p>
            <p class="text-sm text-slate-500 mt-1">
              {{ activeTab === 'active' ? 'Chưa có lô đang xử lý' : 'Không có lô nào ở trạng thái này' }}
            </p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalPages > 1" class="flex items-center justify-between px-3 md:px-4 py-3 bg-white rounded-lg border border-slate-200">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="flex items-center gap-1.5 min-h-[40px] px-3 md:px-4 rounded-lg font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer text-sm"
            :class="currentPage === 1 ? 'bg-slate-100 text-slate-400' : 'bg-slate-100 text-slate-700 hover:bg-slate-200'"
          >
            <ChevronLeft class="w-4 h-4" />
            <span class="hidden sm:inline">Trước</span>
          </button>
          <div class="flex items-center gap-1.5 text-sm">
            <span class="text-slate-500">Trang</span>
            <span class="font-bold text-slate-900">{{ currentPage }}</span>
            <span class="text-slate-400">/</span>
            <span class="font-bold text-slate-900">{{ totalPages }}</span>
          </div>
          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage >= totalPages"
            class="flex items-center gap-1.5 min-h-[40px] px-3 md:px-4 rounded-lg font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer text-sm"
            :class="currentPage >= totalPages ? 'bg-slate-100 text-slate-400' : 'bg-primary-600 text-white hover:bg-primary-700'"
          >
            <span class="hidden sm:inline">Sau</span>
            <ChevronRight class="w-4 h-4" />
          </button>
        </div>
      </div>
    </div>

    <!-- DETAIL VIEW -->
    <div class="flex-1 md:h-full md:overflow-y-auto bg-slate-50 md:bg-white relative min-h-screen md:min-h-0"
         :class="{ 'hidden md:flex flex-col': !isDetailOpen }">
      <router-view v-if="isDetailOpen" :key="$route.fullPath"></router-view>

      <!-- Desktop Placeholder -->
      <div v-else class="hidden md:flex flex-col items-center justify-center h-full text-center p-8">
        <div class="w-16 h-16 rounded-lg bg-slate-200 shadow-sm flex items-center justify-center mb-5 border border-slate-300">
          <FileStack class="w-8 h-8 text-slate-500" />
        </div>
        <h3 class="font-bold text-slate-900 text-xl tracking-tight font-heading">Chọn một lô</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-xs leading-relaxed">Nhấn vào một lô bên trái để xem danh sách tủ và tiến hành kiểm tra.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Calendar, ChevronLeft, ChevronRight, FileStack, Loader2 } from 'lucide-vue-next'

import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import batchService from '@/services/batchService.js'

const router = useRouter()
const route = useRoute()

const isDetailOpen = computed(() => !!route.params.id)
const isActiveBatch = (id) => route.params.id == id

const loading = ref(true)
const errorMessage = ref('')
const batches = ref([])
const counts = ref({ all: 0, active: 0, completed: 0 })
const activeTab = ref('active')
const currentPage = ref(1)
const perPage = 10
const totalPages = ref(1)
const totalItems = ref(0)

const tabs = [
  { label: 'Đang KT', value: 'active' },
  { label: 'Hoàn thành', value: 'completed' }
]

watch(activeTab, () => {
  fetchData()
})

const getCount = (tab) => {
  return counts.value[tab] || 0
}

const statusLabel = (status) => {
  const map = { pending: 'Chờ', active: 'Đang KT', completed: 'Hoàn thành' }
  return map[status] || status
}

const batchProgress = (batch) => {
  const total = batch.plans_count || 0
  if (total === 0) return 0
  const done = batch.completed_count || 0
  return Math.round((done / total) * 100)
}

const formatDate = (dateStr) => {
  if (!dateStr) return '—'
  const d = new Date(dateStr)
  return `${d.getDate().toString().padStart(2, '0')}/${(d.getMonth() + 1).toString().padStart(2, '0')}`
}

const goToBatch = (batch) => {
  router.push({ name: 'inspector-batch-detail', params: { id: batch.id } })
}

const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

const fetchData = async (page = 1) => {
  loading.value = true
  errorMessage.value = ''
  try {
    const statusMap = { active: 'ongoing', completed: 'completed' }
    const status = statusMap[activeTab.value] || ''

    const params = { per_page: perPage, page, approval_status: 'approved' }
    if (status) params.status = status

    const res = await batchService.getBatches(params)

    batches.value = res.data || res || []
    currentPage.value = res.current_page || page
    totalPages.value = res.last_page || 1
    totalItems.value = res.total || 0

    // Compute counts from loaded batches
    if (page === 1) {
      const allRes = await batchService.getBatches({ per_page: 1, approval_status: 'approved' })
      const totalAll = allRes.total || 0
      const activeRes = await batchService.getBatches({ per_page: 1, approval_status: 'approved', status: 'ongoing' })
      const totalActive = activeRes.total || 0
      const completedRes = await batchService.getBatches({ per_page: 1, approval_status: 'approved', status: 'completed' })
      const totalCompleted = completedRes.total || 0
      counts.value = {
        all: totalAll,
        active: totalActive,
        completed: totalCompleted
      }
    }
  } catch (e) {
    console.error('Failed to fetch batches:', e)
    errorMessage.value = e.response?.data?.message || 'Không thể tải danh sách lô.'
    batches.value = []
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchData())
</script>
