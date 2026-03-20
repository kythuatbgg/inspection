<template>
  <div class="flex flex-col md:flex-row w-full h-full md:absolute md:inset-0">
    <!-- MASTER LIST -->
    <div class="w-full md:w-[350px] lg:w-[400px] shrink-0 md:h-full md:overflow-y-auto md:bg-white md:border-r border-slate-200"
         :class="{ 'hidden md:block': isDetailOpen }">
      <div class="p-4 md:p-5 space-y-4">
        <template v-if="!loading && batch">
          <!-- Back + Batch Info -->
          <div class="rounded-lg bg-white border border-slate-200 p-5 shadow-sm">
            <button @click="goBack" class="flex items-center gap-1.5 text-sm text-primary-600 font-medium mb-3 -ml-1 hover:text-primary-700 transition-colors">
              <ChevronLeft class="w-4 h-4" />
              Quay lại
            </button>
            <h2 class="text-lg font-bold text-slate-900 leading-tight font-heading">{{ batch.name }}</h2>
            <div class="flex flex-wrap items-center gap-x-4 gap-y-1.5 mt-2.5 text-[11px] text-slate-500 uppercase tracking-widest font-medium">
              <span class="flex items-center gap-1.5">
                <ListTodo class="w-3.5 h-3.5 text-slate-400" />
                {{ batch.checklist?.name || '—' }}
              </span>
              <span class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 text-slate-400" />
                {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
              </span>
            </div>
          </div>

          <!-- Progress -->
          <div class="rounded-lg bg-white border border-slate-200 p-5 shadow-sm">
            <div class="flex items-center justify-between mb-2">
              <span class="text-xs text-slate-500 font-bold uppercase tracking-widest">Tiến độ tổng</span>
              <span class="text-lg font-bold tracking-tight" :class="progress.pct === 100 ? 'text-success' : 'text-primary-600'">
                {{ progress.done }}/{{ progress.total }}
              </span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="progress.pct === 100 ? 'bg-success' : 'bg-primary-600'"
                :style="{ width: progress.pct + '%' }"
              ></div>
            </div>
            <p class="text-[11px] text-slate-500 mt-2 font-medium uppercase tracking-widest">{{ progress.pct }}% Hoàn thành</p>
          </div>

          <!-- Cabinet List -->
          <div>
            <h3 class="text-sm font-bold text-slate-900 mb-3 uppercase tracking-wider font-heading">Danh sách tủ cáp ({{ planDetails.length }})</h3>

            <div v-if="planDetails.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
              <div class="w-14 h-14 rounded-lg bg-slate-100 flex items-center justify-center mb-4">
                <Server class="w-7 h-7 text-slate-400" />
              </div>
              <p class="font-semibold text-slate-800">Chưa có tủ nào</p>
            </div>

            <div v-else class="space-y-3">
              <button
                v-for="plan in planDetails"
                :key="plan.id"
                @click="goToInspection(plan)"
                class="w-full text-left rounded-lg bg-white border border-slate-200 p-4 transition-all cursor-pointer hover:bg-slate-50 shadow-sm"
                :class="{ 'border-primary-500 ring-1 ring-primary-500 bg-primary-50/10 hover:bg-primary-50/20': isActiveTask(plan.id) }"
              >
                <div class="flex items-center justify-between">
                  <div class="flex-1 min-w-0 pr-3">
                    <div class="flex items-center gap-2">
                      <h4 class="font-bold text-slate-900 font-heading tracking-tight">{{ plan.cabinet_code }}</h4>
                      <span
                        v-if="plan.status === 'done'"
                        class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-success/10 text-success uppercase tracking-widest"
                      >Đã KT</span>
                      <span
                        v-else
                        class="text-[10px] font-bold px-2 py-0.5 rounded-md bg-warning/10 text-warning uppercase tracking-widest"
                      >Chưa KT</span>
                    </div>
                    <p v-if="plan.cabinet?.bts_site" class="text-xs text-slate-500 mt-1.5 font-medium truncate">{{ plan.cabinet.bts_site }}</p>
                  </div>
                  <ChevronRight class="w-5 h-5 shrink-0" :class="isActiveTask(plan.id) ? 'text-primary-500' : 'text-slate-300'" />
                </div>

                <!-- Inspection result -->
                <div v-if="plan.inspection" class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2 flex-wrap" :class="{ 'border-primary-100': isActiveTask(plan.id) }">
                  <span
                    class="text-[10px] font-bold px-2.5 py-1.5 rounded-md tracking-widest uppercase"
                    :class="plan.inspection.final_result === 'PASS' ? 'bg-success/10 text-success' : 'bg-danger/10 text-danger'"
                  >
                    {{ plan.inspection.final_result === 'PASS' ? 'ĐẠT' : 'KHÔNG ĐẠT' }}
                  </span>
                  <span class="text-[10px] font-bold px-2.5 py-1.5 rounded-md bg-primary-50 text-primary-700 tracking-widest uppercase">Điểm: {{ plan.inspection.total_score }}</span>
                  <span v-if="plan.inspection.critical_errors_count > 0" class="text-[10px] font-bold px-2.5 py-1.5 rounded-md bg-danger/10 text-danger flex items-center gap-1.5 tracking-widest uppercase">
                    <AlertTriangle class="w-3.5 h-3.5" />
                    {{ plan.inspection.critical_errors_count }} lỗi
                  </span>
                </div>
              </button>
            </div>
          </div>
        </template>

        <!-- Loading -->
        <div v-if="loading" class="flex justify-center py-12">
          <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
        </div>

        <!-- Error -->
        <div v-else-if="!batch" class="flex flex-col items-center justify-center py-14 text-center">
          <p class="text-slate-600 font-medium">Không thể tải dữ liệu lô kiểm tra.</p>
          <button @click="fetchData" class="mt-4 px-5 py-2.5 bg-primary-600 hover:bg-primary-700 text-white rounded-lg text-sm font-semibold transition-all shadow-sm">Thử lại</button>
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
          <Server class="w-8 h-8 text-slate-500" />
        </div>
        <h3 class="font-bold text-slate-900 text-xl tracking-tight font-heading">Chọn một tủ cáp</h3>
        <p class="text-sm text-slate-500 mt-2 max-w-xs leading-relaxed">Nhấn vào một tủ cáp trong danh sách để bắt đầu kiểm tra hoặc xem chi tiết lỗi.</p>
      </div>
    </div>
  </div>
</template>

<script setup>
import { AlertTriangle, ChevronRight, Calendar, ListTodo, ChevronLeft, Server, Loader2 } from 'lucide-vue-next'

import { ref, computed, onMounted } from 'vue'
import { useRoute, useRouter } from 'vue-router'
import batchService from '@/services/batchService.js'
import api from '@/services/api.js'

const route = useRoute()
const router = useRouter()

const isDetailOpen = computed(() => !!route.params.planId)
const isActiveTask = (id) => route.params.planId == id

const loading = ref(true)
const batch = ref(null)
const planDetails = ref([])

const progress = computed(() => {
  const total = planDetails.value.length
  const done = planDetails.value.filter(p => p.status === 'done').length
  return {
    total,
    done,
    pct: total > 0 ? Math.round((done / total) * 100) : 0
  }
})

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`
}

const goBack = () => {
  router.push({ name: 'inspector-dashboard' })
}

const goToInspection = (plan) => {
  router.push({
    name: 'inspector-batch-inspection',
    params: { planId: plan.id }
  })
}

const fetchData = async () => {
  loading.value = true
  try {
    const batchId = route.params.id

    const [batchData, plansRes] = await Promise.all([
      batchService.getBatchById(batchId),
      api.get(`/batches/${batchId}/plans`)
    ])

    batch.value = batchData
    planDetails.value = plansRes.data?.data || plansRes.data || []
  } catch (e) {
    console.error('Failed to load batch detail:', e)
    batch.value = null
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
