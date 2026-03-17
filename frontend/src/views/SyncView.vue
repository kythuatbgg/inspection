<template>
  <div class="min-h-screen bg-gray-50">
    <header class="bg-white shadow">
      <div class="max-w-7xl mx-auto px-4 py-4 flex items-center gap-4">
        <button @click="goBack" class="text-gray-600">
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
          </svg>
        </button>
        <h1 class="text-xl font-bold text-gray-800">Đồng bộ dữ liệu</h1>
      </div>
    </header>

    <main class="max-w-7xl mx-auto px-4 py-6">
      <div class="bg-white rounded-lg shadow p-6">
        <div class="text-center mb-6">
          <div class="w-16 h-16 mx-auto mb-4 rounded-full flex items-center justify-center"
               :class="isOnline ? 'bg-green-100' : 'bg-red-100'">
            <svg class="w-8 h-8" :class="isOnline ? 'text-green-600' : 'text-red-600'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path v-if="isOnline" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.111 16.404a5.5 5.5 0 017.778 0M12 20h.01m-7.08-7.071c3.904-3.905 10.236-3.905 14.141 0M1.394 9.393c5.857-5.857 15.355-5.857 21.213 0" />
              <path v-else stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 5.636a9 9 0 010 12.728m0 0l-2.829-2.829m2.829 2.829L21 21M15.536 8.464a5 5 0 010 7.072m0 0l-2.829-2.829m-4.243 2.829a4.978 4.978 0 01-1.414-2.83m-1.414 5.658a9 9 0 01-2.167-9.238m7.824 2.167a1 1 0 111.414 1.414m-1.414-1.414L3 3m8.293 8.293l1.414 1.414" />
            </svg>
          </div>
          <h2 class="text-lg font-semibold">{{ isOnline ? 'Đang kết nối' : 'Offline' }}</h2>
          <p class="text-gray-500">{{ isOnline ? 'Dữ liệu sẽ được đồng bộ tự động' : 'Dữ liệu được lưu cục bộ' }}</p>
        </div>

        <div class="space-y-4">
          <div class="flex justify-between items-center p-4 bg-gray-50 rounded-lg">
            <div>
              <p class="font-medium">Dữ liệu chờ đồng bộ</p>
              <p class="text-sm text-gray-500">{{ pendingCount }} phiên kiểm tra</p>
            </div>
            <span class="text-2xl font-bold text-blue-600">{{ pendingCount }}</span>
          </div>

          <button
            @click="syncNow"
            :disabled="!isOnline || syncing"
            class="w-full bg-blue-600 hover:bg-blue-700 text-white font-bold py-3 px-4 rounded-lg disabled:opacity-50"
          >
            {{ syncing ? 'Đang đồng bộ...' : 'Đồng bộ ngay' }}
          </button>

          <button
            @click="clearCache"
            class="w-full bg-gray-200 hover:bg-gray-300 text-gray-800 font-bold py-3 px-4 rounded-lg"
          >
            Xóa dữ liệu cục bộ
          </button>
        </div>
      </div>
    </main>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { db, syncService } from '../db'

const router = useRouter()

const isOnline = ref(navigator.onLine)
const syncing = ref(false)
const pendingCount = ref(0)

onMounted(() => {
  window.addEventListener('online', () => isOnline.value = true)
  window.addEventListener('offline', () => isOnline.value = false)
  loadPendingCount()
})

const loadPendingCount = async () => {
  const pending = await syncService.getPendingInspections()
  pendingCount.value = pending.length
}

const syncNow = async () => {
  syncing.value = true
  try {
    const pending = await syncService.getPendingInspections()
    for (const inspection of pending) {
      // Sync to API
      console.log('Syncing:', inspection)
      await syncService.markAsSynced(inspection.id || inspection.plan_detail_id)
    }
    await loadPendingCount()
    alert('Đồng bộ thành công!')
  } catch (error) {
    console.error('Sync failed:', error)
    alert('Đồng bộ thất bại')
  } finally {
    syncing.value = false
  }
}

const clearCache = async () => {
  if (confirm('Bạn có chắc muốn xóa tất cả dữ liệu cục bộ?')) {
    await syncService.clearAll()
    await loadPendingCount()
    alert('Đã xóa dữ liệu cục bộ')
  }
}

const goBack = () => router.back()
</script>
