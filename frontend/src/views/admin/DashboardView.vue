<template>
  <div class="space-y-6">
    <!-- Stats Cards -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
      <div class="card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-primary-100 flex items-center justify-center">
            <FileStack class="w-5 h-5 text-primary-600" />
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900">{{ stats.totalBatches }}</p>
            <p class="text-sm text-slate-500">Lô kiểm tra</p>
          </div>
        </div>
      </div>

      <div class="card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-green-100 flex items-center justify-center">
            <ShieldCheck class="w-5 h-5 text-success" />
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900">{{ stats.passed }}</p>
            <p class="text-sm text-slate-500">Đạt</p>
          </div>
        </div>
      </div>

      <div class="card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-red-100 flex items-center justify-center">
            <XCircle class="w-5 h-5 text-danger" />
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900">{{ stats.failed }}</p>
            <p class="text-sm text-slate-500">Không đạt</p>
          </div>
        </div>
      </div>

      <div class="card p-4">
        <div class="flex items-center gap-3">
          <div class="w-10 h-10 rounded-lg bg-amber-100 flex items-center justify-center">
            <Clock class="w-5 h-5 text-warning" />
          </div>
          <div>
            <p class="text-2xl font-bold text-slate-900">{{ stats.pending }}</p>
            <p class="text-sm text-slate-500">Đang chờ</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Recent Batches -->
    <div class="card">
      <div class="p-4 border-b border-slate-200">
        <h2 class="text-lg font-semibold text-slate-900">Lô kiểm tra gần đây</h2>
      </div>

      <!-- Loading -->
      <div v-if="loading" class="p-4 text-center text-slate-500">
        Đang tải...
      </div>

      <!-- Error -->
      <div v-else-if="error" class="p-4 text-center text-red-500">
        {{ error }}
      </div>

      <!-- Data -->
      <div v-else class="divide-y divide-gray-100">
        <div v-for="batch in recentBatches" :key="batch.id" class="p-4 flex items-center justify-between hover:bg-slate-50">
          <div>
            <p class="font-medium text-slate-900">{{ batch.name || batch.title }}</p>
            <p class="text-sm text-slate-500">{{ batch.plans_count || 0 }} tủ cáp • {{ formatDate(batch.created_at) }}</p>
          </div>
          <span :class="getStatusClass(batch.status)">{{ getStatusLabel(batch.status) }}</span>
        </div>

        <!-- Empty -->
        <div v-if="recentBatches.length === 0" class="p-4 text-center text-slate-500">
          Chưa có lô kiểm tra nào
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Clock, XCircle, ShieldCheck, FileStack } from 'lucide-vue-next'

import { ref, onMounted } from 'vue'
import batchService from '@/services/batchService.js'
import api from '@/services/api.js'

const loading = ref(true)
const error = ref(null)
const recentBatches = ref([])

const stats = ref({
  totalBatches: 0,
  passed: 0,
  failed: 0,
  pending: 0,
  inProgress: 0
})

const getStatusClass = (status) => {
  const classes = {
    completed: 'badge-success',
    in_progress: 'badge-warning',
    pending: 'badge-info',
    done: 'badge-success',
    'in-progress': 'badge-warning'
  }
  return classes[status] || 'badge-info'
}

const getStatusLabel = (status) => {
  const labels = {
    completed: 'Hoàn thành',
    in_progress: 'Đang kiểm tra',
    pending: 'Chờ xử lý',
    done: 'Hoàn thành',
    'in-progress': 'Đang kiểm tra'
  }
  return labels[status] || 'Chờ xử lý'
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
    // Fetch stats and recent batches in parallel
    const [statsRes, batchesRes] = await Promise.all([
      api.get('/dashboard/stats'),
      batchService.getBatches({ per_page: 5 })
    ])

    const s = statsRes.data
    stats.value.totalBatches = s.total_batches || 0
    stats.value.passed = s.completed || 0
    stats.value.pending = s.pending || 0
    stats.value.failed = s.failed || 0
    stats.value.inProgress = s.in_progress || 0

    recentBatches.value = batchesRes.data || []
  } catch (e) {
    error.value = 'Không thể tải dữ liệu'
    console.error(e)
  } finally {
    loading.value = false
  }
}

onMounted(() => {
  fetchData()
})
</script>
