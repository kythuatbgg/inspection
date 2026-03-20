<template>
  <div class="space-y-8">
    <!-- LÔ KIỂM TRA -->
    <div v-if="!loading && !error">
      <h2 class="text-lg font-semibold text-slate-900 mb-3 tracking-tight font-heading">Thống kê Lô kiểm tra</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        
        <!-- Card 1: Lô hoàn thành & Tổng số lô -->
        <div class="card p-5 hover:shadow-md transition-shadow bg-white border border-slate-200 rounded-lg flex flex-col justify-between">
          <div class="flex items-start justify-between mb-4">
            <div>
              <p class="text-[11px] md:text-sm font-bold text-slate-500 uppercase tracking-widest mb-1.5">Tiến độ thi công Lô</p>
              <div class="flex items-baseline gap-2">
                <p class="text-3xl md:text-4xl font-bold text-slate-900 font-heading tracking-tight">{{ stats.batches.completed }}</p>
                <span class="text-sm md:text-base font-bold text-slate-400 font-heading tracking-tight">/ {{ stats.batches.total }}</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-lg bg-emerald-100 flex items-center justify-center shrink-0">
              <Check class="w-6 h-6 text-success" />
            </div>
          </div>
          <!-- Progress -->
          <div class="w-full mt-auto">
            <div class="flex items-center justify-between text-[10px] md:text-xs font-bold text-slate-500 mb-1.5 uppercase tracking-wide">
              <span>Tỷ lệ hoàn thành</span>
              <span class="text-success">{{ stats.batches.completed_percent }}%</span>
            </div>
            <div class="w-full h-1.5 bg-slate-100 rounded-full overflow-hidden">
              <div class="h-full bg-success transition-all duration-700 ease-out" :style="{ width: stats.batches.completed_percent + '%' }"></div>
            </div>
          </div>
        </div>

        <!-- Card 2: Chờ duyệt -->
        <div class="card p-5 hover:shadow-md transition-shadow bg-white border border-slate-200 rounded-lg">
          <div class="flex items-start justify-between">
            <div>
              <p class="text-[11px] md:text-sm font-bold text-slate-500 uppercase tracking-widest mb-1.5">Chờ phê duyệt</p>
              <p class="text-3xl md:text-4xl font-bold text-slate-900 font-heading tracking-tight">{{ stats.batches.waiting_approval }}</p>
              <div class="mt-3">
                <span class="text-[10px] md:text-xs font-bold uppercase tracking-wider text-amber-600 bg-amber-50 border border-amber-100 px-2 py-1 rounded-md">Đã xong tủ</span>
              </div>
            </div>
            <div class="w-12 h-12 rounded-lg bg-amber-100 flex items-center justify-center shrink-0">
              <Clock class="w-6 h-6 text-warning" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- TỦ CÁP (MẪU KIỂM TRA) -->
    <div v-if="!loading && !error">
      <h2 class="text-lg font-semibold text-slate-900 mb-3 tracking-tight font-heading mt-2">Thống kê Tủ cáp</h2>
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <!-- Card 1: Tổng mẫu tủ -->
        <div class="card p-4 hover:shadow-md transition-shadow bg-white border border-slate-200 rounded-lg flex flex-col justify-center">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
              <ListTodo class="w-5 h-5 text-indigo-600" />
            </div>
            <div>
              <p class="text-2xl font-bold text-slate-900 font-heading tracking-tight">{{ stats.plans.total }}</p>
              <p class="text-sm font-medium text-slate-500">Mẫu tủ trên tuyến</p>
            </div>
          </div>
        </div>

        <!-- Card 2: Tiến độ tủ -->
        <div class="card p-4 hover:shadow-md transition-shadow bg-white border border-slate-200 rounded-lg flex flex-col justify-center">
          <div class="flex items-center gap-3 w-full">
            <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center shrink-0">
              <ShieldCheck class="w-5 h-5 text-slate-600" />
            </div>
            <div class="flex-1 pr-2">
              <div class="flex items-baseline justify-between mb-1.5">
                <p class="text-2xl font-bold text-slate-900 font-heading tracking-tight">{{ stats.plans.completed }}</p>
                <span class="text-[11px] font-bold text-primary-600 uppercase tracking-widest">{{ stats.plans.completed_percent }}%</span>
              </div>
              <!-- Progress mini bar -->
              <div class="w-full h-1 bg-slate-100 rounded-full overflow-hidden">
                <div class="h-full bg-primary-600 transition-all duration-700 ease-out" :style="{ width: stats.plans.completed_percent + '%' }"></div>
              </div>
            </div>
          </div>
        </div>

        <!-- Card 3: Đạt -->
        <div class="card p-4 hover:shadow-md transition-shadow bg-white border border-slate-200 rounded-lg flex flex-col justify-center">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center shrink-0">
              <Check class="w-5 h-5 text-success" />
            </div>
            <div>
              <p class="text-2xl font-bold text-success font-heading tracking-tight">{{ stats.plans.passed }}</p>
              <p class="text-sm font-medium text-slate-500">Kết quả <span class="font-bold text-success uppercase text-[11px] tracking-widest ml-0.5">Đạt</span></p>
            </div>
          </div>
        </div>

        <!-- Card 4: Không đạt -->
        <div class="card p-4 hover:shadow-md transition-shadow bg-white border border-slate-200 rounded-lg flex flex-col justify-center">
          <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
              <XCircle class="w-5 h-5 text-danger" />
            </div>
            <div>
              <p class="text-2xl font-bold text-danger font-heading tracking-tight">{{ stats.plans.failed }}</p>
              <p class="text-sm font-medium text-slate-500">Kết quả <span class="font-bold text-danger uppercase text-[11px] tracking-widest ml-0.5">Lỗi</span></p>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Batches -->
    <div class="card bg-white border border-slate-200 rounded-lg shadow-sm">
      <div class="p-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-900 font-heading tracking-tight">Lô kiểm tra gần đây</h2>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="p-8 flex flex-col items-center text-center text-slate-500">
        <Loader2 class="w-8 h-8 text-primary-500 animate-spin mb-3" />
        <p>Đang tải dữ liệu...</p>
      </div>

      <!-- Error -->
      <div v-else-if="error" class="p-8 text-center text-red-500">
        <div class="w-12 h-12 bg-red-50 text-red-500 rounded-full flex items-center justify-center mx-auto mb-3">
          <XCircle class="w-6 h-6" />
        </div>
        <p class="font-medium">{{ error }}</p>
      </div>

      <!-- Data -->
      <div v-else class="divide-y divide-slate-100">
        <div v-for="batch in recentBatches" :key="batch.id" class="p-4 md:px-5 flex items-center justify-between hover:bg-slate-50 transition-colors">
          <div>
            <p class="font-bold text-slate-900 tracking-tight font-heading">{{ batch.name || batch.title }}</p>
            <p class="text-[13px] text-slate-500 mt-1 font-medium"><Clock class="inline w-3.5 h-3.5 mr-1 align-text-bottom" /> Cập nhật lúc {{ formatDate(batch.created_at) }}</p>
          </div>
          <span class="text-[10px] py-1 px-2.5 uppercase font-bold tracking-widest rounded-md" :class="getStatusClass(batch.status)">{{ getStatusLabel(batch.status) }}</span>
        </div>

        <!-- Empty -->
        <div v-if="recentBatches.length === 0" class="p-8 text-center text-slate-500">
          <FileStack class="w-10 h-10 mx-auto text-slate-300 mb-3" />
          <p class="font-medium">Chưa có lô kiểm tra nào</p>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Clock, XCircle, ShieldCheck, FileStack, ListTodo, Check, Loader2 } from 'lucide-vue-next'

