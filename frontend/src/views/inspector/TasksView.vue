<template>
  <div class="flex flex-col md:flex-row w-full h-full md:absolute md:inset-0">
    <!-- MASTER LIST -->
    <div class="w-full md:w-[350px] lg:w-[400px] shrink-0 md:h-full md:overflow-y-auto md:bg-white md:border-r border-slate-200"
         :class="{ 'hidden md:block': isDetailOpen }">
      <div class="p-4 md:p-5 space-y-4">
        <!-- Segmented Filter -->
        <div class="bg-slate-100 rounded-2xl p-1 flex gap-1">
          <button
            v-for="tab in tabs"
            :key="tab.value"
            @click="activeTab = tab.value"
            class="flex-1 flex items-center justify-center gap-1.5 py-2.5 rounded-xl text-[13px] font-semibold whitespace-nowrap transition-all"
            :class="activeTab === tab.value
              ? 'bg-white text-slate-900 shadow-sm'
              : 'text-slate-500 active:bg-white/50'"
          >
            {{ tab.label }}
            <span
              v-if="!loading"
              class="min-w-[20px] h-5 px-1 rounded-full text-[10px] font-bold flex items-center justify-center leading-none"
              :class="activeTab === tab.value
                ? 'bg-primary-600 text-white'
                : 'bg-slate-200 text-slate-500'"
            >{{ getCount(tab.value) }}</span>
          </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
        </div>

        <!-- Task List -->
        <div v-else class="space-y-3">
          <button
            v-for="task in paginatedTasks"
            :key="task.planId"
            @click="goToInspection(task)"
            class="w-full text-left rounded-2xl bg-white border border-slate-200 p-4 active:scale-[0.98] transition-all cursor-pointer"
            :class="{ 'border-primary-500 ring-1 ring-primary-500 bg-primary-50/10': isActiveTask(task.planId) }"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0 pr-3">
                <div class="flex items-center gap-2">
                  <h4 class="font-bold text-slate-900">{{ task.cabinetCode }}</h4>
                  <span
                    v-if="task.status === 'done'"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600"
                  >Đã kiểm tra</span>
                  <span
                    v-else
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600"
                  >Chưa kiểm tra</span>
                </div>
                <p class="text-xs text-slate-400 mt-1.5 truncate">{{ task.batchName }}</p>
              </div>

              <!-- Result badge -->
              <div v-if="task.result" class="shrink-0">
                <span
                  class="text-[10px] font-bold px-2.5 py-1 rounded-lg"
                  :class="task.result === 'PASS' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'"
                >
                  {{ task.result === 'PASS' ? 'ĐẠT' : 'K.ĐẠT' }}
                </span>
              </div>
              <ChevronRight v-else class="w-5 h-5 shrink-0" :class="isActiveTask(task.planId) ? 'text-primary-500' : 'text-slate-300'" />
            </div>

            <div class="flex items-center gap-3 mt-2.5 text-[11px] text-slate-400">
              <span class="flex items-center gap-1">
                <Calendar class="w-3 h-3" />
                {{ task.dateRange }}
              </span>
              <span v-if="task.score != null" class="flex items-center gap-1 font-semibold text-primary-600">
                Điểm: {{ task.score }}
              </span>
            </div>
          </button>

          <!-- Empty State -->
          <div v-if="paginatedTasks.length === 0" class="flex flex-col items-center justify-center py-14 text-center">
            <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
              <ClipboardList class="w-7 h-7 text-slate-300" />
            </div>
            <p class="font-semibold text-slate-700">Không có nhiệm vụ</p>
            <p class="text-sm text-slate-400 mt-1">{{ activeTab === 'all' ? 'Chưa có nhiệm vụ nào được giao' : 'Không có kết quả phù hợp' }}</p>
          </div>
        </div>

        <!-- Pagination -->
        <div v-if="filteredTasks.length > perPage" class="flex items-center justify-between px-4 py-3 bg-white rounded-2xl border border-slate-200">
          <button
            @click="goToPage(currentPage - 1)"
            :disabled="currentPage === 1"
            class="flex items-center gap-2 min-h-[56px] px-5 rounded-xl font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            :class="currentPage === 1 ? 'bg-slate-100 text-slate-400' : 'bg-slate-100 text-slate-700 active:bg-slate-200'"
          >
            <ChevronLeft class="w-5 h-5" />
            <span class="hidden sm:inline">Trước</span>
          </button>
          <div class="flex items-center gap-2 text-sm">
            <span class="text-slate-600">Trang</span>
            <span class="font-bold text-slate-900">{{ currentPage }}</span>
            <span class="text-slate-400">/</span>
            <span class="font-bold text-slate-900">{{ totalPages }}</span>
          </div>
          <button
            @click="goToPage(currentPage + 1)"
            :disabled="currentPage >= totalPages"
            class="flex items-center gap-2 min-h-[56px] px-5 rounded-xl font-semibold transition-all duration-200 disabled:opacity-50 disabled:cursor-not-allowed cursor-pointer"
            :class="currentPage >= totalPages ? 'bg-slate-100 text-slate-400' : 'bg-primary-600 text-white active:bg-primary-700'"
          >
            <span class="hidden sm:inline">Sau</span>
            <ChevronRight class="w-5 h-5" />
          </button>
        </div>
      </div>
    </div>

    <!-- DETAIL VIEW -->
    <div class="flex-1 md:h-full md:overflow-y-auto bg-slate-50 relative min-h-screen md:min-h-0"
         :class="{ 'hidden md:flex flex-col': !isDetailOpen }">
      <router-view v-if="isDetailOpen" :key="$route.fullPath"></router-view>
      
      <!-- Desktop Placeholder -->
      <div v-else class="hidden md:flex flex-col items-center justify-center h-full text-center p-8">
        <div class="w-20 h-20 rounded-3xl bg-slate-200/50 flex items-center justify-center mb-5">
          <ClipboardList class="w-10 h-10 text-slate-400" />
        </div>
        <h3 class="font-bold text-slate-800 text-xl tracking-tight">Chọn một nhiệm vụ</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-xs leading-relaxed">Nhấn vào một nhiệm vụ từ danh sách bên trái để bắt đầu kiểm tra hoặc xem kết quả.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Calendar, ChevronLeft, ChevronRight, ClipboardList, Loader2 } from 'lucide-vue-next'

