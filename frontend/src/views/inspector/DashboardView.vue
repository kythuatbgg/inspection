<template>
  <div class="w-full h-full md:absolute md:inset-0 md:bg-slate-50 relative overflow-y-auto">
    <!-- Responsive layout chassis: Tight on mobile, wide on desktop -->
    <div class="p-4 md:p-8 space-y-5 md:space-y-8 max-w-5xl mx-auto pb-28 flex flex-col min-w-0">
      
      <!-- Stats System -->
      <div v-if="!loading" class="flex flex-col md:flex-row gap-3 md:gap-5">
        
        <!-- Hero Stat (Total Plans) -->
        <div class="rounded-lg bg-white border border-slate-200 p-5 md:p-6 flex-1 md:min-w-[280px] shadow-sm flex items-center justify-between">
          <div>
            <p class="text-[11px] md:text-sm text-slate-500 font-bold uppercase tracking-widest mb-1 md:mb-1.5">Tổng nhiệm vụ</p>
            <p class="text-4xl md:text-5xl font-bold text-slate-900 tracking-tight font-heading">{{ stats.totalPlans }}</p>
          </div>
          <div class="w-12 h-12 md:w-14 md:h-14 rounded-lg bg-primary-50 flex items-center justify-center">
            <ListTodo class="w-6 h-6 md:w-7 md:h-7 text-primary-600" />
          </div>
        </div>

        <!-- Secondary Stats Grid ("Pro Max" Style) -->
        <!-- Mobile: 2x2 grid. Desktop: 1x4 grid. -->
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 md:gap-4 flex-[2.5]">
          <!-- Stat 1 -->
          <div class="rounded-lg bg-white border border-slate-200 p-4 md:p-5 shadow-sm hover:bg-slate-50 transition-colors">
            <div class="w-9 h-9 md:w-10 md:h-10 rounded-lg bg-amber-50 flex items-center justify-center mb-3">
              <Clock class="w-4.5 h-4.5 md:w-5 md:h-5 text-amber-500" />
            </div>
            <p class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ stats.planned }}</p>
            <p class="text-[11px] md:text-xs text-slate-500 font-medium mt-1 truncate uppercase tracking-widest">Chưa KT</p>
          </div>
          
          <!-- Stat 2 -->
          <div class="rounded-lg bg-white border border-slate-200 p-4 md:p-5 shadow-sm hover:bg-slate-50 transition-colors">
            <div class="w-9 h-9 md:w-10 md:h-10 rounded-lg bg-emerald-50 flex items-center justify-center mb-3">
              <Check class="w-4.5 h-4.5 md:w-5 md:h-5 text-emerald-500" />
            </div>
            <p class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ stats.done }}</p>
            <p class="text-[11px] md:text-xs text-slate-500 font-medium mt-1 truncate uppercase tracking-widest">Hoàn thành</p>
          </div>

          <!-- Stat 3 -->
          <div class="rounded-lg bg-white border border-slate-200 p-4 md:p-5 shadow-sm hover:bg-slate-50 transition-colors">
            <div class="w-9 h-9 md:w-10 md:h-10 rounded-lg bg-blue-50 flex items-center justify-center mb-3">
              <FileStack class="w-4.5 h-4.5 md:w-5 md:h-5 text-primary-500" />
            </div>
            <p class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ stats.totalBatches }}</p>
            <p class="text-[11px] md:text-xs text-slate-500 font-medium mt-1 truncate uppercase tracking-widest">Lô đề xuất</p>
          </div>

          <!-- Stat 4 -->
          <div class="rounded-lg bg-white border border-slate-200 p-4 md:p-5 shadow-sm hover:bg-slate-50 transition-colors">
            <div class="w-9 h-9 md:w-10 md:h-10 rounded-lg bg-emerald-50 flex items-center justify-center mb-3">
              <ShieldCheck class="w-4.5 h-4.5 md:w-5 md:h-5 text-emerald-600" />
            </div>
            <p class="text-2xl md:text-3xl font-bold text-slate-900 tracking-tight">{{ completionPercent }}%</p>
            <p class="text-[11px] md:text-xs text-slate-500 font-medium mt-1 truncate uppercase tracking-widest">Tổng tiến độ</p>
          </div>
        </div>
      </div>

      <!-- Active Batches Section -->
      <div>
        <h3 class="text-lg md:text-xl font-bold text-slate-900 mb-4 tracking-tight font-heading">Lô đang hoạt động</h3>

        <!-- Loading -->
        <div v-if="loading" class="flex flex-col items-center justify-center py-12 md:py-20">
          <Loader2 class="w-8 h-8 animate-spin text-slate-400 mb-4" />
          <p class="text-sm font-medium text-slate-500">Đang tải biểu đồ...</p>
        </div>

        <!-- Empty State -->
        <div v-else-if="activeBatches.length === 0" class="flex flex-col items-center justify-center py-16 md:py-24 text-center">
          <div class="w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center mb-5">
            <FileStack class="w-8 h-8 text-slate-300" />
          </div>
          <p class="font-bold text-slate-800 text-lg md:text-xl tracking-tight">Không có lô kiểm tra nào</p>
          <p class="text-sm text-slate-500 mt-2 max-w-xs leading-relaxed">Khi quản trị viên tạo và giao lô kiểm tra, chúng sẽ xuất hiện tại đây.</p>
        </div>

        <!-- Batches List -->
        <div v-else class="space-y-3.5 md:space-y-4">
          <button
            v-for="batch in activeBatches"
            :key="batch.id"
            @click="goToBatch(batch.id)"
            class="w-full text-left rounded-lg bg-white border border-slate-200 p-5 md:p-6 active:scale-[0.98] transition-all cursor-pointer shadow-sm hover:bg-slate-50 flex flex-col"
          >
            <!-- Batch Header -->
            <div class="flex items-start justify-between gap-4 w-full">
              <div class="flex-1 min-w-0 pr-3">
                <h4 class="font-bold text-[15px] md:text-lg text-slate-900 truncate leading-tight font-heading">{{ batch.name }}</h4>
                <p class="text-[13px] md:text-sm text-slate-500 font-medium mt-1.5 truncate">{{ batch.checklist?.name || 'Mẫu Checklist tiêu chuẩn' }}</p>
              </div>
              <span
                class="shrink-0 text-[10px] md:text-[11px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-lg"
                :class="batch.status === 'completed'
                  ? 'bg-success/10 text-success'
                  : 'bg-warning/10 text-warning'"
              >
                {{ statusLabel(batch.status) }}
              </span>
            </div>

            <!-- Crisp Progress Bar -->
            <div class="mt-5 md:mt-6 w-full">
              <div class="flex items-center justify-between text-[11px] md:text-xs mb-2">
                <span class="text-slate-500 font-bold uppercase tracking-widest">Tiến độ</span>
                <span class="font-bold tracking-tight" :class="batchProgress(batch) === 100 ? 'text-success' : 'text-primary-600'">
                  {{ batchProgress(batch) }}%
                </span>
              </div>
              <div class="w-full h-1.5 md:h-2 bg-slate-100 rounded-full overflow-hidden">
                <div
                  class="h-full rounded-full transition-all duration-700 ease-out"
                  :class="batchProgress(batch) === 100 ? 'bg-success' : 'bg-primary-600'"
                  :style="{ width: batchProgress(batch) + '%' }"
                ></div>
              </div>
            </div>

            <!-- Meta Data (Date & Count) -->
            <div class="flex items-center gap-4 mt-4 md:mt-5 text-[11px] md:text-sm text-slate-500 font-medium uppercase tracking-wide">
              <span class="flex items-center gap-1.5">
                <Calendar class="w-3.5 h-3.5 md:w-4 md:h-4 text-slate-400" />
                {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
              </span>
              <span class="flex items-center gap-1.5">
                <FileStack class="w-3.5 h-3.5 md:w-4 md:h-4 text-slate-400" />
                {{ batch.plan_details?.length || 0 }} TỦ
              </span>
            </div>
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { FileStack, Calendar, ShieldCheck, Check, Clock, ListTodo, Loader2 } from 'lucide-vue-next'

import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import batchService from '@/services/batchService.js'

const router = useRouter()

const loading = ref(true)
const batches = ref([])

const activeBatches = computed(() => {
  return batches.value.filter(b => b.status !== 'completed')
})

const stats = computed(() => {
  let totalPlans = 0, planned = 0, done = 0

  batches.value.forEach(b => {
    const plans = b.plan_details || []
    totalPlans += plans.length
    planned += plans.filter(p => p.status === 'planned').length
    done += plans.filter(p => p.status === 'done').length
  })

  return {
    totalBatches: batches.value.length,
    totalPlans,
    planned,
    done
  }
})

const completionPercent = computed(() => {
  if (stats.value.totalPlans === 0) return 0
  return Math.round((stats.value.done / stats.value.totalPlans) * 100)
})

const batchProgress = (batch) => {
  const plans = batch.plan_details || []
  if (plans.length === 0) return 0
  const done = plans.filter(p => p.status === 'done').length
  return Math.round((done / plans.length) * 100)
}

const statusLabel = (status) => {
  const map = { pending: 'Chờ', active: 'Đang chạy', completed: 'Hoàn thành' }
  return map[status] || status
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const d = new Date(dateStr)
  return `${d.getDate()}/${d.getMonth() + 1}`
}

const goToBatch = (id) => {
  router.push({ name: 'inspector-batch-detail', params: { id } })
}

const fetchData = async () => {
  loading.value = true
  try {
    const res = await batchService.getBatches({ per_page: 50, approval_status: 'approved' })
    batches.value = res.data || res || []

    const detailPromises = batches.value.map(b => batchService.getBatchById(b.id))
    const details = await Promise.all(detailPromises)
    
    details.forEach((detail, i) => {
      if (detail && batches.value[i]) {
        batches.value[i].plan_details = detail.plan_details || []
        batches.value[i].checklist = detail.checklist || batches.value[i].checklist
      }
    })
  } catch (e) {
    console.error('Failed to fetch batches:', e)
  } finally {
    loading.value = false
  }
}

onMounted(fetchData)
</script>
