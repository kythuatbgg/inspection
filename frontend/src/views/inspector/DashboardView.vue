<template>
  <div class="space-y-5">
    <!-- Welcome Card -->
    <div class="rounded-2xl bg-gradient-to-br from-primary-600 to-primary-700 p-5 text-white">
      <h2 class="text-xl font-bold">Xin chào, {{ userName }}!</h2>
      <p class="text-white/80 text-sm mt-1">{{ todayFormatted }}</p>
    </div>

    <!-- Pro Max Stats Grid -->
    <div v-if="!loading" class="space-y-3">
      <!-- Hero stat -->
      <div class="rounded-2xl bg-dark-surface border border-gray-700/50 p-5 flex items-center justify-between">
        <div>
          <p class="text-sm text-gray-500 font-medium">Tổng nhiệm vụ</p>
          <p class="text-3xl font-bold text-gray-100 mt-1">{{ stats.totalPlans }}</p>
        </div>
        <div class="w-14 h-14 rounded-2xl bg-primary-500/10 flex items-center justify-center">
          <ListTodo class="w-7 h-7 text-primary-400" />
        </div>
      </div>

      <!-- 2x2 Grid -->
      <div class="grid grid-cols-2 gap-3">
        <div class="rounded-2xl bg-dark-surface border border-gray-700/50 p-4">
          <div class="w-9 h-9 rounded-xl bg-amber-500/10 flex items-center justify-center mb-2">
            <Clock class="w-5 h-5 text-amber-600" />
          </div>
          <p class="text-2xl font-bold text-gray-100">{{ stats.planned }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Chưa kiểm tra</p>
        </div>
        <div class="rounded-2xl bg-dark-surface border border-gray-700/50 p-4">
          <div class="w-9 h-9 rounded-xl bg-green-500/10 flex items-center justify-center mb-2">
            <Check class="w-5 h-5 text-green-600" />
          </div>
          <p class="text-2xl font-bold text-gray-100">{{ stats.done }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Đã hoàn thành</p>
        </div>
        <div class="rounded-2xl bg-dark-surface border border-gray-700/50 p-4">
          <div class="w-9 h-9 rounded-xl bg-blue-500/10 flex items-center justify-center mb-2">
            <FileStack class="w-5 h-5 text-blue-400" />
          </div>
          <p class="text-2xl font-bold text-gray-100">{{ stats.totalBatches }}</p>
          <p class="text-xs text-gray-500 mt-0.5">Lô kiểm tra</p>
        </div>
        <div class="rounded-2xl bg-dark-surface border border-gray-700/50 p-4">
          <div class="w-9 h-9 rounded-xl bg-emerald-50 flex items-center justify-center mb-2">
            <ShieldCheck class="w-5 h-5 text-emerald-600" />
          </div>
          <p class="text-2xl font-bold text-gray-100">{{ completionPercent }}%</p>
          <p class="text-xs text-gray-500 mt-0.5">Tiến độ</p>
        </div>
      </div>
    </div>



    <!-- Active Batches -->
    <div>
      <h3 class="text-lg font-bold text-gray-100 mb-3">Lô kiểm tra đang hoạt động</h3>

      <div v-if="!loading && activeBatches.length === 0" class="text-center py-10">
        <Save class="w-16 h-16 mx-auto text-gray-300" />
        <p class="font-semibold text-gray-300 mt-4">Chưa có lô kiểm tra nào</p>
        <p class="text-sm text-gray-500 mt-1">Khi admin tạo lô mới, bạn sẽ thấy ở đây</p>
      </div>

      <div v-else class="space-y-3">
        <button
          v-for="batch in activeBatches"
          :key="batch.id"
          @click="goToBatch(batch.id)"
          class="w-full text-left rounded-2xl bg-dark-surface border border-gray-700/50 p-5 hover:border-primary-300 hover:shadow-md active:scale-[0.98] transition-all"
        >
          <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
              <h4 class="font-bold text-gray-100 truncate">{{ batch.name }}</h4>
              <p class="text-sm text-gray-500 mt-1">{{ batch.checklist?.name || 'Checklist' }}</p>
            </div>
            <span
              class="ml-3 shrink-0 text-xs font-semibold px-2.5 py-1 rounded-full"
              :class="batch.status === 'completed'
                ? 'bg-green-500/10 text-green-400'
                : batch.status === 'active' || batch.status === 'pending'
                  ? 'bg-amber-500/10 text-amber-400'
                  : 'bg-dark-elevated text-gray-500'"
            >
              {{ statusLabel(batch.status) }}
            </span>
          </div>

          <!-- Progress Bar -->
          <div class="mt-4">
            <div class="flex items-center justify-between text-xs mb-1.5">
              <span class="text-gray-500">Tiến độ</span>
              <span class="font-bold" :class="batchProgress(batch) === 100 ? 'text-green-600' : 'text-primary-400'">
                {{ batchProgress(batch) }}%
              </span>
            </div>
            <div class="w-full h-2 bg-dark-elevated rounded-full overflow-hidden">
              <div
                class="h-full rounded-full transition-all duration-500"
                :class="batchProgress(batch) === 100 ? 'bg-green-500/100' : 'bg-primary-500/100'"
                :style="{ width: batchProgress(batch) + '%' }"
              ></div>
            </div>
          </div>

          <!-- Meta row -->
          <div class="flex items-center gap-4 mt-3 text-xs text-gray-500">
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
import { FileStack, Calendar, Save, ShieldCheck, Check, Clock, ListTodo } from 'lucide-vue-next'

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
    // Backend auto-filters by inspector's user_id
    const res = await batchService.getBatches({ per_page: 50 })
    // getBatches returns response.data which has { data: [...], current_page, ... }
    batches.value = res.data || res || []

    // For each batch, we need plan_details. The list endpoint doesn't include them.
    // Fetch batch details with plan_details for each batch.
    const detailPromises = batches.value.map(b => batchService.getBatchById(b.id))
    const details = await Promise.all(detailPromises)
    
    // Merge plan_details into batches
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
