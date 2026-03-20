<template>
  <div class="space-y-4">
    <template v-if="!loading && batch">
      <!-- Back + Batch Info -->
      <div class="rounded-2xl bg-white border border-slate-200 p-5">
        <button @click="goBack" class="flex items-center gap-1 text-sm text-primary-600 font-medium mb-3 -ml-1 active:opacity-70">
          <ChevronLeft class="w-4 h-4" />
          Quay lại
        </button>
        <h2 class="text-lg font-bold text-slate-900 leading-tight">{{ batch.name }}</h2>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-xs text-slate-400">
          <span class="flex items-center gap-1">
            <ListTodo class="w-3.5 h-3.5" />
            {{ batch.checklist?.name || '—' }}
          </span>
          <span class="flex items-center gap-1">
            <Calendar class="w-3.5 h-3.5" />
            {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
          </span>
        </div>
      </div>

      <!-- Progress -->
      <div class="rounded-2xl bg-white border border-slate-200 p-5">
        <div class="flex items-center justify-between mb-2">
          <span class="text-xs text-slate-400 font-medium uppercase tracking-wide">Tiến độ tổng</span>
          <span class="text-lg font-bold tracking-tight" :class="progress.pct === 100 ? 'text-emerald-600' : 'text-primary-600'">
            {{ progress.done }}/{{ progress.total }}
          </span>
        </div>
        <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-500"
            :class="progress.pct === 100 ? 'bg-emerald-500' : 'bg-primary-500'"
            :style="{ width: progress.pct + '%' }"
          ></div>
        </div>
        <p class="text-[11px] text-slate-400 mt-1.5">{{ progress.pct }}% hoàn thành</p>
      </div>

      <!-- Cabinet List -->
      <div>
        <h3 class="text-sm font-bold text-slate-900 mb-3 uppercase tracking-wide">Danh sách tủ cáp ({{ planDetails.length }})</h3>

        <div v-if="planDetails.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
          <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
            <Server class="w-7 h-7 text-slate-300" />
          </div>
          <p class="font-semibold text-slate-700">Chưa có tủ nào</p>
        </div>

        <div v-else class="space-y-3">
          <button
            v-for="plan in planDetails"
            :key="plan.id"
            @click="goToInspection(plan)"
            class="w-full text-left rounded-2xl bg-white border border-slate-200 p-4 active:scale-[0.98] transition-all cursor-pointer"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0 pr-3">
                <div class="flex items-center gap-2">
                  <h4 class="font-bold text-slate-900">{{ plan.cabinet_code }}</h4>
                  <span
                    v-if="plan.status === 'done'"
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-emerald-50 text-emerald-600"
                  >Đã kiểm tra</span>
                  <span
                    v-else
                    class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-amber-50 text-amber-600"
                  >Chưa kiểm tra</span>
                </div>
                <p v-if="plan.cabinet?.bts_site" class="text-xs text-slate-400 mt-1">{{ plan.cabinet.bts_site }}</p>
              </div>
              <ChevronRight class="w-5 h-5 text-slate-300 shrink-0" />
            </div>

            <!-- Inspection result -->
            <div v-if="plan.inspection" class="mt-3 pt-3 border-t border-slate-100 flex items-center gap-2 flex-wrap">
              <span
                class="text-[10px] font-bold px-2.5 py-1 rounded-full"
                :class="plan.inspection.final_result === 'PASS' ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-600'"
              >
                {{ plan.inspection.final_result === 'PASS' ? 'ĐẠT' : 'KHÔNG ĐẠT' }}
              </span>
              <span class="text-[10px] font-bold px-2.5 py-1 rounded-full bg-primary-50 text-primary-700">Điểm: {{ plan.inspection.total_score }}</span>
              <span v-if="plan.inspection.critical_errors_count > 0" class="text-[10px] font-bold px-2 py-1 rounded-full bg-red-50 text-red-600 flex items-center gap-1">
                <AlertTriangle class="w-3 h-3" />
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
      <p class="text-slate-500">Không thể tải dữ liệu lô kiểm tra.</p>
      <button @click="fetchData" class="mt-3 px-5 py-2.5 bg-primary-600 text-white rounded-xl text-sm font-semibold active:scale-95 transition-all">Thử lại</button>
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
    name: 'inspector-inspection',
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
