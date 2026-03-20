<template>
  <div class="space-y-6">
    <div class="flex items-center justify-between">
      <h1 class="text-2xl font-bold text-slate-900">Cài đặt hệ thống</h1>
    </div>

    <!-- Storage Stats -->
    <div class="bg-white rounded-lg border border-slate-200 shadow-sm overflow-hidden">
      <div class="px-6 py-4 border-b border-slate-200 flex items-center justify-between">
        <div class="flex items-center gap-3">
          <div class="p-2 bg-primary-50 rounded-lg">
            <Server class="w-5 h-5 text-primary-600" />
          </div>
          <h2 class="text-lg font-bold text-slate-900">Quản lý lưu trữ</h2>
        </div>
        <button @click="fetchStats" class="text-sm text-primary-600 font-medium hover:text-primary-700 flex items-center gap-1">
          <RefreshCw class="w-4 h-4" />
          Làm mới
        </button>
      </div>

      <div v-if="loading" class="p-6 text-center text-slate-500">
        <Loader2 class="w-6 h-6 animate-spin mx-auto mb-2" />
        Đang tải thống kê...
      </div>

      <div v-else-if="stats" class="p-6">
        <!-- Stat Cards Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-4 mb-6">
          <!-- Orphan Files -->
          <div class="rounded-lg bg-warning/10 border border-amber-100 p-4">
            <p class="text-xs font-bold text-warning uppercase tracking-wide mb-1">File mồ côi</p>
            <p class="text-2xl font-bold text-amber-800">{{ stats.orphan_files_count }}</p>
            <p class="text-sm text-warning mt-1">{{ stats.orphan_files_size_mb }} MB</p>
          </div>

          <!-- Used Upload Files -->
          <div class="rounded-lg bg-sky-50 border border-sky-100 p-4">
            <p class="text-xs font-bold text-sky-600 uppercase tracking-wide mb-1">File upload đang dùng</p>
            <p class="text-2xl font-bold text-sky-800">{{ stats.used_upload_files_count }}</p>
            <p class="text-sm text-sky-600 mt-1">{{ stats.used_upload_files_size_mb }} MB</p>
          </div>

          <!-- Spatie Media -->
          <div class="rounded-lg bg-success/10 border border-green-100 p-4">
            <p class="text-xs font-bold text-success uppercase tracking-wide mb-1">Ảnh Spatie (Đã lưu)</p>
            <p class="text-2xl font-bold text-green-800">{{ stats.media_count }}</p>
            <p class="text-sm text-success mt-1">{{ stats.media_size_mb }} MB</p>
          </div>

          <!-- Total -->
          <div class="rounded-lg bg-primary-50 border border-blue-100 p-4">
            <p class="text-xs font-bold text-primary-600 uppercase tracking-wide mb-1">Tổng dung lượng</p>
            <p class="text-2xl font-bold text-primary-800">{{ stats.total_size_mb }} MB</p>
            <p class="text-sm text-primary-600 mt-1">Upload + Media</p>
          </div>
        </div>

        <div class="mb-6 rounded-lg bg-slate-50 border border-slate-200 p-4 text-sm text-slate-600 space-y-1">
          <p><span class="font-semibold text-slate-800">Tổng file upload:</span> {{ stats.upload_files_count }} file · {{ stats.upload_files_size_mb }} MB</p>
          <p>File mồ côi là ảnh đã upload nhưng chưa còn được tham chiếu trong báo cáo kiểm tra.</p>
        </div>

        <!-- Cleanup Controls -->
        <div class="rounded-lg bg-slate-50 border border-slate-200 p-5">
          <h3 class="text-sm font-bold text-slate-700 mb-3">Dọn dẹp file tạm</h3>
          <p class="text-xs text-slate-500 mb-4">Xóa các file mồ côi đã upload nhưng không còn được gắn vào báo cáo kiểm tra. Tự động chạy hàng ngày lúc 3:00 AM nếu scheduler đang hoạt động.</p>

          <div class="flex items-center gap-3">
            <select v-model="cleanupHours" class="text-sm border border-slate-300 rounded-lg px-3 py-2 bg-white">
              <option :value="0">⚠ Tất cả file tạm</option>
              <option :value="1">Cũ hơn 1 giờ</option>
              <option :value="6">Cũ hơn 6 giờ</option>
              <option :value="24">Cũ hơn 24 giờ</option>
              <option :value="72">Cũ hơn 3 ngày</option>
              <option :value="168">Cũ hơn 7 ngày</option>
            </select>

            <button
              @click="runCleanup"
              :disabled="cleaningUp"
              class="px-4 py-2 bg-red-600 text-white text-sm font-bold rounded-lg hover:bg-red-700 disabled:opacity-50 transition-colors flex items-center gap-2"
            >
              <Loader2 v-if="cleaningUp" class="w-4 h-4 animate-spin" />
              <template v-else>
                <Trash2 class="w-4 h-4" />
                <span>Chạy dọn dẹp</span>
              </template>
            </button>
          </div>

          <!-- Cleanup Result -->
          <div v-if="cleanupMessage" class="mt-3 px-3 py-2 rounded-lg text-sm font-medium" :class="cleanupMessage.includes('error') ? 'bg-danger/10 text-danger' : 'bg-success/10 text-success'">
            ✓ {{ cleanupMessage }}
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { Trash2, Loader2, RefreshCw, Server } from 'lucide-vue-next'

import { ref, onMounted } from 'vue'
import api from '@/services/api'

const loading = ref(true)
const stats = ref(null)
const cleanupHours = ref(24)
const cleaningUp = ref(false)
const cleanupMessage = ref('')

const fetchStats = async () => {
  loading.value = true
  try {
    const res = await api.get('/admin/storage-stats')
    stats.value = res.data
  } catch (e) {
    console.error('Failed to fetch storage stats:', e)
  } finally {
    loading.value = false
  }
}

const runCleanup = async () => {
  if (cleanupHours.value === 0) {
    const confirmed = window.confirm('Xóa toàn bộ file mồ côi hiện có? Các file đang được báo cáo sử dụng sẽ được giữ lại.')
    if (!confirmed) return
  }

  cleaningUp.value = true
  cleanupMessage.value = ''
  try {
    const res = await api.post('/admin/storage-cleanup', { hours: cleanupHours.value })
    cleanupMessage.value = res.data?.message || 'Dọn dẹp hoàn tất'
    // Refresh stats
    await fetchStats()
  } catch (e) {
    cleanupMessage.value = 'error: ' + (e.response?.data?.message || 'Không thể dọn dẹp')
  } finally {
    cleaningUp.value = false
  }
}

onMounted(fetchStats)
</script>