import { ref, onMounted } from 'vue'
import batchService from '@/services/batchService.js'
import api from '@/services/api.js'

const loading = ref(true)
const error = ref(null)
const recentBatches = ref([])

const stats = ref({
  batches: {
    total: 0,
    completed: 0,
    completed_percent: 0,
    waiting_approval: 0
  },
  plans: {
    total: 0,
    completed: 0,
    completed_percent: 0,
    passed: 0,
    failed: 0
  }
})

const getStatusClass = (status) => {
  const classes = {
    completed: 'bg-success/10 text-success',
    active: 'bg-primary-500/10 text-primary-600',
    pending: 'bg-warning/10 text-warning',
    in_progress: 'bg-primary-500/10 text-primary-600',
  }
  return classes[status] || 'bg-slate-100 text-slate-600'
}

const getStatusLabel = (status) => {
  const labels = {
    completed: 'Hoàn thành',
    active: 'Đang kiểm tra',
    pending: 'Chờ phân công',
    in_progress: 'Đang kiểm tra',
  }
  return labels[status] || 'Không xác định'
}

const formatDate = (dateStr) => {
  if (!dateStr) return ''
  const date = new Date(dateStr)
  return date.toLocaleDateString('vi-VN')
}

const fetchData = async () => {
  loading.value = true
  error.value = null

  try {
    const [statsRes, batchesRes] = await Promise.all([
      api.get('/dashboard/stats'),
      batchService.getBatches({ per_page: 5 })
    ])

    const s = statsRes.data
    
    if (s.batches && s.plans) {
      // Use new structured response
      stats.value.batches = s.batches
      stats.value.plans = s.plans
    } else {
      // Legacy fallback
      stats.value.batches.total = s.total_batches || 0
      stats.value.batches.completed = s.completed || 0
      stats.value.batches.waiting_approval = s.pending || 0
      stats.value.plans.failed = s.failed || 0
    }

    recentBatches.value = batchesRes.data || batchesRes || []
  } catch (e) {
    error.value = 'Không thể kết nối máy chủ dữ liệu'
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>
