<template>
  <div class="flex flex-col md:flex-row w-full h-full md:absolute md:inset-0">
    <!-- MASTER LIST -->
    <div class="w-full md:w-[350px] lg:w-[400px] shrink-0 md:h-full md:overflow-y-auto md:bg-white md:border-r border-slate-200"
         :class="{ 'hidden md:block': isDetailOpen }">
      <div class="p-4 md:p-5 space-y-4">
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
          <p class="font-semibold text-red-700">Không thể tải danh sách nhiệm vụ</p>
          <p class="mt-1 text-sm text-red-600">{{ errorMessage }}</p>
          <button
            @click="fetchData(currentPage)"
            class="mt-4 inline-flex items-center justify-center rounded-lg bg-red-600 px-4 py-2 text-sm font-semibold text-white transition-all hover:bg-red-700"
          >
            Thử lại
          </button>
        </div>

        <!-- Task List -->
        <div v-else class="space-y-3">
          <button
            v-for="task in paginatedTasks"
            :key="task.planId"
            @click="goToInspection(task)"
            class="w-full text-left rounded-lg bg-white border border-slate-200 p-4 transition-all cursor-pointer hover:bg-slate-50"
            :class="{ 'border-primary-500 ring-1 ring-primary-500 bg-primary-50/10 hover:bg-primary-50/20': isActiveTask(task.planId) }"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0 pr-3">
                <div class="flex items-center gap-2">
                  <h4 class="font-bold text-slate-900 font-heading tracking-tight">{{ task.cabinetCode }}</h4>
                  <span
                    v-if="task.status === 'done'"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-success/10 text-success uppercase tracking-widest"
                  >Đã KT</span>
                  <span
                    v-else
                    class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-warning/10 text-warning uppercase tracking-widest"
                  >Chưa KT</span>
                </div>
                <p class="text-xs text-slate-500 mt-1 truncate font-medium">{{ task.batchName }}</p>
              </div>

              <!-- Result badge -->
              <div v-if="task.result" class="shrink-0">
                <span
                  class="text-[10px] font-bold px-2.5 py-1 rounded-md tracking-widest uppercase"
                  :class="task.result === 'PASS' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                >
                  {{ task.result === 'PASS' ? 'ĐẠT' : 'K.ĐẠT' }}
                </span>
              </div>
              <ChevronRight v-else class="w-5 h-5 shrink-0" :class="isActiveTask(task.planId) ? 'text-primary-500' : 'text-slate-300'" />
            </div>

            <div class="flex items-center gap-3 mt-3 text-[11px] text-slate-500 uppercase tracking-widest font-medium">
              <span class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 text-slate-400" />
                {{ task.dateRange }}
              </span>
              <span v-if="task.score != null" class="flex items-center gap-1.5 font-bold text-primary-600">
                Điểm: {{ task.score }}
              </span>
            </div>
          </button>

          <!-- Empty State -->
          <div v-if="paginatedTasks.length === 0" class="flex flex-col items-center justify-center py-14 text-center">
            <div class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center mb-4">
              <ClipboardList class="w-7 h-7 text-slate-400" />
            </div>
            <p class="font-semibold text-slate-800">Không có nhiệm vụ</p>
            <p class="text-sm text-slate-500 mt-1">{{ activeTab === 'all' ? 'Chưa có nhiệm vụ nào được giao' : 'Không có kết quả phù hợp' }}</p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="totalItems > perPage" class="flex items-center justify-between px-3 md:px-4 py-3 bg-white rounded-lg border border-slate-200">
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
      <div v-else class="hidden md:flex flex-col items-center justify-center h-full text-center p-8 bg-slate-50">
        <div class="w-16 h-16 rounded-lg bg-slate-200 shadow-sm flex items-center justify-center mb-5 border border-slate-300">
          <ClipboardList class="w-8 h-8 text-slate-500" />
        </div>
        <h3 class="font-bold text-slate-900 text-xl tracking-tight font-heading">Chọn một nhiệm vụ</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-xs leading-relaxed">Nhấn vào một nhiệm vụ từ danh sách bên trái để bắt đầu kiểm tra hoặc xem kết quả.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Calendar, ChevronLeft, ChevronRight, ClipboardList, Loader2 } from 'lucide-vue-next'

import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import api from '@/services/api.js'

const router = useRouter()
const route = useRoute()

const isDetailOpen = computed(() => !!route.params.planId)
const isActiveTask = (id) => route.params.planId == id

const loading = ref(true)
const errorMessage = ref('')
const tasks = ref([])
const counts = ref({ all: 0, planned: 0, done: 0 })
const activeTab = ref('all')
const currentPage = ref(1)
const perPage = 10
const totalPages = ref(1)
const totalItems = ref(0)

const tabs = [
  { label: 'Tất cả', value: 'all' },
  { label: 'Chưa kiểm tra', value: 'planned' },
  { label: 'Đã hoàn thành', value: 'done' }
]

watch(activeTab, () => {
  fetchData(1)
})

const paginatedTasks = computed(() => tasks.value)

const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return
  fetchData(page)
}

const getCount = (tab) => {
  return counts.value[tab] || 0
}

const goToInspection = (task) => {
  router.push({ name: 'inspector-tasks-inspection', params: { planId: task.planId } })
}

const fetchData = async (page = currentPage.value) => {
  loading.value = true
  errorMessage.value = ''
  try {
    const res = await api.get('/inspector/tasks', {
      silent: true,
      params: {
        page,
        per_page: perPage,
        status: activeTab.value
      }
    })

    tasks.value = res.data?.data || []
    counts.value = res.data?.counts || { all: 0, planned: 0, done: 0 }
    currentPage.value = res.data?.current_page || 1
    totalPages.value = res.data?.last_page || 1
    totalItems.value = res.data?.total || 0
  } catch (e) {
    console.error('Failed to fetch tasks:', e)
    errorMessage.value = e.response?.data?.message || 'Vui lòng kiểm tra kết nối và thử lại.'
    tasks.value = []
    counts.value = { all: 0, planned: 0, done: 0 }
    currentPage.value = 1
    totalPages.value = 1
    totalItems.value = 0
  } finally {
    loading.value = false
  }
}

onMounted(() => fetchData(1))
</script>
