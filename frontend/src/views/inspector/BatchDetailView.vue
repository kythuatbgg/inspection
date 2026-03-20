<template>
  <div class="space-y-5">
    <template v-if="!loading && batch">
      <!-- Back + Batch Info -->
      <div class="rounded-2xl bg-white border border-gray-200 p-5">
        <button @click="goBack" class="flex items-center gap-1 text-sm text-primary-600 font-medium mb-3 -ml-1 active:opacity-70">
          <ChevronLeft class="w-4 h-4" />
          Quay lại
        </button>
        <h2 class="text-lg font-bold text-gray-900">{{ batch.name }}</h2>
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 mt-2 text-sm text-gray-500">
          <span class="flex items-center gap-1">
            <ListTodo class="w-4 h-4" />
            {{ batch.checklist?.name || '—' }}
          </span>
          <span class="flex items-center gap-1">
            <Calendar class="w-4 h-4" />
            {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
          </span>
        </div>
      </div>

      <!-- Progress Bar -->
      <div class="rounded-2xl bg-white border border-gray-200 p-5">
        <div class="flex items-center justify-between mb-2">
          <span class="text-sm font-semibold text-gray-700">Tiến độ tổng</span>
          <span class="text-lg font-bold" :class="progress.pct === 100 ? 'text-green-600' : 'text-primary-600'">
            {{ progress.done }}/{{ progress.total }}
          </span>
        </div>
        <div class="w-full h-3 bg-gray-100 rounded-full overflow-hidden">
          <div
            class="h-full rounded-full transition-all duration-500"
            :class="progress.pct === 100 ? 'bg-green-500' : 'bg-primary-500'"
            :style="{ width: progress.pct + '%' }"
          ></div>
        </div>
        <p class="text-xs text-gray-500 mt-1.5">{{ progress.pct }}% hoàn thành</p>
      </div>

      <!-- Cabinet List -->
      <div>
        <h3 class="text-base font-bold text-gray-900 mb-3">Danh sách tủ cáp ({{ planDetails.length }})</h3>

        <div v-if="planDetails.length === 0" class="text-center py-10">
          <Save class="w-14 h-14 mx-auto text-gray-300" />
          <p class="font-semibold text-gray-700 mt-3">Chưa có tủ nào</p>
        </div>

        <div v-else class="space-y-3">
          <button
            v-for="plan in planDetails"
            :key="plan.id"
            @click="goToInspection(plan)"
            class="w-full text-left rounded-2xl bg-white border border-gray-200 p-4 hover:border-primary-300 hover:shadow-md active:scale-[0.98] transition-all"
          >
            <div class="flex items-center justify-between">
              <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2">
                  <h4 class="font-bold text-gray-900">{{ plan.cabinet_code }}</h4>
                  <span 
                    v-if="plan.status === 'done'" 
                    class="text-xs font-semibold px-2 py-0.5 rounded-full bg-green-50 text-green-700"
                  >✓ Đã kiểm tra</span>
                  <span 
                    v-else 
                    class="text-xs font-semibold px-2 py-0.5 rounded-full bg-amber-50 text-amber-700"
                  >Chưa kiểm tra</span>
                </div>
                <p v-if="plan.cabinet?.bts_site" class="text-sm text-gray-500 mt-1">{{ plan.cabinet.bts_site }}</p>
              </div>
              <ChevronRight class="w-5 h-5 text-gray-400 shrink-0" />
            </div>

            <!-- Inspection result if exists -->
            <div v-if="plan.inspection" class="mt-3 pt-3 border-t border-gray-100 flex items-center gap-2 flex-wrap">
              <span
                class="text-xs font-bold px-2.5 py-1 rounded-full shrink-0"
                :class="plan.inspection.final_result === 'PASS' ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700'"
              >
                {{ plan.inspection.final_result === 'PASS' ? 'ĐẠT' : 'KHÔNG ĐẠT' }}
              </span>
              <span class="text-xs font-bold px-2.5 py-1 rounded-full bg-primary-50 text-primary-700 shrink-0">Điểm gốc: {{ plan.inspection.total_score }}</span>
              <span v-if="plan.inspection.critical_errors_count > 0" class="text-xs font-bold px-2 py-1 rounded-full bg-red-50 text-red-700 flex items-center gap-1 shrink-0">
                <AlertTriangle class="w-3.5 h-3.5" />
                {{ plan.inspection.critical_errors_count }} lỗi nghiêm trọng
              </span>
            </div>
          </button>
        </div>
      </div>
    </template>

    <!-- Error -->
    <div v-else class="text-center py-10">
      <p class="text-gray-500">Không thể tải dữ liệu lô kiểm tra.</p>
      <button @click="fetchData" class="mt-3 px-4 py-2 bg-primary-600 text-white rounded-xl text-sm font-semibold">Thử lại</button>
    </div>
  </div>
</template>

<script setup>
import { AlertTriangle, ChevronRight, Save, Calendar, ListTodo, ChevronLeft } from 'lucide-vue-next'

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

    // ✅ OPTIMIZED: 2 API calls thay vì N+1
    const [batchData, plansRes] = await Promise.all([
      batchService.getBatchById(batchId),
      api.get(`/batches/${batchId}/plans`)
    ])

    batch.value = batchData
    // ✅ Direct access: inspection đã có sẵn trong plans response
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
