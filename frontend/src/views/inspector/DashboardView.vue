<template>
  <div class="space-y-5">
    <!-- Welcome -->
    <div class="rounded-2xl bg-gradient-to-br from-primary-600 to-primary-700 p-5 text-white">
      <h2 class="text-lg font-bold leading-tight">Xin chào, {{ userName }}!</h2>
      <p class="text-white/70 text-sm mt-1">{{ todayFormatted }}</p>
    </div>

    <!-- Stats -->
    <div v-if="!loading" class="space-y-3">
      <!-- Hero Stat -->
      <div class="rounded-2xl bg-white border border-slate-200 p-5 flex items-center justify-between">
        <div>
          <p class="text-xs text-slate-500 font-medium uppercase tracking-wide">Tổng nhiệm vụ</p>
          <p class="text-3xl font-bold text-slate-900 mt-1 tracking-tight">{{ stats.totalPlans }}</p>
        </div>
        <div class="w-12 h-12 rounded-xl bg-primary-50 flex items-center justify-center">
          <ListTodo class="w-6 h-6 text-primary-600" />
        </div>
      </div>

      <!-- 2x2 Stat Grid -->
      <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
          <div class="w-8 h-8 rounded-lg bg-amber-50 flex items-center justify-center mb-2.5">
            <Clock class="w-4.5 h-4.5 text-amber-500" />
          </div>
          <p class="text-2xl font-bold text-slate-900 tracking-tight">{{ stats.planned }}</p>
          <p class="text-[11px] text-slate-400 font-medium mt-0.5">Chưa kiểm tra</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
          <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center mb-2.5">
            <Check class="w-4.5 h-4.5 text-emerald-500" />
          </div>
          <p class="text-2xl font-bold text-slate-900 tracking-tight">{{ stats.done }}</p>
          <p class="text-[11px] text-slate-400 font-medium mt-0.5">Đã hoàn thành</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
          <div class="w-8 h-8 rounded-lg bg-blue-50 flex items-center justify-center mb-2.5">
            <FileStack class="w-4.5 h-4.5 text-primary-500" />
          </div>
          <p class="text-2xl font-bold text-slate-900 tracking-tight">{{ stats.totalBatches }}</p>
          <p class="text-[11px] text-slate-400 font-medium mt-0.5">Lô kiểm tra</p>
        </div>
        <div class="rounded-2xl bg-white border border-slate-200 p-4">
          <div class="w-8 h-8 rounded-lg bg-emerald-50 flex items-center justify-center mb-2.5">
            <ShieldCheck class="w-4.5 h-4.5 text-emerald-600" />
          </div>
          <p class="text-2xl font-bold text-slate-900 tracking-tight">{{ completionPercent }}%</p>
          <p class="text-[11px] text-slate-400 font-medium mt-0.5">Tiến độ</p>
        </div>
      </div>
    </div>

    <!-- Active Batches -->
    <div>
      <h3 class="text-base font-bold text-slate-900 mb-3">Lô đang hoạt động</h3>

      <div v-if="loading" class="flex justify-center py-10">
        <Loader2 class="w-7 h-7 animate-spin text-primary-500" />
      </div>

      <div v-else-if="activeBatches.length === 0" class="flex flex-col items-center justify-center py-12 text-center">
        <div class="w-14 h-14 rounded-2xl bg-slate-100 flex items-center justify-center mb-4">
          <FileStack class="w-7 h-7 text-slate-300" />
        </div>
        <p class="font-semibold text-slate-700">Chưa có lô kiểm tra nào</p>
        <p class="text-sm text-slate-400 mt-1">Khi admin tạo lô mới, bạn sẽ thấy ở đây</p>
      </div>

      <div v-else class="space-y-3">
        <button
          v-for="batch in activeBatches"
          :key="batch.id"
          @click="goToBatch(batch.id)"
          class="w-full text-left rounded-2xl bg-white border border-slate-200 p-5 active:scale-[0.98] transition-all cursor-pointer"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0 pr-3">
              <h4 class="font-bold text-slate-900 truncate leading-tight">{{ batch.name }}</h4>
              <p class="text-xs text-slate-400 mt-1">{{ batch.checklist?.name || 'Checklist' }}</p>
            </div>
            <span
              class="shrink-0 text-[10px] font-bold uppercase tracking-wider px-2.5 py-1 rounded-full"
              :class="batch.status === 'completed'
                ? 'bg-emerald-50 text-emerald-600'
                : 'bg-amber-50 text-amber-600'"
            >
              {{ statusLabel(batch.status) }}
            </span>
          </div>

          <!-- Progress -->
          <div class="mt-4">
            <div class="flex items-center justify-between text-xs mb-1.5">
              <span class="text-slate-400 font-medium">Tiến độ</span>
              <span class="font-bold" :class="batchProgress(batch) === 100 ? 'text-emerald-600' : 'text-primary-600'">
                {{ batchProgress(batch) }}%
              </span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="batchProgress(batch) === 100 ? 'bg-emerald-500' : 'bg-primary-500'"
                :style="{ width: batchProgress(batch) + '%' }"
              ></div>
            </div>
          </div>

          <!-- Meta -->
          <div class="flex items-center gap-4 mt-3 text-xs text-slate-400">
            <span class="flex items-center gap-1">
              <Calendar class="w-3.5 h-3.5" />
              {{ formatDate(batch.start_date) }} — {{ formatDate(batch.end_date) }}
            </span>
            <span class="flex items-center gap-1">
              <FileStack class="w-3.5 h-3.5" />
              {{ batch.plan_details?.length || 0 }} tủ
            </span>
          </div>
        </button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { FileStack, Calendar, ShieldCheck, Check, Clock, ListTodo, Loader2 } from 'lucide-vue-next'

import { ref, computed, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { useAuthStore } from '@/stores/auth'
import batchService from '@/services/batchService.js'

const router = useRouter()
const authStore = useAuthStore()

const loading = ref(true)
const batches = ref([])

const userName = computed(() => authStore.user?.name || 'Nhân viên')

const todayFormatted = computed(() => {
  const d = new Date()
  const days = ['Chủ Nhật', 'Thứ Hai', 'Thứ Ba', 'Thứ Tư', 'Thứ Năm', 'Thứ Sáu', 'Thứ Bảy']
  return `${days[d.getDay()]}, ${d.getDate()}/${d.getMonth() + 1}/${d.getFullYear()}`
})

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