import { ref, computed, onMounted, watch } from 'vue'
import { useRouter, useRoute } from 'vue-router'
import batchService from '@/services/batchService.js'
import api from '@/services/api.js'

const router = useRouter()
const route = useRoute()

const isDetailOpen = computed(() => !!route.params.planId)
const isActiveTask = (id) => route.params.planId == id

const loading = ref(true)
const allTasks = ref([])
const activeTab = ref('all')
const currentPage = ref(1)
const perPage = 5
const totalTasks = ref(0)

const tabs = [
  { label: 'Tất cả', value: 'all' },
  { label: 'Chưa kiểm tra', value: 'planned' },
  { label: 'Đã hoàn thành', value: 'done' }
]

watch(activeTab, () => {
  currentPage.value = 1
})

const filteredTasks = computed(() => {
  if (activeTab.value === 'all') return allTasks.value
  return allTasks.value.filter(t => t.status === activeTab.value)
})

const paginatedTasks = computed(() => {
  const start = (currentPage.value - 1) * perPage
  const end = start + perPage
  return filteredTasks.value.slice(start, end)
})

const totalPages = computed(() => Math.ceil(filteredTasks.value.length / perPage) || 1)

const goToPage = (page) => {
  if (page < 1 || page > totalPages.value) return
  currentPage.value = page
  // TODO: Fetch tasks for specific page
}

const getCount = (tab) => {
  if (tab === 'all') return allTasks.value.length
  return allTasks.value.filter(t => t.status === tab).length
}

const formatDateRange = (start, end) => {
  const fmt = (d) => {
    if (!d) return ''
    const dt = new Date(d)
    return `${dt.getDate()}/${dt.getMonth() + 1}`
  }
  return `${fmt(start)} — ${fmt(end)}`
}

const goToInspection = (task) => {
  router.push({ name: 'inspector-tasks-inspection', params: { planId: task.planId } })
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await batchService.getBatches({ per_page: 100, approval_status: 'approved' })
    const batches = res.data || res || []
    
    const tasks = []
    
    await Promise.all(
      batches.map(async (batch) => {
        try {
          const plansRes = await api.get(`/batches/${batch.id}/plans`)
          const plans = plansRes.data?.data || plansRes.data || []
          
          for (const plan of plans) {
            // Lấy inspection từ plan đã eager load sẵn - KHÔNG gọi API riêng
            const inspection = plan.inspection || null
            const result = inspection?.final_result || null
            const score = inspection?.total_score ?? null

            tasks.push({
              planId: plan.id,
              cabinetCode: plan.cabinet_code,
              batchName: batch.name,
              status: plan.status,
              result,
              score,
              dateRange: formatDateRange(batch.start_date, batch.end_date)
            })
          }
        } catch { /* skip if fails */ }
      })
    )
    
    allTasks.value = tasks
    totalTasks.value = tasks.length
  } catch (e) {
    console.error('Failed to fetch tasks:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
